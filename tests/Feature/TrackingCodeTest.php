<?php

use johnhenry\clickyanalytics\helpers\TrackingCode;

// ---------------------------------------------------------------------------
// Script tag
// ---------------------------------------------------------------------------

describe('TrackingCode::html(): script tag', function () {
    it('includes the site ID in the data-id attribute', function () {
        expect(TrackingCode::html('149191', false))
            ->toContain('data-id="149191"')
            ->toContain('src="//static.getclicky.com/js"');
    });

    it('is HTML-encoded against a malicious site ID', function () {
        $html = TrackingCode::html('"><script>alert(1)</script>', false);

        expect($html)->not->toContain('"><script>alert(1)</script>');
    });
});

// ---------------------------------------------------------------------------
// Noscript fallback
// ---------------------------------------------------------------------------

describe('TrackingCode::html(): noscript fallback', function () {
    it('includes the noscript pixel when enabled', function () {
        $html = TrackingCode::html('149191', true);

        expect($html)
            ->toContain('<noscript>')
            ->toContain('src="//in.getclicky.com/149191ns.gif"');
    });

    it('omits the noscript pixel when disabled', function () {
        expect(TrackingCode::html('149191', false))->not->toContain('<noscript>');
    });
});
