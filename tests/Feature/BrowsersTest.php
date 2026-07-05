<?php

use johnhenry\clickyanalytics\helpers\Browsers;

// ---------------------------------------------------------------------------
// Known families
// ---------------------------------------------------------------------------

describe('Browsers::match(): known families', function () {
    it('matches a versioned Chrome title to the Google Chrome family', function () {
        $result = Browsers::match('Google Chrome 149.0 mobile');

        expect($result['label'])->toBe('Google Chrome');
        expect($result['slug'])->toBe('chrome');
    });

    it('matches Firefox', function () {
        expect(Browsers::match('Firefox 130.0'))
            ->toBe(['label' => 'Firefox', 'slug' => 'firefox']);
    });

    it('matches Safari', function () {
        expect(Browsers::match('Safari 17.4'))
            ->toBe(['label' => 'Safari', 'slug' => 'safari']);
    });
});

// ---------------------------------------------------------------------------
// Match precedence (order-sensitive needles)
// ---------------------------------------------------------------------------

describe('Browsers::match(): precedence', function () {
    it('matches Edge before Chrome even though Edge UAs contain "chrome"', function () {
        expect(Browsers::match('Microsoft Edge 120'))
            ->toBe(['label' => 'Microsoft Edge', 'slug' => 'edge']);
    });

    it('matches Brave before Chrome', function () {
        expect(Browsers::match('Brave 1.60'))
            ->toBe(['label' => 'Brave', 'slug' => 'brave']);
    });

    it('matches Samsung Internet before Chrome', function () {
        expect(Browsers::match('Samsung Internet 23'))
            ->toBe(['label' => 'Samsung Internet', 'slug' => 'samsung-internet']);
    });
});

// ---------------------------------------------------------------------------
// Brands with no bundled logo
// ---------------------------------------------------------------------------

describe('Browsers::match(): no logo', function () {
    it('returns a null slug for a recognised brand without a bundled logo', function () {
        expect(Browsers::match('Yandex Browser 24'))
            ->toBe(['label' => 'Yandex', 'slug' => null]);
    });
});

// ---------------------------------------------------------------------------
// Unknown + case-insensitivity
// ---------------------------------------------------------------------------

describe('Browsers::match(): fallback', function () {
    it('keeps the full title and returns a null slug for an unknown browser', function () {
        expect(Browsers::match('Some Weird Browser 9'))
            ->toBe(['label' => 'Some Weird Browser 9', 'slug' => null]);
    });

    it('matches case-insensitively', function () {
        expect(Browsers::match('FIREFOX 130')['label'])->toBe('Firefox');
    });
});
