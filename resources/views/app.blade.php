<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="{{ csrf_token() }}" name="csrf-token">
        <meta content="light" name="color-scheme" />

        <!-- Favicon -->
        <link href="/assets/favicon/apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180">
        <link href="/assets/favicon/favicon-32x32.png" rel="icon" sizes="32x32" type="image/png">
        <link href="/assets/favicon/favicon-16x16.png" rel="icon" sizes="16x16" type="image/png">
        <link href="/assets/favicon/site.webmanifest" rel="manifest">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Style -->
        <style>
            @font-face {
                font-family: "Inter";
                src: url("{{ asset('assets/fonts/inter/Inter-VariableFont_opsz,wght.ttf') }}") format("truetype");
                font-weight: 100 900;
                font-style: normal;
                font-display: swap;
                font-optical-sizing: auto;
            }

            @font-face {
                font-family: "Inter";
                src: url("{{ asset('assets/fonts/inter/Inter-Italic-VariableFont_opsz,wght.ttf') }}") format("truetype");
                font-weight: 100 900;
                font-style: italic;
                font-display: swap;
                font-optical-sizing: auto;
            }

            * {
                font-family: "Inter", sans-serif;
                font-optical-sizing: auto;
                font-style: normal;
            }

            html,
            body {
                print-color-adjust: exact;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>

    <body class="bg-[#f8f7fa] antialiased dark:bg-[#0a0a0a] print:bg-white">
        @inertia
    </body>

</html>
