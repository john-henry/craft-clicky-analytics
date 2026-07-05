<?php

use johnhenry\clickyanalytics\helpers\Devices;

// ---------------------------------------------------------------------------
// Icon mapping
// ---------------------------------------------------------------------------

describe('Devices::icon()', function () {
    it('maps an iPad to a tablet', function () {
        expect(Devices::icon('Apple iPad'))->toBe('tablet');
    });

    it('maps a generic tablet to a tablet', function () {
        expect(Devices::icon('Android Tablet'))->toBe('tablet');
    });

    it('maps an iPhone to a phone', function () {
        expect(Devices::icon('Apple iPhone'))->toBe('phone');
    });

    it('maps an Android device to a phone', function () {
        expect(Devices::icon('Android device'))->toBe('phone');
    });

    it('maps a Mac to a desktop', function () {
        expect(Devices::icon('Apple Mac'))->toBe('desktop');
    });

    it('maps Windows to a desktop', function () {
        expect(Devices::icon('Windows PC'))->toBe('desktop');
    });

    it('falls back to a generic device for unknown hardware', function () {
        expect(Devices::icon('Smart Fridge'))->toBe('device');
    });
});

// ---------------------------------------------------------------------------
// Precedence
// ---------------------------------------------------------------------------

describe('Devices::icon(): precedence', function () {
    it('treats an iPad as a tablet, not a phone, despite "pad" not being "phone"', function () {
        // The tablet arm is evaluated before the phone arm.
        expect(Devices::icon('iPad Pro'))->toBe('tablet');
    });

    it('matches case-insensitively', function () {
        expect(Devices::icon('APPLE IPHONE'))->toBe('phone');
    });
});
