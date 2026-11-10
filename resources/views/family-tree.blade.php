<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="is-admin" content="{{ auth()->user()?->isAdmin() ? 'true' : 'false' }}">
    <meta name="is-pin-viewer" content="{{ !empty($pinViewer) ? 'true' : 'false' }}">
    <title>Uy Tong Liong - Loo Siok Tin | Family Tree</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
