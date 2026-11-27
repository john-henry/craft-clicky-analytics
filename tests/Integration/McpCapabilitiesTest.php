<?php

/**
 * Coverage for the MCP tools in mcp\Capabilities.
 *
 * The tools are called directly, as Craft's MCP server calls them once it has
 * checked the attributes on each method, so the attributes themselves are
 * pinned separately by reflection. Clicky is mocked with a handler that answers
 * whatever report types a request names, since one tool call can make several
 * requests.
 */

use craft\mcp\attributes\RequiresPermission;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\mcp\Capabilities;
use markhuot\craftpest\factories\User as UserFactory;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Psr\Http\Message\RequestInterface;

/**
 * Returns mock responses that answer each requested report type, or segment,
 * with three ranked rows carrying the extra fields Clicky and the widgets add.
 *
 * @return array<int, callable>
 */
function clickyMcpResponses(int $count = 6): array
{
    $respond = static function (RequestInterface $request): Response {
        parse_str($request->getUri()->getQuery(), $query);
        $types = explode(',', $query['segments'] ?? $query['type']);

        $items = [
            ['title' => 'Home', 'value' => '120', 'value_percent' => '60.5', 'url' => 'https://example.com/', 'stats_url' => 'https://clicky.com/stats/1', 'ip_address' => '192.0.2.1'],
            ['title' => 'Who we&amp;apos;re &amp; why', 'value' => '45', 'value_percent' => '22.5', 'url' => 'https://example.com/about'],
            ['title' => 'Contact', 'value' => '12', 'value_percent' => '6'],
        ];

        return new Response(200, [], json_encode(array_map(
            static fn (string $type): array => ['type' => $type, 'dates' => [['date' => '2026-11-06,2026-11-12', 'items' => $items]]],
            $types,
        )));
    };

    return array_fill(0, $count, $respond);
}

// ---------------------------------------------------------------------------
// Registration and access
// ---------------------------------------------------------------------------

describe('MCP capability registration', function () {
    it('offers the three tools to Craft\'s capability registry', function () {
        $registry = Craft::$app->get('mcp')->getCapabilityRegistry();

        expect($registry->types())->toContain(Capabilities::class);

        $names = array_map(static fn ($reference) => $reference->tool->name, $registry->definitions()->getTools());

        expect(array_values($names))->toContain('clicky_analytics_overview', 'clicky_analytics_report', 'clicky_analytics_page_stats');
    });

    it('marks every tool read-only and behind the view permission', function () {
        foreach (['overview', 'report', 'pageStats'] as $method) {
            $reflection = new ReflectionMethod(Capabilities::class, $method);
            $tool = $reflection->getAttributes(McpTool::class)[0]->newInstance();
            $permissions = array_map(
                static fn (ReflectionAttribute $attribute) => $attribute->newInstance()->permission,
                $reflection->getAttributes(RequiresPermission::class),
            );

            expect($tool->annotations?->readOnlyHint)->toBeTrue()
                ->and($tool->name)->toStartWith('clicky_analytics_')
                ->and($permissions)->toBe([ClickyAnalytics::PERMISSION_VIEW_ANALYTICS]);
        }
    });

    it('names the same permission in the attribute Craft 6 reads', function () {
        // Craft 6 offers a tool to every user unless it carries Craft 6's own
        // attribute class, which doesn't exist here, so it's read by name.
        foreach (['overview', 'report', 'pageStats'] as $method) {
            $arguments = [];

            foreach ((new ReflectionMethod(Capabilities::class, $method))->getAttributes() as $attribute) {
                if ($attribute->getName() === 'CraftCms\Cms\Mcp\Attributes\RequiresPermission') {
                    $arguments[] = $attribute->getArguments();
                }
            }

            expect($arguments)->toBe([[ClickyAnalytics::PERMISSION_VIEW_ANALYTICS]]);
        }
    });
});

// ---------------------------------------------------------------------------
// Overview
// ---------------------------------------------------------------------------

describe('clicky_analytics_overview', function () {
    it('returns the headline metrics and the visitors online', function () {
        clickyApiWithMock(clickyMcpResponses());

        $result = (new Capabilities())->overview('last-7-days');

        expect($result['dateRange'])->toBe('last-7-days')
            ->and($result['metrics'])->toHaveKeys(['visitors', 'actions', 'bounceRate', 'time'])
            ->and($result['metrics']['visitors']['value'])->toBe(120)
            ->and($result['visitorsOnline'])->toBe(120);
    });

    it('rejects a date range that is not one of the plugin\'s keys', function () {
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->overview('2026-01-01,2026-12-31');
    })->throws(ToolCallException::class, 'Unknown date range');

    it('rejects a site handle that does not exist', function () {
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->overview('today', 'no-such-site');
    })->throws(ToolCallException::class, 'Unknown site handle');

    it('says so when Clicky is not configured', function () {
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $settings->siteId = null;
        $settings->siteKey = null;

        (new Capabilities())->overview();
    })->throws(ToolCallException::class, 'no Site ID and Sitekey');

    it('keeps the failure detail, and the sitekey, out of the tool error', function () {
        clickyApiWithMock([new Response(500, [], 'boom')]);

        try {
            (new Capabilities())->overview();
        } catch (ToolCallException $e) {
            expect($e->getMessage())->toBe('Clicky data could not be loaded. Check the Craft logs for details.');

            return;
        }

        $this->fail('The tool did not report the failed request.');
    });
});

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------

describe('clicky_analytics_report', function () {
    it('returns ranked rows with only the fields an agent can use', function () {
        clickyApiWithMock(clickyMcpResponses());

        $result = (new Capabilities())->report('pages', 'last-28-days', 3);

        expect($result['type'])->toBe('pages')
            ->and($result['rows'])->toHaveCount(3)
            ->and($result['rows'][0])->toBe(['title' => 'Home', 'value' => 120, 'percent' => 60.5, 'url' => 'https://example.com/'])
            ->and($result['rows'][1]['title'])->toBe('Who we\'re & why')
            ->and($result['rows'][2])->toBe(['title' => 'Contact', 'value' => 12, 'percent' => 6.0, 'url' => null]);
    });

    it('caps the row limit', function () {
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->report('downloads', 'today', 5000);

        expect(clickyRequestQueries()[0]['limit'])->toBe((string)Capabilities::MAX_LIMIT);
    });

    it('rejects a report type outside its list, visitors-list included', function () {
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->report('visitors-list');
    })->throws(ToolCallException::class, 'Unknown report type');

    it('accepts every report type it advertises', function () {
        foreach (Capabilities::REPORT_TYPES as $type) {
            clickyApiWithMock(clickyMcpResponses());

            expect((new Capabilities())->report($type)['type'])->toBe($type);
        }
    });
});

// ---------------------------------------------------------------------------
// Page stats
// ---------------------------------------------------------------------------

describe('clicky_analytics_page_stats', function () {
    it('returns a page\'s figures by URL without any visitor rows', function () {
        clickyApiWithMock(clickyMcpResponses());

        $result = (new Capabilities())->pageStats(url: 'https://example.com/about?ref=x');

        expect($result['path'])->toBe('/about')
            ->and($result)->toHaveKeys(['visitors', 'unique', 'actions', 'bounceRate', 'time', 'sources'])
            ->and($result)->not->toHaveKey('visitorsList')
            ->and($result['sources'][0])->toHaveKeys(['title', 'value', 'percent', 'url']);

        $requested = implode(',', array_map(static fn (array $query): string => $query['type'], clickyRequestQueries()));

        expect($requested)->not->toContain('visitors-list');
    });

    it('needs exactly one of an entry ID and a URL', function (?int $entryId, ?string $url) {
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->pageStats($entryId, $url);
    })->with([
        'neither' => [null, null],
        'both' => [1, '/about'],
        'an empty URL' => [null, ''],
    ])->throws(ToolCallException::class, 'Provide exactly one of');

    it('reports an entry that does not exist as not found', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());
        clickyApiWithMock(clickyMcpResponses());

        (new Capabilities())->pageStats(entryId: 999999999);
    })->throws(ToolCallException::class, 'Entry not found');
});
