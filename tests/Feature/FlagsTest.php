<?php

use johnhenry\clickyanalytics\helpers\Flags;

// ---------------------------------------------------------------------------
// Known countries
// ---------------------------------------------------------------------------

describe('Flags::code(): known countries', function () {
    it('maps a country name to a lower-case ISO code', function () {
        expect(Flags::code('Ireland'))->toBe('ie');
    });

    it('maps the United States', function () {
        expect(Flags::code('United States'))->toBe('us');
    });

    it('resolves common aliases to the same code', function () {
        expect(Flags::code('USA'))->toBe('us');
        expect(Flags::code('United States of America'))->toBe('us');
    });

    it('maps the home nations to GB', function () {
        expect(Flags::code('Scotland'))->toBe('gb');
        expect(Flags::code('Wales'))->toBe('gb');
        expect(Flags::code('England'))->toBe('gb');
    });
});

// ---------------------------------------------------------------------------
// Normalisation
// ---------------------------------------------------------------------------

describe('Flags::code(): normalisation', function () {
    it('strips a leading "the"', function () {
        expect(Flags::code('The Netherlands'))->toBe('nl');
    });

    it('is case-insensitive', function () {
        expect(Flags::code('IRELAND'))->toBe('ie');
    });

    it('trims surrounding whitespace', function () {
        expect(Flags::code('  Ireland  '))->toBe('ie');
    });

    it('always returns a lower-case code', function () {
        expect(Flags::code('Germany'))->toBe('de');
    });
});

// ---------------------------------------------------------------------------
// Unknown
// ---------------------------------------------------------------------------

describe('Flags::code(): unknown', function () {
    it('returns null for a country not in the map', function () {
        expect(Flags::code('Atlantis'))->toBeNull();
    });

    it('returns null for an empty string', function () {
        expect(Flags::code(''))->toBeNull();
    });
});
