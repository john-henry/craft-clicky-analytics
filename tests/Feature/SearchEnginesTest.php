<?php

use johnhenry\clickyanalytics\helpers\SearchEngines;

// ---------------------------------------------------------------------------
// Known engines (including regional domains)
// ---------------------------------------------------------------------------

describe('SearchEngines::match(): known engines', function () {
    it('matches google.com', function () {
        expect(SearchEngines::match('google.com'))
            ->toBe(['label' => 'Google', 'slug' => 'google']);
    });

    it('collapses a regional Google domain into the Google brand', function () {
        expect(SearchEngines::match('google.co.uk'))
            ->toBe(['label' => 'Google', 'slug' => 'google']);
    });

    it('matches Bing', function () {
        expect(SearchEngines::match('bing.com'))
            ->toBe(['label' => 'Bing', 'slug' => 'bing']);
    });

    it('matches DuckDuckGo', function () {
        expect(SearchEngines::match('duckduckgo.com'))
            ->toBe(['label' => 'DuckDuckGo', 'slug' => 'duckduckgo']);
    });
});

// ---------------------------------------------------------------------------
// No bundled logo
// ---------------------------------------------------------------------------

describe('SearchEngines::match(): no logo', function () {
    it('returns a null slug for an engine without a bundled logo', function () {
        expect(SearchEngines::match('yandex.ru'))
            ->toBe(['label' => 'Yandex', 'slug' => null]);
    });
});

// ---------------------------------------------------------------------------
// Fallback
// ---------------------------------------------------------------------------

describe('SearchEngines::match(): fallback', function () {
    it('keeps the domain as the label and returns a null slug for an unknown engine', function () {
        expect(SearchEngines::match('example-search.net'))
            ->toBe(['label' => 'example-search.net', 'slug' => null]);
    });

    it('matches case-insensitively', function () {
        expect(SearchEngines::match('GOOGLE.COM')['label'])->toBe('Google');
    });
});
