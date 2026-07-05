<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Browsers;
use johnhenry\clickyanalytics\helpers\Devices;
use johnhenry\clickyanalytics\helpers\Flags;
use johnhenry\clickyanalytics\helpers\OperatingSystems;
use johnhenry\clickyanalytics\helpers\SearchEngines;
use RuntimeException;

/**
 * Clicky API service.
 *
 * Wraps the Clicky stats API (https://clicky.com/help/api). A single low-level
 * request method packs one or more report "types" into each call, normalises
 * Clicky's nested `[{type, dates:[{items:[…]}]}]` response into flat arrays, and
 * caches results for the configured duration. The typed helper methods on top of
 * it (overview, top pages, …) are what the dashboard, widgets and Twig variable
 * consume.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class Api extends Component
{
    // Constants
    // =========================================================================

    /**
     * @var string The Clicky stats API endpoint.
     */
    public const ENDPOINT = 'https://api.clicky.com/api/stats/4';

    // Private Properties
    // =========================================================================

    /**
     * @var Client|null An injected HTTP client, used by tests to mock
     * Clicky API responses. When null, a real Guzzle client is created per request.
     */
    private ?Client $_httpClient = null;

    // Public Methods
    // =========================================================================

    /**
     * Injects an HTTP client to use instead of a real Guzzle client.
     *
     * Test-only seam - lets tests exercise `_request()`'s and `ping()`'s error
     * handling (timeouts, non-2xx responses, malformed JSON) against a Guzzle
     * `MockHandler` without making a real network call.
     *
     * @param Client|null $client The client to use, or null to restore the default.
     * @return void
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function setHttpClient(?Client $client): void
    {
        $this->_httpClient = $client;
    }

    /**
     * Returns whether the plugin has both a Site ID and a Sitekey configured.
     *
     * @return bool Whether the API is configured.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function isConfigured(): bool
    {
        $settings = ClickyAnalytics::getInstance()->getSettings();
        return $settings->getSiteId() !== null && $settings->getSiteKey() !== null;
    }

    /**
     * Fetches one or more report types for a date (range), returning each type's
     * items keyed by type name.
     *
     * @param string[] $types The Clicky report types (e.g. `['pages', 'countries']`).
     * @param string $date A Clicky date expression (e.g. `today`, `last-7-days`).
     * @param array $params Extra query parameters (e.g. `['limit' => 10]`).
     * @param bool $bypassCache Whether to skip the response cache and always fetch fresh (used for the real-time online-visitor tally).
     * @return array<string, array<int, array>> Items keyed by report type.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getStats(array $types, string $date, array $params = [], bool $bypassCache = false): array
    {
        $response = $this->_request($types, $date, $params, $bypassCache);

        $results = [];
        foreach ($types as $type) {
            $results[$type] = [];
        }

        foreach ($response as $block) {
            $type = $block['type'] ?? null;
            if ($type === null || !isset($results[$type])) {
                continue;
            }

            // Without `daily`, there is a single date block; flatten its items.
            foreach (($block['dates'] ?? []) as $dateBlock) {
                foreach (($dateBlock['items'] ?? []) as $item) {
                    // Clicky HTML-encodes titles (e.g. "&amp;"); decode for display.
                    if (isset($item['title'])) {
                        $item['title'] = html_entity_decode((string)$item['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    // Referrer/stats URLs are attacker-influenceable (e.g. a
                    // crafted Referer header) and are rendered into href
                    // attributes downstream; strip any non-http(s) scheme.
                    if (isset($item['url'])) {
                        $item['url'] = self::_safeUrl((string)$item['url']);
                    }
                    if (isset($item['stats_url'])) {
                        $item['stats_url'] = self::_safeUrl((string)$item['stats_url']);
                    }
                    $results[$type][] = $item;
                }
            }
        }

        return $results;
    }

    /**
     * Returns the single scalar value of a tally report type (e.g. `visitors`).
     *
     * @param string $type The tally report type.
     * @param string $date A Clicky date expression.
     * @param bool $bypassCache Whether to skip the response cache and always fetch fresh.
     * @return int The tally value (0 if absent).
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTally(string $type, string $date, bool $bypassCache = false): int
    {
        $items = $this->getStats([$type], $date, [], $bypassCache)[$type] ?? [];
        return (int)($items[0]['value'] ?? 0);
    }

    /**
     * Returns the number of visitors currently online.
     *
     * Bypasses the response cache - unlike the rest of the API, a stale count
     * defeats the purpose of a widget titled "Visitors Online".
     *
     * @return int The online visitor count.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getVisitorsOnline(): int
    {
        return $this->getTally('visitors-online', 'today', bypassCache: true);
    }

    /**
     * Returns the new vs returning visitor split for a date range.
     *
     * @param string $date A Clicky date expression.
     * @return array{new: int, returning: int, total: int} The visitor split.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getNewVsReturning(string $date): array
    {
        $stats = $this->getStats(['visitors', 'visitors-new'], $date);

        $total = (int)($stats['visitors'][0]['value'] ?? 0);
        $new = (int)($stats['visitors-new'][0]['value'] ?? 0);

        return [
            'new' => $new,
            'returning' => max(0, $total - $new),
            'total' => $total,
        ];
    }

    /**
     * Returns the headline metrics for a date range: visitors, actions, bounce
     * rate and average visit time, each as a value plus, when a comparison
     * period is given, a period-over-period trend.
     *
     * @param string $date A Clicky date expression.
     * @param string|null $previousDate A Clicky date expression for the prior period (for trends).
     * @return array<string, array> The overview metrics, keyed visitors/actions/bounceRate/time.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getOverview(string $date, ?string $previousDate = null): array
    {
        $types = ['visitors', 'actions', 'bounce-rate', 'time-average'];
        $current = $this->getStats($types, $date);

        $previous = null;
        if ($previousDate !== null) {
            try {
                $previous = $this->getStats($types, $previousDate);
            } catch (RuntimeException) {
                // Trends are a nicety; never let a failed comparison break the page.
                $previous = null;
            }
        }

        $value = static fn(?array $stats, string $type): int => (int)($stats[$type][0]['value'] ?? 0);

        $time = $this->_metric($value($current, 'time-average'), $previous ? $value($previous, 'time-average') : null, true);
        $time['pretty'] = $this->_prettyTime($time['value']);

        return [
            'visitors' => $this->_metric($value($current, 'visitors'), $previous ? $value($previous, 'visitors') : null, true),
            'actions' => $this->_metric($value($current, 'actions'), $previous ? $value($previous, 'actions') : null, true),
            'bounceRate' => $this->_metric($value($current, 'bounce-rate'), $previous ? $value($previous, 'bounce-rate') : null, false),
            'time' => $time,
        ];
    }

    /**
     * Returns a per-day series of a tally type, suitable for charting.
     *
     * @param string $type The tally report type (e.g. `visitors`).
     * @param string $date A Clicky date expression spanning multiple days.
     * @return array<int, array{date: string, value: int}> The daily series, oldest first.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getDailySeries(string $type, string $date): array
    {
        $response = $this->_request([$type], $date, ['daily' => 1]);

        $series = [];
        foreach ($response as $block) {
            if (($block['type'] ?? null) !== $type) {
                continue;
            }

            foreach (($block['dates'] ?? []) as $dateBlock) {
                $series[] = [
                    'date' => (string)($dateBlock['date'] ?? ''),
                    'value' => (int)($dateBlock['items'][0]['value'] ?? 0),
                ];
            }
        }

        // Clicky returns dates newest-first; reverse for a left-to-right chart.
        return array_reverse($series);
    }

    /**
     * Returns the top pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked page items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopPages(string $date, int $limit = 10): array
    {
        return $this->getStats(['pages'], $date, ['limit' => $limit])['pages'] ?? [];
    }

    /**
     * Returns the top traffic sources for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked traffic-source items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopSources(string $date, int $limit = 10): array
    {
        return $this->getStats(['traffic-sources'], $date, ['limit' => $limit])['traffic-sources'] ?? [];
    }

    /**
     * Returns the top referring domains (external links) for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked referrer items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopReferrers(string $date, int $limit = 10): array
    {
        return $this->getStats(['links-domains'], $date, ['limit' => $limit])['links-domains'] ?? [];
    }

    /**
     * Returns the top countries for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @param bool $withDrilldown Whether to flag rows that have city/region data (`canDrill`).
     * @return array<int, array> The ranked country items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopCountries(string $date, int $limit = 10, bool $withDrilldown = false): array
    {
        $countries = $this->getStats(['countries'], $date, ['limit' => $limit])['countries'] ?? [];
        $hasBreakdown = $withDrilldown ? $this->getCountriesWithBreakdown($date) : [];

        foreach ($countries as &$country) {
            // Attach an ISO code per country so the UI can show its flag.
            $country['code'] = Flags::code((string)($country['title'] ?? ''));

            if ($withDrilldown) {
                $country['canDrill'] = isset($hasBreakdown[$this->_countryKey((string)($country['title'] ?? ''))]);
            }
        }
        unset($country);

        return $countries;
    }

    /**
     * Returns the top web browsers for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @param bool|null $consolidate Whether to merge versions; null consolidates by default.
     * @return array<int, array> The ranked browser items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopBrowsers(string $date, int $limit = 10, ?bool $consolidate = null): array
    {
        $consolidate ??= true;

        // When consolidating, pull a deep list so grouping isn't truncated.
        $rows = $this->getStats(['web-browsers'], $date, ['limit' => $consolidate ? 100 : $limit])['web-browsers'] ?? [];

        if ($consolidate) {
            $rows = array_slice(
                $this->_consolidate($rows, static fn(string $t): string => Browsers::match($t)['label']),
                0,
                $limit
            );
        }

        foreach ($rows as &$row) {
            $slug = Browsers::match((string)($row['title'] ?? ''))['slug'];
            $row['logo'] = $slug !== null ? 'browsers/' . $slug : null;
            $row['logoFallback'] = 'window';
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the top operating systems for a date range, optionally consolidating
     * versions (e.g. "Windows 10" + "Windows 11" → "Windows").
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @param bool|null $consolidate Whether to merge versions; null consolidates by default.
     * @return array<int, array> The ranked operating-system items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopOperatingSystems(string $date, int $limit = 10, ?bool $consolidate = null): array
    {
        $consolidate ??= true;

        $rows = $this->getStats(['operating-systems'], $date, ['limit' => $consolidate ? 100 : $limit])['operating-systems'] ?? [];

        if ($consolidate) {
            $rows = array_slice(
                $this->_consolidate($rows, static fn(string $t): string => OperatingSystems::match($t)['label']),
                0,
                $limit
            );
        }

        foreach ($rows as &$row) {
            $slug = OperatingSystems::match((string)($row['title'] ?? ''))['slug'];
            $row['logo'] = $slug !== null ? 'os/' . $slug : null;
            $row['logoFallback'] = 'desktop';
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the top search engines for a date range, grouped by brand (so a
     * brand's regional domains collapse into one row) with a brand logo.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked search-engine items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopSearchEngines(string $date, int $limit = 10): array
    {
        $rows = $this->getStats(['searches-engines'], $date, ['limit' => 100])['searches-engines'] ?? [];

        $rows = array_slice(
            $this->_consolidate($rows, static fn(string $t): string => SearchEngines::match($t)['label']),
            0,
            $limit
        );

        foreach ($rows as &$row) {
            $slug = SearchEngines::match((string)($row['title'] ?? ''))['slug'];
            $row['logo'] = $slug !== null ? 'search/' . $slug : null;
            $row['logoFallback'] = 'globe';
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the top devices (hardware) for a date range, each tagged with a
     * device-type icon name (phone / tablet / desktop / device).
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked device items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopDevices(string $date, int $limit = 10): array
    {
        $rows = $this->getStats(['hardware'], $date, ['limit' => $limit])['hardware'] ?? [];

        foreach ($rows as &$row) {
            $row['logo'] = null;
            $row['logoFallback'] = Devices::icon((string)($row['title'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the top entrance (landing) pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked entrance-page items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getEntryPages(string $date, int $limit = 10): array
    {
        return $this->getStats(['pages-entrance'], $date, ['limit' => $limit])['pages-entrance'] ?? [];
    }

    /**
     * Returns the top exit pages for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked exit-page items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getExitPages(string $date, int $limit = 10): array
    {
        return $this->getStats(['pages-exit'], $date, ['limit' => $limit])['pages-exit'] ?? [];
    }

    /**
     * Returns the top downloads for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked download items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopDownloads(string $date, int $limit = 10): array
    {
        return $this->getStats(['downloads'], $date, ['limit' => $limit])['downloads'] ?? [];
    }

    /**
     * Returns the top goal completions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked goal items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopGoals(string $date, int $limit = 10): array
    {
        return $this->getStats(['goals'], $date, ['limit' => $limit])['goals'] ?? [];
    }

    /**
     * Returns the top campaigns for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked campaign items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopCampaigns(string $date, int $limit = 10): array
    {
        return $this->getStats(['campaigns'], $date, ['limit' => $limit])['campaigns'] ?? [];
    }

    /**
     * Returns the top screen resolutions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked screen-resolution items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopScreenResolutions(string $date, int $limit = 10): array
    {
        return $this->getStats(['screen-resolutions'], $date, ['limit' => $limit])['screen-resolutions'] ?? [];
    }

    /**
     * Returns the top cities for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked city items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopCities(string $date, int $limit = 10): array
    {
        return $this->getStats(['cities'], $date, ['limit' => $limit])['cities'] ?? [];
    }

    /**
     * Returns the top regions for a date range.
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked region items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTopRegions(string $date, int $limit = 10): array
    {
        return $this->getStats(['regions'], $date, ['limit' => $limit])['regions'] ?? [];
    }

    /**
     * Returns the cities and regions for a single country, filtered from the
     * global lists by country name (Clicky has no server-side country filter).
     * Used by the Top Countries drilldown.
     *
     * @param string $country The country name (as shown in the countries report).
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum rows per breakdown.
     * @return array{cities: array<int, array>, regions: array<int, array>} The breakdown.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getCountryBreakdown(string $country, string $date, int $limit = 5): array
    {
        $stats = $this->getStats(['cities', 'regions'], $date, ['limit' => 100]);

        return [
            'cities' => $this->_filterByCountry($stats['cities'] ?? [], $country, $limit),
            'regions' => $this->_filterByCountry($stats['regions'] ?? [], $country, $limit),
        ];
    }

    /**
     * Returns the set of countries that have any city or region data for a date
     * range, keyed by normalised country name, so the UI can show the drilldown
     * only where there's something to expand. Shares the cities/regions cache
     * with {@see self::getCountryBreakdown()}, so it costs no extra request.
     *
     * @param string $date A Clicky date expression.
     * @return array<string, bool> A lookup keyed by normalised country name.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getCountriesWithBreakdown(string $date): array
    {
        $stats = $this->getStats(['cities', 'regions'], $date, ['limit' => 100]);

        $countries = [];
        foreach (array_merge($stats['cities'] ?? [], $stats['regions'] ?? []) as $row) {
            $title = (string)($row['title'] ?? '');
            $comma = strrpos($title, ',');
            if ($comma !== false) {
                $countries[$this->_countryKey(substr($title, $comma + 1))] = true;
            }
        }

        return $countries;
    }

    /**
     * Returns a ranked report by its Clicky type, routing to the enriched
     * getTop* method when one exists (flags, logos, consolidation) and falling
     * back to a generic ranked fetch otherwise. Backs the configurable widget.
     *
     * @param string $type The Clicky report type.
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of rows.
     * @return array<int, array> The ranked items.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getReport(string $type, string $date, int $limit = 10): array
    {
        return match ($type) {
            'pages' => $this->getTopPages($date, $limit),
            'traffic-sources' => $this->getTopSources($date, $limit),
            'links-domains' => $this->getTopReferrers($date, $limit),
            'searches-engines' => $this->getTopSearchEngines($date, $limit),
            'countries' => $this->getTopCountries($date, $limit),
            'web-browsers' => $this->getTopBrowsers($date, $limit),
            'operating-systems' => $this->getTopOperatingSystems($date, $limit),
            'hardware' => $this->getTopDevices($date, $limit),
            'pages-entrance' => $this->getEntryPages($date, $limit),
            'pages-exit' => $this->getExitPages($date, $limit),
            'downloads' => $this->getTopDownloads($date, $limit),
            'goals' => $this->getTopGoals($date, $limit),
            'campaigns' => $this->getTopCampaigns($date, $limit),
            'cities' => $this->getTopCities($date, $limit),
            'regions' => $this->getTopRegions($date, $limit),
            'screen-resolutions' => $this->getTopScreenResolutions($date, $limit),
            default => $this->getStats([$type], $date, ['limit' => $limit])[$type] ?? [],
        };
    }

    /**
     * Tests a Site ID / Sitekey pair against the Clicky API, independent of the
     * saved settings; used by the "Test connection" button.
     *
     * @param string $siteId The Clicky Site ID to test.
     * @param string $siteKey The Clicky Sitekey to test.
     * @return array{success: bool, message: string} The test outcome.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function ping(string $siteId, string $siteKey): array
    {
        if ($siteId === '' || $siteKey === '') {
            return ['success' => false, 'message' => Craft::t('clicky-analytics', 'Enter both a Site ID and a Sitekey.')];
        }

        try {
            $response = $this->_client()->get(self::ENDPOINT, [
                'query' => [
                    'site_id' => $siteId,
                    'sitekey' => $siteKey,
                    'type' => 'visitors',
                    'date' => 'today',
                    'output' => 'json',
                    'app' => 'craft-clicky-analytics',
                ],
            ]);
            $data = Json::decodeIfJson((string)$response->getBody());
        } catch (GuzzleException $e) {
            return ['success' => false, 'message' => Craft::t('clicky-analytics', 'Could not reach the Clicky API: {message}', ['message' => $e->getMessage()])];
        }

        if (!is_array($data)) {
            return ['success' => false, 'message' => Craft::t('clicky-analytics', 'Invalid Site ID or Sitekey.')];
        }

        if (isset($data['error'])) {
            return ['success' => false, 'message' => (string)$data['error'] ?: Craft::t('clicky-analytics', 'Invalid Site ID or Sitekey.')];
        }

        return ['success' => true, 'message' => Craft::t('clicky-analytics', 'Connection successful.')];
    }

    /**
     * Returns the most recent individual visitors, normalised for the live feed
     * (location, flag, browser/OS logos, referrer, duration, new vs returning).
     *
     * @param string $date A Clicky date expression.
     * @param int $limit The maximum number of visitors.
     * @return array<int, array> The recent-visitor rows, newest first.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getRecentVisitors(string $date = 'last-7-days', int $limit = 12): array
    {
        $items = $this->getStats(['visitors-list'], $date, ['limit' => $limit])['visitors-list'] ?? [];
        return $this->_normalizeVisitors($items);
    }

    /**
     * Returns analytics for a single page (by its path/href): headline tallies,
     * traffic sources and recent visitors. Backs the page-stats field.
     *
     * @param string $href The page path (e.g. `/blog/my-post`).
     * @param string $date A Clicky date expression.
     * @param int $visitorLimit The maximum recent visitors to include.
     * @return array The page stats payload.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getPageStats(string $href, string $date, int $visitorLimit = 5): array
    {
        $stats = $this->getStats(
            ['visitors', 'visitors-unique', 'actions', 'bounce-rate', 'time-average', 'traffic-sources'],
            $date,
            ['href' => $href, 'limit' => 10]
        );

        $items = $visitorLimit > 0
            ? ($this->getStats(['visitors-list'], $date, ['href' => $href, 'limit' => $visitorLimit])['visitors-list'] ?? [])
            : [];

        return [
            'visitors' => (int)($stats['visitors'][0]['value'] ?? 0),
            'unique' => (int)($stats['visitors-unique'][0]['value'] ?? 0),
            'actions' => (int)($stats['actions'][0]['value'] ?? 0),
            'bounceRate' => (int)round((float)($stats['bounce-rate'][0]['value'] ?? 0)),
            'time' => $this->_prettyTime((int)($stats['time-average'][0]['value'] ?? 0)),
            'sources' => $stats['traffic-sources'] ?? [],
            'visitorsList' => $this->_normalizeVisitors($items),
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the HTTP client to use for Clicky API calls.
     *
     * Returns the injected test client if one was set via {@see setHttpClient()},
     * otherwise a real Guzzle client configured per Craft's conventions.
     *
     * @return Client The HTTP client.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _client(): Client
    {
        return $this->_httpClient ?? Craft::createGuzzleClient();
    }

    /**
     * Normalises raw `visitors-list` items into feed rows (flag, browser/OS
     * logos, referrer, duration, new vs returning).
     *
     * @param array<int, array> $items The raw visitors-list items.
     * @return array<int, array> The normalised visitor rows.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _normalizeVisitors(array $items): array
    {
        $visitors = [];
        foreach ($items as $item) {
            $browser = Browsers::match((string)($item['web_browser'] ?? ''));
            $os = OperatingSystems::match((string)($item['operating_system'] ?? ''));
            $time = (int)($item['time'] ?? 0);
            $landing = self::_safeUrl((string)($item['landing_page'] ?? '')) ?? '';
            $refType = (string)($item['referrer_type'] ?? 'direct');

            $visitors[] = [
                'time' => $time,
                'ago' => $this->_ago($time),
                'geolocation' => (string)($item['geolocation'] ?? '') ?: Craft::t('clicky-analytics', 'Unknown location'),
                'countryCode' => strtolower((string)($item['country_code'] ?? '')),
                'browserLabel' => $browser['label'],
                'browserLogo' => $browser['slug'] !== null ? 'browsers/' . $browser['slug'] : null,
                'osLabel' => $os['label'],
                'osLogo' => $os['slug'] !== null ? 'os/' . $os['slug'] : null,
                'landingPage' => $landing,
                'landingPath' => $landing !== '' ? (parse_url($landing, PHP_URL_PATH) ?: '/') : '/',
                'referrerLabel' => $refType === 'direct'
                    ? Craft::t('clicky-analytics', 'Direct')
                    : ((string)($item['referrer_domain'] ?? '') ?: ucfirst($refType)),
                'referrerType' => $refType,
                'actions' => (int)($item['actions'] ?? 0),
                'duration' => $this->_prettyTime((int)($item['time_total'] ?? 0)),
                'returning' => (int)($item['total_visits'] ?? 1) > 1,
                'statsUrl' => self::_safeUrl((string)($item['stats_url'] ?? '')) ?? '',
            ];
        }

        return $visitors;
    }

    /**
     * Returns the given URL if it has an http(s) scheme, or null otherwise.
     *
     * Referrer, landing-page and stats URLs originate from Clicky's raw API
     * response and are rendered into `href` attributes in the CP. Some of
     * these (referrer/landing page) reflect values an attacker can influence
     * via a crafted `Referer` header on the tracked site, so any scheme other
     * than http/https - `javascript:`, `data:`, etc. - is stripped before the
     * value ever reaches a template.
     *
     * @param string $url The raw URL from the Clicky API.
     * @return string|null The URL if it is a safe http(s) URL, or null.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private static function _safeUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string)$scheme), ['http', 'https'], true) ? $url : null;
    }

    /**
     * Formats a Unix timestamp as a short "time ago" string.
     *
     * @param int $timestamp The Unix timestamp.
     * @return string The relative time (e.g. "5m ago").
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _ago(int $timestamp): string
    {
        $seconds = max(0, time() - $timestamp);

        if ($seconds < 60) {
            return Craft::t('clicky-analytics', 'just now');
        }
        if ($seconds < 3600) {
            return Craft::t('clicky-analytics', '{n}m ago', ['n' => intdiv($seconds, 60)]);
        }
        if ($seconds < 86400) {
            return Craft::t('clicky-analytics', '{n}h ago', ['n' => intdiv($seconds, 3600)]);
        }

        return Craft::t('clicky-analytics', '{n}d ago', ['n' => intdiv($seconds, 86400)]);
    }

    /**
     * Groups ranked rows by a family label, summing visitors and share so related
     * rows (browser/OS versions, a brand's regional search domains) collapse into
     * a single row, ordered by visitors.
     *
     * @param array<int, array> $rows The raw ranked items from Clicky.
     * @param callable(string): string $labelFn Maps a row title to its family label.
     * @return array<int, array> The consolidated, sorted items.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _consolidate(array $rows, callable $labelFn): array
    {
        // Summing value_percent is only sound when the rows are mutually
        // exclusive shares of the one 100% base, which holds for the report
        // types routed through here (browsers, OSes, search engines).
        $grouped = [];

        foreach ($rows as $row) {
            $label = $labelFn((string)($row['title'] ?? ''));

            if (!isset($grouped[$label])) {
                $grouped[$label] = ['title' => $label, 'value' => 0, 'value_percent' => 0.0];
            }

            $grouped[$label]['value'] += (int)($row['value'] ?? 0);
            $grouped[$label]['value_percent'] += (float)($row['value_percent'] ?? 0);
        }

        foreach ($grouped as &$group) {
            $group['value_percent'] = round($group['value_percent'], 1);
        }
        unset($group);

        $grouped = array_values($grouped);
        usort($grouped, static fn(array $a, array $b): int => $b['value'] <=> $a['value']);

        return $grouped;
    }

    /**
     * Filters city/region rows ("Place, Country") to a single country and trims
     * the country suffix from each title. Country names are compared loosely
     * (case-insensitive, leading "the" ignored) to bridge naming differences.
     *
     * @param array<int, array> $rows The city or region rows.
     * @param string $country The target country name.
     * @param int $limit The maximum rows to return.
     * @return array<int, array> The filtered rows, country suffix stripped.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _filterByCountry(array $rows, string $country, int $limit): array
    {
        $target = $this->_countryKey($country);

        $matched = [];
        foreach ($rows as $row) {
            $title = (string)($row['title'] ?? '');
            $comma = strrpos($title, ',');
            if ($comma === false) {
                continue;
            }

            if ($this->_countryKey(substr($title, $comma + 1)) !== $target) {
                continue;
            }

            $row['title'] = trim(substr($title, 0, $comma));
            $matched[] = $row;

            if (count($matched) >= $limit) {
                break;
            }
        }

        return $matched;
    }

    /**
     * Normalises a country name for loose matching (lower-case, trimmed, leading
     * "the" removed) to bridge naming differences across Clicky reports.
     *
     * @param string $name The country name.
     * @return string The normalised key.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _countryKey(string $name): string
    {
        return preg_replace('/^the\s+/', '', strtolower(trim($name))) ?? '';
    }

    /**
     * Builds a metric value with an optional period-over-period trend.
     *
     * @param int $value The current-period value.
     * @param int|null $previous The prior-period value, or null for no comparison.
     * @param bool $higherIsBetter Whether a higher value is a good thing (for colouring).
     * @return array{value: int, trend: int|null, dir: string|null, positive: bool|null} The metric.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _metric(int $value, ?int $previous, bool $higherIsBetter): array
    {
        if ($previous === null || $previous === 0) {
            return ['value' => $value, 'trend' => null, 'dir' => null, 'positive' => null];
        }

        $delta = $value - $previous;
        $dir = $delta >= 0 ? 'up' : 'down';

        return [
            'value' => $value,
            'trend' => (int)round(abs($delta) / $previous * 100),
            'dir' => $dir,
            'positive' => $higherIsBetter ? $delta >= 0 : $delta <= 0,
        ];
    }

    /**
     * Formats a number of seconds as a compact "1m 39s" style duration.
     *
     * @param int $seconds The duration in seconds.
     * @return string The formatted duration.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _prettyTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        return $remainder > 0 ? "{$minutes}m {$remainder}s" : "{$minutes}m";
    }

    /**
     * Performs (and caches) a Clicky API request, returning the decoded array.
     *
     * @param string[] $types The report types to request.
     * @param string $date A Clicky date expression.
     * @param array $params Extra query parameters.
     * @param bool $bypassCache Whether to skip the cache read/write entirely and always fetch fresh.
     * @return array The decoded JSON response.
     * @throws RuntimeException if the API is misconfigured or returns an error.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _request(array $types, string $date, array $params = [], bool $bypassCache = false): array
    {
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $siteId = $settings->getSiteId();
        $siteKey = $settings->getSiteKey();

        if ($siteId === null || $siteKey === null) {
            throw new RuntimeException(Craft::t('clicky-analytics', 'Clicky is not configured. Add your Site ID and Sitekey in the plugin settings.'));
        }

        // A cacheDuration of 0 is documented (settings screen and config.php)
        // as "disable caching". Yii's cache component instead treats a set()
        // duration of 0 as "never expire" - the opposite - so honour the
        // documented behaviour explicitly rather than passing 0 straight through.
        $bypassCache = $bypassCache || $settings->cacheDuration === 0;

        $query = array_merge([
            'site_id' => $siteId,
            'sitekey' => $siteKey,
            'type' => implode(',', $types),
            'date' => $date,
            'output' => 'json',
            'app' => 'craft-clicky-analytics',
        ], $params);

        $cacheKey = 'clicky-analytics:' . md5(Json::encode([$siteId, $types, $date, $params]));
        $cache = Craft::$app->getCache();

        $cached = $bypassCache ? false : $cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $response = $this->_client()->get(self::ENDPOINT, ['query' => $query]);
            $body = (string)$response->getBody();
        } catch (GuzzleException $e) {
            Craft::error('Clicky API request failed: ' . $e->getMessage(), 'clicky-analytics');
            throw new RuntimeException(Craft::t('clicky-analytics', 'Could not reach the Clicky API: {message}', ['message' => $e->getMessage()]), 0, $e);
        }

        $data = Json::decodeIfJson($body);

        // Clicky surfaces errors either as a plain string or an {error: …} object.
        if (!is_array($data)) {
            Craft::error('Unexpected Clicky API response: ' . $body, 'clicky-analytics');
            throw new RuntimeException(Craft::t('clicky-analytics', 'The Clicky API returned an unexpected response. Check your Site ID and Sitekey.'));
        }

        if (isset($data['error'])) {
            Craft::error('Clicky API error: ' . (string)$data['error'], 'clicky-analytics');
            throw new RuntimeException(Craft::t('clicky-analytics', 'Clicky API error: {message}', ['message' => (string)$data['error']]));
        }

        if (!$bypassCache) {
            $cache->set($cacheKey, $data, $settings->cacheDuration);
        }

        return $data;
    }
}
