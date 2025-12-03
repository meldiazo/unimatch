<?php

namespace App\Support;

/**
 * Very small XLS (BIFF8) reader that converts the first sheet of a legacy
 * Excel workbook into a two dimensional array of rows/columns.
 *
 * The implementation intentionally supports only the subset of the format that
 * we need for the current import flow (numeric cells, shared strings, inline
 * labels and RK values). It is not a general purpose spreadsheet reader.
 */
class BinaryXlsReader
{
    /**
     * Attempts to parse the first worksheet of the workbook stored in $path.
     *
     * @return array<int, array<int, string>>
     */
    public static function extractRows(string $path): array
    {
        $binary = @file_get_contents($path);
        if ($binary === false) {
            return [];
        }

        $ole = new BiffOleDocument($binary);
        $stream = $ole->getStream('Workbook') ?? $ole->getStream('Book');
        if ($stream === null) {
            return [];
        }

        $parser = new BiffWorkbook($stream);

        return $parser->readFirstSheetRows();
    }
}

/**
 * Minimal OLE compound document reader that exposes workbook streams.
 */
class BiffOleDocument
{
    private const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
    private const ENDOFCHAIN = 0xFFFFFFFE;
    private const FREESECT = 0xFFFFFFFF;

    private string $binary;
    private int $sectorSize = 512;
    private int $miniSectorSize = 64;
    private int $miniStreamCutoff = 4096;
    private array $fat = [];
    private array $miniFat = [];
    private array $entries = [];
    private string $miniStream = '';

    public function __construct(string $binary)
    {
        $this->binary = $binary;
        $this->parseHeader();
    }

    private function parseHeader(): void
    {
        if (strlen($this->binary) < 512 || ! str_starts_with($this->binary, self::SIGNATURE)) {
            return;
        }

        $sectorShift = $this->readUInt16($this->binary, 0x1E);
        $miniSectorShift = $this->readUInt16($this->binary, 0x20);
        if ($sectorShift > 0) {
            $this->sectorSize = 1 << $sectorShift;
        }
        if ($miniSectorShift > 0) {
            $this->miniSectorSize = 1 << $miniSectorShift;
        }

        $firstDirSector = $this->readUInt32($this->binary, 0x30);
        $this->miniStreamCutoff = $this->readUInt32($this->binary, 0x38) ?: 4096;
        $firstMiniFatSector = $this->readUInt32($this->binary, 0x3C);
        $miniFatSectorCount = $this->readUInt32($this->binary, 0x40);
        $firstDifatSector = $this->readUInt32($this->binary, 0x44);
        $difatSectorCount = $this->readUInt32($this->binary, 0x48);

        $fatSectorIds = [];
        for ($i = 0; $i < 109; $i++) {
            $sector = $this->readUInt32($this->binary, 0x4C + ($i * 4));
            if ($sector === self::FREESECT) {
                continue;
            }
            $fatSectorIds[] = $sector;
        }

        $nextDifat = $firstDifatSector;
        for ($i = 0; $i < $difatSectorCount && $nextDifat !== self::ENDOFCHAIN; $i++) {
            $offset = $this->sectorOffset($nextDifat);
            $entries = $this->readSectorValues($offset);
            $pointerCount = ($this->sectorSize / 4) - 1;
            for ($j = 0; $j < $pointerCount; $j++) {
                $sector = $entries[$j] ?? self::FREESECT;
                if ($sector !== self::FREESECT) {
                    $fatSectorIds[] = $sector;
                }
            }
            $nextDifat = $entries[$pointerCount] ?? self::ENDOFCHAIN;
        }

        $this->fat = $this->buildFat($fatSectorIds);

        $directoryStream = $this->readStream($firstDirSector);
        if ($directoryStream === '') {
            return;
        }

        $this->parseDirectoryEntries($directoryStream);

        $this->miniFat = $this->buildMiniFat($firstMiniFatSector, $miniFatSectorCount);
        $this->miniStream = $this->buildMiniStream();
    }

    private function buildFat(array $sectorIds): array
    {
        $fat = [];
        foreach ($sectorIds as $sector) {
            $offset = $this->sectorOffset($sector);
            $values = $this->readSectorValues($offset);
            foreach ($values as $value) {
                $fat[] = $value;
            }
        }

        return $fat;
    }

    /**
     * @return array<int, int>
     */
    private function buildMiniFat(int $startSector, int $sectorCount): array
    {
        if ($startSector === self::FREESECT || $startSector === self::ENDOFCHAIN || $sectorCount === 0) {
            return [];
        }

        $chain = $this->getSectorChain($startSector, $sectorCount);
        $data = '';
        foreach ($chain as $sector) {
            $data .= substr($this->binary, $this->sectorOffset($sector), $this->sectorSize);
        }

        if ($data === '') {
            return [];
        }

        return array_values(unpack('V*', $data));
    }

    private function buildMiniStream(): string
    {
        $root = $this->entries['root entry'] ?? null;
        if (! $root || ($root['start_sector'] ?? self::ENDOFCHAIN) === self::ENDOFCHAIN) {
            return '';
        }

        $data = $this->readStream($root['start_sector'], $root['size'] ?? null);

        return $data ?: '';
    }

    private function parseDirectoryEntries(string $directoryStream): void
    {
        $entrySize = 128;
        $entryCount = intdiv(strlen($directoryStream), $entrySize);

        for ($i = 0; $i < $entryCount; $i++) {
            $entry = substr($directoryStream, $i * $entrySize, $entrySize);
            $nameLength = $this->readUInt16($entry, 64);
            if ($nameLength < 2) {
                continue;
            }

            $nameBinary = substr($entry, 0, $nameLength - 2);
            $name = strtolower(mb_convert_encoding($nameBinary, 'UTF-8', 'UTF-16LE'));
            $type = ord($entry[66]);
            $startSector = $this->readUInt32($entry, 116);
            $size = $this->readUInt64($entry, 120);

            $this->entries[$name] = [
                'type' => $type,
                'start_sector' => $startSector,
                'size' => $size,
            ];
        }
    }

    public function getStream(string $name): ?string
    {
        $key = strtolower($name);
        $entry = $this->entries[$key] ?? null;
        if (! $entry || ($entry['start_sector'] ?? self::ENDOFCHAIN) === self::ENDOFCHAIN) {
            return null;
        }

        $size = $entry['size'] ?? null;
        if ($size !== null && $size < $this->miniStreamCutoff && $this->miniStream !== '') {
            $data = $this->readMiniStream((int) $entry['start_sector'], (int) $size);
        } else {
            $data = $this->readStream((int) $entry['start_sector'], $size);
        }

        return $data === '' ? null : $data;
    }

    private function readStream(int $startSector, ?int $size = null): string
    {
        $chain = $this->getSectorChain($startSector);
        if (empty($chain)) {
            return '';
        }

        $buffer = '';
        foreach ($chain as $sector) {
            $buffer .= substr($this->binary, $this->sectorOffset($sector), $this->sectorSize);
        }

        if ($size !== null) {
            return substr($buffer, 0, $size);
        }

        return $buffer;
    }

    private function readMiniStream(int $startMiniSector, int $size): string
    {
        if ($this->miniStream === '' || empty($this->miniFat)) {
            return '';
        }

        $chain = [];
        $current = $startMiniSector;
        $guard = 0;
        $max = intdiv(strlen($this->miniStream), $this->miniSectorSize) + 1;
        while ($current !== self::ENDOFCHAIN && $current !== self::FREESECT && $guard < $max) {
            $chain[] = $current;
            $current = $this->miniFat[$current] ?? self::ENDOFCHAIN;
            $guard++;
        }

        if (empty($chain)) {
            return '';
        }

        $buffer = '';
        foreach ($chain as $sector) {
            $offset = $sector * $this->miniSectorSize;
            $buffer .= substr($this->miniStream, $offset, $this->miniSectorSize);
        }

        return substr($buffer, 0, $size);
    }

    /**
     * @return array<int, int>
     */
    private function getSectorChain(int $startSector, ?int $limit = null): array
    {
        $result = [];
        $current = $startSector;
        $guard = 0;
        $maxSectors = intdiv(strlen($this->binary), $this->sectorSize) + 2;
        while ($current !== self::ENDOFCHAIN && $current !== self::FREESECT && $current >= 0 && $guard < $maxSectors) {
            $result[] = $current;
            $next = $this->fat[$current] ?? self::ENDOFCHAIN;
            if ($limit !== null && count($result) >= $limit) {
                break;
            }
            $current = $next;
            $guard++;
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    private function readSectorValues(int $offset): array
    {
        $chunk = substr($this->binary, $offset, $this->sectorSize);
        if ($chunk === '') {
            return [];
        }

        return array_values(unpack('V*', $chunk));
    }

    private function sectorOffset(int $sector): int
    {
        return 512 + ($sector * $this->sectorSize);
    }

    private function readUInt16(string $binary, int $offset): int
    {
        $segment = substr($binary, $offset, 2);
        if ($segment === '') {
            return 0;
        }

        return unpack('v', $segment)[1];
    }

    private function readUInt32(string $binary, int $offset): int
    {
        $segment = substr($binary, $offset, 4);
        if ($segment === '') {
            return 0;
        }

        return unpack('V', $segment)[1];
    }

    private function readUInt64(string $binary, int $offset): int
    {
        $lo = $this->readUInt32($binary, $offset);
        $hi = $this->readUInt32($binary, $offset + 4);

        return ($hi << 32) | $lo;
    }
}

/**
 * Simplified BIFF8 workbook parser that extracts values from the first sheet.
 */
class BiffWorkbook
{
    private const RECORD_BOF = 0x0809;
    private const RECORD_EOF = 0x000A;
    private const RECORD_BOUNDSHEET = 0x0085;
    private const RECORD_SST = 0x00FC;
    private const RECORD_CONTINUE = 0x003C;
    private const RECORD_LABELSST = 0x00FD;
    private const RECORD_LABEL = 0x0204;
    private const RECORD_NUMBER = 0x0203;
    private const RECORD_FORMULA = 0x0406;
    private const RECORD_RK = 0x027E;
    private const RECORD_MULRK = 0x00BD;
    private const RECORD_CODEPAGE = 0x0042;

    private string $data;
    private array $sheets = [];
    private array $sharedStrings = [];
    private string $codepage = 'CP1252';

    public function __construct(string $data)
    {
        $this->data = $data;
        $this->parseWorkbookGlobals();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function readFirstSheetRows(): array
    {
        if (empty($this->sheets)) {
            return [];
        }

        $first = $this->sheets[0];

        return $this->parseWorksheet($first['offset']);
    }

    private function parseWorkbookGlobals(): void
    {
        $pos = 0;
        $length = strlen($this->data);

        while ($pos + 4 <= $length) {
            $code = $this->readUInt16($this->data, $pos);
            $size = $this->readUInt16($this->data, $pos + 2);
            $pos += 4;
            $body = substr($this->data, $pos, $size);
            $pos += $size;

            if ($code === self::RECORD_BOUNDSHEET) {
                $this->parseBoundSheet($body);
                continue;
            }

            if ($code === self::RECORD_CODEPAGE) {
                $this->parseCodepage($body);
                continue;
            }

            if ($code === self::RECORD_SST) {
                $buffer = $body;
                while ($pos + 4 <= $length) {
                    $nextCode = $this->readUInt16($this->data, $pos);
                    if ($nextCode !== self::RECORD_CONTINUE) {
                        break;
                    }
                    $continueSize = $this->readUInt16($this->data, $pos + 2);
                    $pos += 4;
                    $buffer .= substr($this->data, $pos, $continueSize);
                    $pos += $continueSize;
                }
                $this->sharedStrings = $this->parseSharedStrings($buffer);
            }
        }
    }

    private function parseBoundSheet(string $body): void
    {
        if (strlen($body) < 8) {
            return;
        }

        $offset = $this->readUInt32($body, 0);
        $nameLength = ord($body[6]);
        $flags = ord($body[7] ?? "\0");
        $nameBytes = substr($body, 8);

        if ($nameLength === 0) {
            $name = 'Sheet1';
        } elseif ($flags & 0x01) {
            $name = mb_convert_encoding(substr($nameBytes, 0, $nameLength * 2), 'UTF-8', 'UTF-16LE');
        } else {
            $source = strtoupper($this->codepage) === 'UTF-16LE' ? 'CP1252' : $this->codepage;
            $name = mb_convert_encoding(substr($nameBytes, 0, $nameLength), 'UTF-8', $source);
        }

        $this->sheets[] = [
            'name' => $name,
            'offset' => $offset,
        ];
    }

    private function parseCodepage(string $body): void
    {
        if (strlen($body) < 2) {
            return;
        }

        $value = unpack('v', substr($body, 0, 2))[1];

        $this->codepage = match ($value) {
            0x04B0 => 'UTF-16LE',
            0x04E4 => 'CP1252',
            0x2710 => 'macintosh',
            default => 'CP1252',
        };
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseWorksheet(int $offset): array
    {
        $rows = [];
        $pos = $offset;
        $length = strlen($this->data);

        while ($pos + 4 <= $length) {
            $code = $this->readUInt16($this->data, $pos);
            $size = $this->readUInt16($this->data, $pos + 2);
            $pos += 4;
            $body = substr($this->data, $pos, $size);
            $pos += $size;

            switch ($code) {
                case self::RECORD_BOF:
                    continue 2;
                case self::RECORD_EOF:
                    break 2;
                case self::RECORD_LABELSST:
                    $row = $this->readUInt16($body, 0);
                    $col = $this->readUInt16($body, 2);
                    $index = $this->readUInt32($body, 6);
                    $value = $this->sharedStrings[$index] ?? '';
                    $rows[$row][$col] = $value;
                    break;
                case self::RECORD_LABEL:
                    $row = $this->readUInt16($body, 0);
                    $col = $this->readUInt16($body, 2);
                    $stringReader = new BiffBinaryStream(substr($body, 6));
                    $rows[$row][$col] = $this->readUnicodeString($stringReader);
                    break;
                case self::RECORD_NUMBER:
                    $row = $this->readUInt16($body, 0);
                    $col = $this->readUInt16($body, 2);
                    $value = $this->readDouble(substr($body, 6, 8));
                    $rows[$row][$col] = $this->formatNumber($value);
                    break;
                case self::RECORD_RK:
                    $row = $this->readUInt16($body, 0);
                    $col = $this->readUInt16($body, 2);
                    $rk = $this->readUInt32($body, 6);
                    $value = $this->decodeRkValue($rk);
                    $rows[$row][$col] = $this->formatNumber($value);
                    break;
                case self::RECORD_MULRK:
                    $row = $this->readUInt16($body, 0);
                    $firstCol = $this->readUInt16($body, 2);
                    $col = $firstCol;
                    $pointer = 4;
                    while ($pointer + 6 <= strlen($body) - 2) {
                        $pointer += 2; // skip XF
                        $rkBytes = substr($body, $pointer, 4);
                        $rk = unpack('V', $rkBytes)[1];
                        $value = $this->decodeRkValue($rk);
                        $rows[$row][$col] = $this->formatNumber($value);
                        $pointer += 4;
                        $col++;
                    }
                    break;
                case self::RECORD_FORMULA:
                    $row = $this->readUInt16($body, 0);
                    $col = $this->readUInt16($body, 2);
                    $result = substr($body, 6, 8);
                    $value = $this->readDouble($result);
                    $rows[$row][$col] = $this->formatNumber($value);
                    break;
            }
        }

        if (empty($rows)) {
            return [];
        }

        ksort($rows);
        $normalized = [];
        foreach ($rows as $rowIndex => $cols) {
            ksort($cols);
            $normalizedRow = [];
            foreach ($cols as $value) {
                $normalizedRow[] = is_string($value) ? $value : (string) $value;
            }
            $normalized[] = $normalizedRow;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function parseSharedStrings(string $data): array
    {
        $reader = new BiffBinaryStream($data);
        $reader->readUInt32(); // total strings (unused)
        $uniqueCount = $reader->readUInt32();

        $strings = [];
        for ($i = 0; $i < $uniqueCount && $reader->remaining() > 0; $i++) {
            $strings[] = $this->readUnicodeString($reader);
        }

        return $strings;
    }

    private function readUnicodeString(BiffBinaryStream $reader): string
    {
        $charCount = $reader->readUInt16();
        if ($charCount === null) {
            return '';
        }

        $flags = $reader->readUInt8() ?? 0;
        $is16Bit = (bool) ($flags & 0x01);
        $hasRich = (bool) ($flags & 0x08);
        $hasExt = (bool) ($flags & 0x04);

        $runCount = $hasRich ? ($reader->readUInt16() ?? 0) : 0;
        $extSize = $hasExt ? ($reader->readUInt32() ?? 0) : 0;

        $charBytes = $is16Bit ? $charCount * 2 : $charCount;
        $textBytes = $reader->readBytes($charBytes);
        if ($is16Bit) {
            $text = mb_convert_encoding($textBytes, 'UTF-8', 'UTF-16LE');
        } else {
            $source = strtoupper($this->codepage) === 'UTF-16LE' ? 'CP1252' : $this->codepage;
            $text = mb_convert_encoding($textBytes, 'UTF-8', $source);
        }

        if ($runCount > 0) {
            $reader->skip($runCount * 4);
        }

        if ($extSize > 0) {
            $reader->skip($extSize);
        }

        return $text;
    }

    private function decodeRkValue(int $rk): float
    {
        $isMult100 = (bool) ($rk & 0x01);
        $isInteger = ! (bool) ($rk & 0x02);
        $value = 0.0;

        if ($isInteger) {
            $value = ($rk >> 2);
        } else {
            $raw = $rk & 0xFFFFFFFC;
            $packed = pack('VV', 0, $raw);
            $value = unpack('d', $packed)[1];
        }

        if ($isMult100) {
            $value /= 100;
        }

        return $value;
    }

    private function readDouble(string $bytes): float
    {
        if (strlen($bytes) !== 8) {
            return 0.0;
        }

        return unpack('d', $bytes)[1];
    }

    private function formatNumber(float $value): string
    {
        $isInt = abs($value - round($value)) < 0.0000001;

        return $isInt ? (string) (int) round($value) : rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }

    private function readUInt16(string $binary, int $offset): int
    {
        $segment = substr($binary, $offset, 2);
        if ($segment === '') {
            return 0;
        }

        return unpack('v', $segment)[1];
    }

    private function readUInt32(string $binary, int $offset): int
    {
        $segment = substr($binary, $offset, 4);
        if ($segment === '') {
            return 0;
        }

        return unpack('V', $segment)[1];
    }
}

/**
 * Small helper to read bytes from a binary buffer.
 */
class BiffBinaryStream
{
    private string $data;
    private int $offset = 0;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function readUInt8(): ?int
    {
        return $this->readUnpack('C', 1);
    }

    public function readUInt16(): ?int
    {
        return $this->readUnpack('v', 2);
    }

    public function readUInt32(): ?int
    {
        return $this->readUnpack('V', 4);
    }

    public function readBytes(int $length): string
    {
        $segment = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $segment;
    }

    public function skip(int $length): void
    {
        $this->offset += $length;
    }

    public function remaining(): int
    {
        return max(strlen($this->data) - $this->offset, 0);
    }

    private function readUnpack(string $format, int $length): ?int
    {
        if ($this->remaining() < $length) {
            $this->offset = strlen($this->data);

            return null;
        }

        $segment = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return unpack($format, $segment)[1];
    }
}
