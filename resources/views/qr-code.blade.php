<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Scan to open Proflect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="qr-access-body">
    <main class="qr-access-main" aria-labelledby="qr-access-title">
        <h1 id="qr-access-title" class="sr-only">Scan to open Proflect</h1>
        <canvas
            class="qr-access-code"
            data-qr-code
            data-qr-url="{{ $entryUrl }}"
            role="img"
            aria-label="QR code that opens Proflect"
        ></canvas>
        <p class="qr-access-error" data-qr-error hidden>QR code could not be displayed. Please refresh this page.</p>
    </main>
</body>
</html>
