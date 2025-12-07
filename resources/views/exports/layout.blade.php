<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{{ $title ?? 'Reporte' }}</title>
  <style>
    * {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 12px;
    }

    body {
      margin: 0;
      padding: 24px;
      color: #222;
    }

    h2 {
      margin-top: 0;
      margin-bottom: 16px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #999;
      padding: 4px 6px;
      text-align: left;
    }

    th {
      background: #f2f2f2;
    }

    .text-right {
      text-align: right;
    }
  </style>
</head>
<body>
  <h2>{{ $title ?? 'Reporte descargado' }}</h2>
  @yield('content')
</body>
</html>
