<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

use craft\helpers\Html;

/**
 * Tracking code helper.
 *
 * Builds the front-end Clicky tracking snippet (the async `<script>` tag plus
 * an optional `<noscript>` pixel fallback) from a resolved Site ID, matching
 * the markup shown under Clicky → Prefs → Tracking code.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class TrackingCode
{
    // Public Methods
    // =========================================================================

    /**
     * Builds the tracking snippet markup for the given Site ID.
     *
     * @param string $siteId The resolved (numeric) Clicky Site ID.
     * @param bool $noScript Whether to include the `<noscript>` pixel fallback.
     * @return string The tracking snippet markup.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function html(string $siteId, bool $noScript): string
    {
        $id = Html::encode($siteId);

        $html = '<script async data-id="' . $id . '" src="//static.getclicky.com/js"></script>';

        if ($noScript) {
            $html .= '<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/' . $id . 'ns.gif" /></p></noscript>';
        }

        return $html;
    }
}
