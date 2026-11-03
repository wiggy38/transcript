<?php

use App\Support\Horodatage;

test('un horodatage sous l\'heure s\'affiche en mm:ss', function (int $ms, string $attendu) {
    expect(Horodatage::format($ms))->toBe($attendu);
})->with([
    'zéro' => [0, '00:00'],
    'treize secondes' => [13000, '00:13'],
    'arrondi vers le bas' => [13999, '00:13'],
    'douze minutes et demie' => [754500, '12:34'],
]);

test('au-delà d\'une heure, l\'horodatage s\'affiche en h:mm:ss', function () {
    expect(Horodatage::format(3 * 3600 * 1000 + 5000))->toBe('3:00:05')
        ->and(Horodatage::format(3600 * 1000))->toBe('1:00:00');
});

test('la durée ISO de l\'attribut datetime garde la milliseconde', function () {
    expect(Horodatage::iso(13250))->toBe('PT13.250S');
});
