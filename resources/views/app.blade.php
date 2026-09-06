<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#be123c">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="description" content="{{ config('group.name') }} — {{ config('group.tagline') }}">
        <link rel="icon" type="image/x-icon" href="/sj.ico">
        <link rel="apple-touch-icon" href="/images/logosj.png">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph / redes sociales -->
        <meta property="og:site_name" content="{{ config('group.name') }}">
        <meta property="og:locale" content="es_ES">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('group.name') }} | {{ config('group.claim') }}">
        <meta property="og:description" content="{{ config('group.tagline') }}">
        <meta property="og:image" content="{{ url('/images/logosj.png') }}">
        <meta name="twitter:card" content="summary_large_image">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|outfit:600,700,800&display=swap" rel="stylesheet" />

        {{-- Datos estructurados: ayuda a Google a mostrar el grupo en busquedas locales. --}}
        @php
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => ['Organization', 'LocalBusiness'],
                'name' => config('group.name'),
                'alternateName' => config('group.short_name'),
                'url' => url('/'),
                'logo' => url('/images/logosj.png'),
                'description' => config('group.tagline'),
                'foundingDate' => (string) config('group.founded_year'),
                'email' => config('group.contact.email'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => config('group.contact.address'),
                    'addressLocality' => config('group.contact.locality'),
                    'addressRegion' => config('group.contact.province') ?: config('group.contact.region'),
                    'postalCode' => config('group.contact.postal_code'),
                    'addressCountry' => 'ES',
                ],
                'memberOf' => [
                    '@type' => 'Organization',
                    'name' => config('group.federation'),
                ],
                'sameAs' => array_values(array_filter((array) config('group.social', []))),
            ];

            if ($phone = config('group.contact.phone')) {
                $structuredData['telephone'] = $phone;
            }
        @endphp
        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
