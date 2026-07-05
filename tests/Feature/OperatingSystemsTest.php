<?php

use johnhenry\clickyanalytics\helpers\OperatingSystems;

// ---------------------------------------------------------------------------
// Known families
// ---------------------------------------------------------------------------

describe('OperatingSystems::match(): known families', function () {
    it('matches a versioned Windows title', function () {
        expect(OperatingSystems::match('Windows 10'))
            ->toBe(['label' => 'Windows', 'slug' => 'windows']);
    });

    it('maps "Mac OS X" to macOS', function () {
        expect(OperatingSystems::match('Mac OS X 10.15'))
            ->toBe(['label' => 'macOS', 'slug' => 'apple']);
    });

    it('maps "OS X" to macOS', function () {
        expect(OperatingSystems::match('OS X'))
            ->toBe(['label' => 'macOS', 'slug' => 'apple']);
    });

    it('maps a Linux distro to the Linux family', function () {
        expect(OperatingSystems::match('Ubuntu 22.04'))
            ->toBe(['label' => 'Linux', 'slug' => 'linux']);
    });
});

// ---------------------------------------------------------------------------
// Precedence
// ---------------------------------------------------------------------------

describe('OperatingSystems::match(): precedence', function () {
    it('matches "Windows Phone" before plain "Windows"', function () {
        expect(OperatingSystems::match('Windows Phone 8'))
            ->toBe(['label' => 'Windows Phone', 'slug' => 'windows']);
    });

    it('maps iPadOS to its own label, not iOS', function () {
        expect(OperatingSystems::match('iPadOS 17')['label'])->toBe('iPadOS');
    });
});

// ---------------------------------------------------------------------------
// Fallback
// ---------------------------------------------------------------------------

describe('OperatingSystems::match(): fallback', function () {
    it('keeps the full title and returns a null slug for an unknown OS', function () {
        expect(OperatingSystems::match('HaikuOS'))
            ->toBe(['label' => 'HaikuOS', 'slug' => null]);
    });
});
