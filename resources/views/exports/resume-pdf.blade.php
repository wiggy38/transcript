{{-- Export PDF du résumé : titre, résumé, points clés et chapitres, horodatages en tête de ligne.
     Les valeurs sont échappées par {{ }} ; mPDF lit le HTML tel quel. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
</head>
<body>
    <h1>{{ $titre }}</h1>

    <h2>{{ $libelles['resume'] }}</h2>
    <p>{{ $contenu['resume'] ?? '' }}</p>

    @if (($contenu['points'] ?? []) !== [])
        <h2>{{ $libelles['points'] }}</h2>
        <ul>
            @foreach ($contenu['points'] as $point)
                <li><span style="font-family: monospace;">[{{ \App\Support\Horodatage::format((int) $point['debut_ms']) }}]</span> {{ $point['texte'] }}</li>
            @endforeach
        </ul>
    @endif

    @if (($contenu['chapitres'] ?? []) !== [])
        <h2>{{ $libelles['chapitres'] }}</h2>
        @foreach ($contenu['chapitres'] as $chapitre)
            <h3><span style="font-family: monospace;">[{{ \App\Support\Horodatage::format((int) $chapitre['debut_ms']) }}]</span> {{ $chapitre['titre'] }}</h3>
            <p>{{ $chapitre['resume'] }}</p>
        @endforeach
    @endif
</body>
</html>
