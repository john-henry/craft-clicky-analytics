<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\variables;

use Craft;
use craft\base\ElementInterface;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;
use johnhenry\clickyanalytics\services\Api;
use Throwable;

/**
 * Clicky Analytics Twig variable.
 *
 * Exposes the Clicky API service to templates as `craft.clickyAnalytics.*`, so front-end
 * or CP templates can read live analytics without touching the service directly.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSoftFillColor(): string
    {
        return Palette::soft();
    }

    /**
     * Returns the four ranked-list row tints (rank 1 to 4) for the current scheme.
     *
     * Keyed by rank position, not by the widget's accent colour, so the first
     * row reads the same wherever it appears. A list longer than four rows
     * reuses the fourth, which is near neutral.
     *
     * @return string[] The four rank tints.
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function isConfigured(): bool
    {
        return $this->_api()->isConfigured();
    }

    /**
     * Returns the number of visitors currently online.
     *
     * @return int The online visitor count.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getVisitorsOnline(): int
    {
        return $this->_safely(fn() => $this->_api()->getVisitorsOnline(), 0);
    }

    /**
     * Returns the headline tallies for a date range.
     *
     * @param string $date A Clicky date expression.
     * @return array The overview tallies.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function overview(string $date = 'last-7-days'): array
    {
        return $this->_safely(fn() => $this->_api()->getOverview($date), []);
    }

    /**
     * Returns the top pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked page items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopPages($date, $limit), []);
    }

    /**
     * Returns the top traffic sources for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked traffic-source items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topSources(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopSources($date, $limit), []);
    }

    /**
     * Returns the top referring domains for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked referrer items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topReferrers(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopReferrers($date, $limit), []);
    }

    /**
     * Returns the top search engines for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked search-engine items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topSearchEngines(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopSearchEngines($date, $limit), []);
    }

    /**
     * Returns the top operating systems for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked operating-system items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topOperatingSystems(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopOperatingSystems($date, $limit), []);
    }

    /**
     * Returns the top countries for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked country items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCountries(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopCountries($date, $limit), []);
    }

    /**
     * Returns the top devices for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked device items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topDevices(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopDevices($date, $limit), []);
    }

    /**
     * Returns the top entry pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked entry-page items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function entryPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getEntryPages($date, $limit), []);
    }

    /**
     * Returns the top exit pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked exit-page items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function exitPages(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getExitPages($date, $limit), []);
    }

    /**
     * Returns the top downloads for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked download items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topDownloads(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopDownloads($date, $limit), []);
    }

    /**
     * Returns the top goal completions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked goal items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topGoals(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopGoals($date, $limit), []);
    }

    /**
     * Returns the top campaigns for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked campaign items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCampaigns(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopCampaigns($date, $limit), []);
    }

    /**
     * Returns analytics for a single page: an entry (or any element with a
     * URL), a full URL, or a path.
     *
     * @param ElementInterface|string $page The element, URL or path (e.g. `/blog/my-post`).
     * @param string $date A Clicky date expression.
     * @param int $visitorLimit The maximum recent visitors to include.
     * @return array The page stats payload, or an empty array if the page has no URL or Clicky can't be reached.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function pageStats(ElementInterface|string $page, string $date = 'last-28-days', int $visitorLimit = 5): array
    {
        $api = $this->_api();

        if ($page instanceof ElementInterface) {
            $api = $api->forSite($page->getSite());
            $page = (string)$page->getUrl();

            if ($page === '') {
                return [];
            }
        }

        return $this->_safely(fn() => $api->getPageStats(Api::pathFromUrl($page), $date, $visitorLimit), []);
    }

    /**
     * Returns the top cities for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked city items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topCities(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopCities($date, $limit), []);
    }

    /**
     * Returns the top regions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked region items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topRegions(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopRegions($date, $limit), []);
    }

    /**
     * Returns the top screen resolutions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked screen-resolution items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topScreenResolutions(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopScreenResolutions($date, $limit), []);
    }

    /**
     * Returns the most recent visitors for the live feed.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of visitors.
     * @return array The recent-visitor rows.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function recentVisitors(string $date = 'last-7-days', int $limit = 12): array
    {
        return $this->_safely(fn() => $this->_api()->getRecentVisitors($date, $limit), []);
    }

    /**
     * Returns the top web browsers for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array The ranked browser items.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function topBrowsers(string $date = 'last-7-days', int $limit = 10): array
    {
        return $this->_safely(fn() => $this->_api()->getTopBrowsers($date, $limit), []);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the API service for the current site's Clicky credentials.
     *
     * @return Api The API service.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _api(): Api
    {
        return ClickyAnalytics::getInstance()->getApi()->forSite(Craft::$app->getSites()->getCurrentSite());
    }

    /**
     * Runs an API call for a template, returning the fallback instead when it
     * fails. Twig can't catch exceptions, so an unreachable Clicky would
     * otherwise take the whole page down with it.
     *
     * @template T
     * @param callable(): T $call The API call.
     * @param T $fallback What to return if it fails.
     * @return T The call's result, or the fallback.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _safely(callable $call, mixed $fallback): mixed
    {
        try {
            return $call();
        } catch (Throwable $e) {
            Craft::warning('Clicky data for a template couldn’t be loaded: ' . $e->getMessage(), 'clicky-analytics');

            return $fallback;
        }
    }
}
