<?php

test('les plans sont définis avec leurs exports', function () {
    $plans = config('voxia.plans');

    expect($plans)->toHaveKeys(['free', 'pro', 'pack', 'business']);

    foreach ($plans as $plan) {
        expect($plan['exports'])->toBeArray()->not->toBeEmpty();
    }
});

test('le plan Business est vendable en V2 (décision du 5 novembre 2026)', function () {
    expect(config('voxia.plans.business.sellable'))->toBeTrue()
        ->and(config('voxia.plans.free.sellable'))->toBeTrue()
        ->and(config('voxia.plans.pro.sellable'))->toBeTrue()
        ->and(config('voxia.plans.pack.sellable'))->toBeTrue();
});

test('le plan gratuit n\'exporte ni PDF ni VTT', function () {
    expect(config('voxia.plans.free.exports'))->toBe(['txt', 'srt']);
});

test('les formats de fichiers importés sont fixés par le cahier des charges', function () {
    expect(config('voxia.uploads.extensions'))
        ->toBe(['mp3', 'm4a', 'wav', 'mp4', 'mov', 'webm']);
});

test('les langues de l\'interface sont en et fr', function () {
    expect(config('voxia.locales'))->toBe(['en', 'fr']);
});

test('les valeurs numériques sont typées, même lues depuis le .env', function () {
    expect(config('voxia.plans.free.videos_per_month'))->toBeInt()
        ->and(config('voxia.plans.pro.price_monthly_usd'))->toBeFloat()
        ->and(config('voxia.costs.transcription_usd_per_hour'))->toBeFloat()
        ->and(config('voxia.monitoring.download_failure_alert_threshold'))->toBeFloat();
});
