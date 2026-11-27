<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\mcp;

use Craft;
use craft\elements\Entry;
use craft\mcp\attributes\RequiresPermission;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission as Craft6RequiresPermission;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\services\Api;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use RuntimeException;

/**
 * Clicky Analytics MCP capabilities.
 *
 * The tools Craft's MCP servers offer to agents: the headline figures, a ranked
 * report and one page's stats. Each is read-only, needs the same permission as
 * the dashboard widgets, and returns aggregates only, never individual visitors.
 *
 * Only loaded on Craft versions that ship MCP, since the attributes it carries
 * come from Craft's MCP SDK. Each tool names its permission twice, because
 * Craft 5 and Craft 6 each read their own attribute class and ignore the other.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.2.0
 */
class Capabilities
{
    // Const Properties
    // =========================================================================

    /**
     * @var string[] The report types the report tool accepts.
     */
    public const REPORT_TYPES = [
        'pages',
        'pages-entrance',
        'pages-exit',
        'traffic-sources',
        'links-domains',
        'searches-engines',
        'countries',
        'regions',
        'cities',
        'web-browsers',
        'operating-systems',
        'hardware',
        'screen-resolutions',
        'downloads',
        'goals',
        'campaigns',
    ];

    /**
     * @var int The most rows the report tool returns.
     */
    public const MAX_LIMIT = 50;

    /**
     * @var string What the date range argument accepts, for the tool schemas.
     */
    private const DATE_RANGE_DESCRIPTION = 'One of today, yesterday, 2-days-ago, last-7-days, last-14-days, last-28-days, last-60-days, last-90-days, or a calendar month as YYYY-MM.';

    /**
     * @var string What the site argument accepts, for the tool schemas.
     */
    private const SITE_DESCRIPTION = 'A Craft site handle, for a site with its own Clicky Site ID. Leave out for the default.';

    // Public Methods
    // =========================================================================

    /**
     * Returns the headline figures for a date range, each with its change
     * against the period before, plus the visitors online now.
     *
     * @param string $dateRange The date range key.
     * @param string|null $site The Craft site handle, or null for the default credentials.
     * @return array<string, mixed> The overview payload.
     * @throws ToolCallException if an argument is invalid, Clicky isn't configured or the request fails.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    #[McpTool(
        name: 'clicky_analytics_overview',
        description: 'Returns site-wide Clicky web analytics for a date range: visitors, actions, bounce rate and average time per visit, each with its change against the previous period, plus the number of visitors online now.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    #[Craft6RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    public function overview(
        #[Schema(description: self::DATE_RANGE_DESCRIPTION)]
        string $dateRange = DateRanges::DEFAULT,
        #[Schema(description: self::SITE_DESCRIPTION)]
        ?string $site = null,
    ): array {
        $range = $this->_range($dateRange);
        $api = $this->_api($site);

        return $this->_fetch(static fn(): array => [
            'dateRange' => $dateRange,
            'period' => $range['label'],
            'metrics' => $api->getOverview($range['date'], $range['previous']),
            'visitorsOnline' => $api->getVisitorsOnline(),
        ]);
    }

    /**
     * Returns the ranked rows of one report type for a date range.
     *
     * @param string $type The report type, one of {@see self::REPORT_TYPES}.
     * @param string $dateRange The date range key.
     * @param int $limit The maximum number of rows.
     * @param string|null $site The Craft site handle, or null for the default credentials.
     * @return array<string, mixed> The report payload.
     * @throws ToolCallException if an argument is invalid, Clicky isn't configured or the request fails.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    #[McpTool(
        name: 'clicky_analytics_report',
        description: 'Returns a ranked Clicky web analytics report for a date range, most visited first. Report types: pages, pages-entrance, pages-exit, traffic-sources, links-domains (referring sites), searches-engines, countries, regions, cities, web-browsers, operating-systems, hardware (devices), screen-resolutions, downloads, goals, campaigns.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    #[Craft6RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    public function report(
        #[Schema(enum: self::REPORT_TYPES)]
        string $type,
        #[Schema(description: self::DATE_RANGE_DESCRIPTION)]
        string $dateRange = DateRanges::DEFAULT,
        #[Schema(minimum: 1, maximum: self::MAX_LIMIT)]
        int $limit = 10,
        #[Schema(description: self::SITE_DESCRIPTION)]
        ?string $site = null,
    ): array {
        if (!in_array($type, self::REPORT_TYPES, true)) {
            throw new ToolCallException('Unknown report type. Use one of: ' . implode(', ', self::REPORT_TYPES) . '.');
        }

        $range = $this->_range($dateRange);
        $api = $this->_api($site);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        return $this->_fetch(fn(): array => [
            'type' => $type,
            'dateRange' => $dateRange,
            'period' => $range['label'],
            'rows' => $this->_rows($api->getReport($type, $range['date'], $limit)),
        ]);
    }

    /**
     * Returns one page's figures for a date range, by entry or by URL.
     *
     * @param int|null $entryId The ID of the entry whose page to report on.
     * @param string|null $url The page URL or path, when not reporting on an entry.
     * @param string $dateRange The date range key.
     * @param string|null $site The Craft site handle, or null for the default site and credentials.
     * @return array<string, mixed> The page stats payload.
     * @throws ToolCallException if an argument is invalid, the entry can't be viewed, Clicky isn't
     * configured or the request fails.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    #[McpTool(
        name: 'clicky_analytics_page_stats',
        description: 'Returns Clicky web analytics for a single page over a date range: visitors, unique visitors, actions, bounce rate, average time and traffic sources. Give either a Craft entry ID or a page URL or path. Clicky keeps per-page data for about 30 days.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    #[Craft6RequiresPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)]
    public function pageStats(
        #[Schema(description: 'The ID of the Craft entry whose page to report on.')]
        ?int $entryId = null,
        #[Schema(description: 'The page URL or path, such as /blog/my-post. Use when not reporting on an entry.')]
        ?string $url = null,
        #[Schema(description: self::DATE_RANGE_DESCRIPTION)]
        string $dateRange = 'last-28-days',
        #[Schema(description: self::SITE_DESCRIPTION)]
        ?string $site = null,
    ): array {
        if (($entryId === null) === ($url === null || $url === '')) {
            throw new ToolCallException('Provide exactly one of: entryId, url.');
        }

        $range = $this->_range($dateRange);
        $api = $this->_api($site);

        if ($entryId !== null) {
            $entry = $this->_entry($entryId, $site);
            $url = (string)$entry->getUrl();

            if ($url === '') {
                throw new ToolCallException('That entry has no URL, so it has no page stats.');
            }

            if ($site === null) {
                $api = $this->_api($entry->getSite()->handle);
            }
        }

        $path = Api::pathFromUrl((string)$url);

        return $this->_fetch(function() use ($api, $path, $range, $dateRange): array {
            $stats = $api->getPageStats($path, $range['date'], 0);
            unset($stats['visitorsList']);
            $stats['sources'] = $this->_rows($stats['sources']);

            return ['path' => $path, 'dateRange' => $dateRange, 'period' => $range['label']] + $stats;
        });
    }

    // Private Methods
    // =========================================================================

    /**
     * Resolves a date range key, refusing anything that isn't one. Clicky
     * treats a date it doesn't recognise as today without saying so.
     *
     * @param string $dateRange The date range key.
     * @return array{date: string, previous: string, label: string} The resolved range.
     * @throws ToolCallException if the key isn't recognised.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _range(string $dateRange): array
    {
        if (!DateRanges::isValid($dateRange)) {
            throw new ToolCallException('Unknown date range. ' . self::DATE_RANGE_DESCRIPTION);
        }

        return DateRanges::resolve($dateRange);
    }

    /**
     * Returns the API service for a site handle, once it's known to be a real
     * site with Clicky credentials.
     *
     * @param string|null $site The Craft site handle, or null for the default credentials.
     * @return Api The API service.
     * @throws ToolCallException if the site doesn't exist or Clicky isn't configured.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _api(?string $site): Api
    {
        if ($site !== null && Craft::$app->getSites()->getSiteByHandle($site) === null) {
            throw new ToolCallException('Unknown site handle.');
        }

        $api = ClickyAnalytics::getInstance()->getApi()->forSite($site);

        if (!$api->isConfigured()) {
            throw new ToolCallException('Clicky Analytics has no Site ID and Sitekey configured.');
        }

        return $api;
    }

    /**
     * Returns an entry the current user is allowed to view.
     *
     * @param int $entryId The entry ID.
     * @param string|null $site The Craft site handle, or null for the current site.
     * @return Entry The entry.
     * @throws ToolCallException if there's no such entry or the user can't view it.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _entry(int $entryId, ?string $site): Entry
    {
        $siteId = $site !== null ? Craft::$app->getSites()->getSiteByHandle($site)?->id : null;
        $entry = Craft::$app->getElements()->getElementById($entryId, Entry::class, $siteId);
        $user = Craft::$app->getUser()->getIdentity();

        // One message for both cases, so the tool doesn't confirm that an entry
        // the user can't see exists.
        if (!$entry instanceof Entry || $user === null || !$entry->canView($user)) {
            throw new ToolCallException('Entry not found.');
        }

        return $entry;
    }

    /**
     * Runs a Clicky fetch, turning a failure into a tool error. The detail goes
     * to the log rather than the agent.
     *
     * @param callable(): array<string, mixed> $fetch The fetch to run.
     * @return array<string, mixed> What the fetch returned.
     * @throws ToolCallException if the request fails.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _fetch(callable $fetch): array
    {
        try {
            return $fetch();
        } catch (RuntimeException $e) {
            Craft::error('Clicky MCP tool failed: ' . $e->getMessage(), 'clicky-analytics');

            throw new ToolCallException('Clicky data could not be loaded. Check the Craft logs for details.');
        }
    }

    /**
     * Reduces ranked items to the fields an agent can use, dropping the logo,
     * flag and drilldown data the widgets carry.
     *
     * @param array<int, array<string, mixed>> $items The ranked items.
     * @return array<int, array{title: string, value: int, percent: float|null, url: string|null}> The rows.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _rows(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'title' => (string)($item['title'] ?? ''),
                'value' => (int)($item['value'] ?? 0),
                'percent' => isset($item['value_percent']) ? (float)$item['value_percent'] : null,
                'url' => isset($item['url']) && $item['url'] !== '' ? (string)$item['url'] : null,
            ];
        }

        return $rows;
    }
}
