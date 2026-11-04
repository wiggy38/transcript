<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: geist, notosans; font-size: 10.5pt; line-height: 1.55; color: #1F201E; }
        h1 { font-family: geist; font-weight: bold; font-size: 18pt; line-height: 1.25; margin: 0 0 8pt 0; color: #121311; }
        .meta { font-family: geistmono; font-size: 8.5pt; color: #5F5E59; margin: 0 0 3pt 0; }
        .filet { border-top: 0.5pt solid #C9C8C2; margin: 14pt 0 16pt 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0 0 9pt 0; }
        td.heure { width: 52pt; padding-top: 2.5pt; font-family: geistmono; font-size: 8.5pt; color: #5F5E59; }
        p.paragraphe { margin: 0 0 9pt 0; }
    </style>
</head>
<body>
    <h1>{!! $titre !!}</h1>
    {{-- Les valeurs passées avec !! sont déjà échappées par ExporteurPdf::html(). --}}
    <p class="meta">{{ $libelleSource }} : {!! $urlSource ?? e($libelleFichier) !!}</p>
    @if ($date)
        <p class="meta">{{ $libelleDate }} : {{ $date }}</p>
    @endif
    <div class="filet"></div>

    @if ($avecHorodatages)
        <table>
            @foreach ($paragraphes as $paragraphe)
                <tr>
                    <td class="heure">{{ $paragraphe['heure'] }}</td>
                    <td>{!! $paragraphe["texte"] !!}</td>
                </tr>
            @endforeach
        </table>
    @else
        @foreach ($paragraphes as $paragraphe)
            <p class="paragraphe">{!! $paragraphe["texte"] !!}</p>
        @endforeach
    @endif
</body>
</html>
