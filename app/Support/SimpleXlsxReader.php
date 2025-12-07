<?php

namespace App\Support;

class SimpleXlsxReader
{
    /**
     * @return array<int, array<int, string>>
     */
    public static function extractRows(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = static::parseSharedStrings($zip);
        $sheetPath = static::resolveFirstSheetPath($zip);
        if (! $sheetPath) {
            $zip->close();

            return [];
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        return static::parseSheetXml($sheetXml, $sharedStrings);
    }

    private static function resolveFirstSheetPath(\ZipArchive $zip): ?string
    {
        $default = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($default) !== false) {
            return $default;
        }

        $rels = [];
        if (($relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false) {
            $doc = new \SimpleXMLElement($relsXml);
            $doc->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            foreach ($doc->Relationship as $relationship) {
                $attrs = $relationship->attributes();
                if (! $attrs) {
                    continue;
                }
                $rels[(string) $attrs->Id] = 'xl/'.ltrim((string) $attrs->Target, '/');
            }
        }

        if (($workbookXml = $zip->getFromName('xl/workbook.xml')) !== false) {
            $doc = new \SimpleXMLElement($workbookXml);
            $doc->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $sheets = $doc->sheets->sheet ?? [];
            foreach ($sheets as $sheet) {
                $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                if ($attrs && isset($attrs->id) && isset($rels[(string) $attrs->id])) {
                    $target = $rels[(string) $attrs->id];
                    if ($zip->locateName($target) !== false) {
                        return $target;
                    }
                }
            }
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'xl/worksheets/') && str_ends_with($name, '.xml')) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private static function parseSharedStrings(\ZipArchive $zip): array
    {
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml === false) {
            return [];
        }

        $doc = new \SimpleXMLElement($sharedXml);
        $doc->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];

        foreach ($doc->si as $si) {
            $strings[] = static::extractTextFromRichNode($si);
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array<int, string>>
     */
    private static function parseSheetXml(string $xml, array $sharedStrings): array
    {
        $doc = new \SimpleXMLElement($xml);
        $doc->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($doc->sheetData->row ?? [] as $row) {
            $rowIndexAttr = (int) ($row['r'] ?? 0);
            $rowIndex = $rowIndexAttr > 0 ? $rowIndexAttr - 1 : count($rows);
            foreach ($row->c as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = static::columnIndexFromReference($reference);
                $type = (string) ($cell['t'] ?? '');

                $value = '';
                if ($type === 's') {
                    $sharedIndex = isset($cell->v) ? (int) $cell->v : null;
                    $value = $sharedIndex !== null && isset($sharedStrings[$sharedIndex]) ? $sharedStrings[$sharedIndex] : '';
                } elseif ($type === 'inlineStr') {
                    $value = static::extractTextFromRichNode($cell->is);
                } elseif ($type === 'b') {
                    $value = ((string) $cell->v) === '1' ? 'TRUE' : 'FALSE';
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }

                $rows[$rowIndex][$columnIndex] = $value;
            }
        }

        if (empty($rows)) {
            return [];
        }

        ksort($rows);
        $normalized = [];
        foreach ($rows as $columns) {
            if (empty($columns)) {
                continue;
            }
            ksort($columns);
            $maxIndex = array_key_last($columns);
            $normalizedRow = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $value = $columns[$i] ?? '';
                $normalizedRow[$i] = is_numeric($value) ? (string) $value : $value;
            }
            $normalized[] = $normalizedRow;
        }

        return $normalized;
    }

    private static function extractTextFromRichNode(\SimpleXMLElement $node): string
    {
        $texts = [];
        if (isset($node->t)) {
            $texts[] = (string) $node->t;
        }

        foreach ($node->r as $run) {
            if (isset($run->t)) {
                $texts[] = (string) $run->t;
            }
        }

        if (empty($texts)) {
            foreach ($node->children() as $child) {
                if ($child->getName() === 't') {
                    $texts[] = (string) $child;
                }
            }
        }

        return implode('', $texts);
    }

    private static function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return 0;
        }

        if (! preg_match('/([A-Z]+)(\d+)/i', $reference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($letters[$i]) - 64;
        }

        return max($index - 1, 0);
    }
}
