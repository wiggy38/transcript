<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: geist, notosans; font-size: 10.5pt; line-height: 1.55; color: #1F201E; }
        h1 { font-family: geist; font-weight: bold; font-size: 18pt; line-height: 1.25; margin: 0 0 8pt 0; color: #121311; }
        .meta { font-family: geistmono; font-size: 8.5pt; color: #5F5E59; margin: 0 0 3pt 0; }
        .filet { border-top: 0.5pt solid #C9C8C2; margin: 14pt 0 16pt 0; }
        h2 { font-family: geistmono; font-weight: normal; font-size: 8.5pt; color: #5F5E59; margin: 0 0 3pt 0; text-transform: uppercase; }
        p.texte { margin: 0 0 12pt 0; font-size: 11pt; }
        p.absente { margin: 0 0 12pt 0; color: #8B8A85; }
    </style>
</head>
<body>
    <h1>{{ $titre }}</h1>
    <p class="meta">{{ $libelleAnalyse }}</p>
    <div class="filet"></div>

    @foreach ($parties as $partie)
        @if ($partie['detectee'])
            <h2>{{ $partie['libelle'] }} · {{ $partie['horodatage'] }}</h2>
            <p class="texte">« {{ $partie['texte'] }} »</p>
        @else
            <h2>{{ $partie['libelle'] }} · {{ __('analyse.non_detectee') }}</h2>
            <p class="absente">—</p>
        @endif
    @endforeach
</body>
</html>
