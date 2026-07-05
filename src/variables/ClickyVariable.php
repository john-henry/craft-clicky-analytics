<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\variables;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Clicky Analytics Twig variable.
 *
 * Exposes the Clicky API service to templates as `craft.clickyAnalytics.*`, so front-end
 * or CP templates can read live analytics without touching the service directly.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class ClickyVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the primary accent colour for the current colour scheme.
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getPrimaryColor(): string
    {
        return Palette::primary();
    }

    /**
     * Returns the accent colour for a widget role under the current scheme.
     *
     * @param string $role A role key (e.g. `sources`).
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function accent(string $role): string
    {
        return Palette::accent($role);
    }

    /**
     * Returns the pie-slice colours for the current colour scheme.
     *
     * @return string[] The pie palette.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getPieColors(): array
    {
        return Palette::pie();
    }

    /**
     * Returns the secondary accent colour for the current colour scheme.
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSecondaryColor(): string
    {
        return Palette::secondary();
    }

    /**
     * Returns the soft area-fill colour for the current colour scheme.
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSoftFillColor(): string
    {
        return Palette::soft();
    }

    /**
     * Returns the four ranked-list row tints (rank 1 to 4) for the current scheme.
     *
     * @return string[] The four rank tints.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getRankBarColors(): array
    {
        return Palette::bars();
    }

    /**
     * Returns the published URL for a file bundled in the plugin's asset dist
     * directory (e.g. `flags/ie.svg`, `browsers/chrome.svg`).
     *
     * @param string $path The path within the dist directory.
     * @return string The published URL.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function assetUrl(string $path = ''): string
    {
        $dir = dirname(__DIR__) . '/assetbundles/dist';
        $base = Craft::$app->getAssetManager()->getPublishedUrl($dir, true);

        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }

    /**
     * Returns whether Clicky credentials are configured.
     *
     * @return bool Whether the API is configured.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function isConfigured(): bool
    {
        return ClickyAnalytics::getInstance()->getApi()->isConfigured();
    }

    /**
     * Returns the number of visitors currently online.
     *
     * @return int The online visitor count.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getVisitorsOnline(): int
    {
        return ClickyAnalytics::getInstance()->getApi()->getVisitorsOnline();
    }

    /**
     * Returns the headline tallies for a date range.
     *
     * @param string $date A Clicky date expression.
     * @return array The overview tallies.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function overview(string $date = 'last-7-days'): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getOverview($date);
    }

    /**
     * Returns the top pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked page items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopPages($date, $limit);
    }

    /**
     * Returns the top traffic sources for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked traffic-source items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topSources(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopSources($date, $limit);
    }

    /**
     * Returns the top referring domains for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked referrer items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topReferrers(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopReferrers($date, $limit);
    }

    /**
     * Returns the top search engines for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked search-engine items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topSearchEngines(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopSearchEngines($date, $limit);
    }

    /**
     * Returns the top operating systems for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked operating-system items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topOperatingSystems(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopOperatingSystems($date, $limit);
    }

    /**
     * Returns the top countries for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked country items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCountries(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopCountries($date, $limit);
    }

    /**
     * Returns the top devices for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked device items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topDevices(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopDevices($date, $limit);
    }

    /**
     * Returns the top entry pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked entry-page items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function entryPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getEntryPages($date, $limit);
    }

    /**
     * Returns the top exit pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked exit-page items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function exitPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getExitPages($date, $limit);
    }

    /**
     * Returns the top downloads for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked download items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topDownloads(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopDownloads($date, $limit);
    }

    /**
     * Returns the top goal completions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked goal items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topGoals(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopGoals($date, $limit);
    }

    /**
     * Returns the top campaigns for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked campaign items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCampaigns(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopCampaigns($date, $limit);
    }

    /**
     * Returns analytics for a single page path.
     *
     * @param string $href The page path (e.g. `/blog/my-post`).
     * @param string $date A Clicky date expression.
     * @param int $visitorLimit The maximum recent visitors to include.
     * @return array The page stats payload.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function pageStats(string $href, string $date = 'last-90-days', int $visitorLimit = 5): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getPageStats($href, $date, $visitorLimit);
    }

    /**
     * Returns the top cities for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked city items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCities(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopCities($date, $limit);
    }

    /**
     * Returns the top regions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked region items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topRegions(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopRegions($date, $limit);
    }

    /**
     * Returns the top screen resolutions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked screen-resolution items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topScreenResolutions(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopScreenResolutions($date, $limit);
    }

    /**
     * Returns the most recent visitors for the live feed.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of visitors.
     * @return array The recent-visitor rows.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function recentVisitors(string $date = 'last-7-days', int $limit = 12): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getRecentVisitors($date, $limit);
    }

    /**
     * Returns the top web browsers for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked browser items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topBrowsers(string $date = 'last-7-days', int $limit = 10): array
    {
        return ClickyAnalytics::getInstance()->getApi()->getTopBrowsers($date, $limit);
    }
}
