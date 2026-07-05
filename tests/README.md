# Clicky Analytics Tests

Tests use [Pest](https://pestphp.com/) on top of [`markhuot/craft-pest-core`](https://github.com/markhuot/craft-pest). Pest lives in the parent Craft project's `vendor/`; the plugin has no `vendor/` of its own, so all commands run from the project root.

## Layout

- `Feature/`: pure PHP tests with no Craft application, covering the Clicky → UI mapping helpers and the settings model's validation rules.
- `Integration/`: tests that need a real Craft application (the site time zone, `Craft::t()`, `App::parseEnv()`, or the plugin settings singleton). Each test is wrapped in a DB transaction via `RefreshesDatabase` and rolled back on teardown.

```
tests/
├── Pest.php                          # Bootstrap: applies TestCase to Integration/
├── Feature/
│   ├── BrowsersTest.php              # Browsers::match() family + slug + precedence + fallback
│   ├── DevicesTest.php               # Devices::icon() phone/tablet/desktop mapping
│   ├── OperatingSystemsTest.php      # OperatingSystems::match() family precedence
│   ├── SearchEnginesTest.php         # SearchEngines::match() brand + regional domains
│   ├── FlagsTest.php                 # Flags::code() ISO mapping, "the" stripping, aliases
│   ├── SettingsTest.php              # Settings defaults + required credentials + cacheDuration
│   ├── ApiHelpersTest.php            # Api private helpers via reflection: metric/prettyTime/countryKey/consolidate/filterByCountry
│   └── TrackingCodeTest.php          # TrackingCode::html(): script tag, HTML encoding, noscript fallback
└── Integration/
    ├── DateRangesTest.php            # isValid/normalize/resolve: bounds, custom swap/cap, previous period
    ├── PaletteTest.php               # schemes/swatches + per-scheme primary/accent/pie + invalid fallback
    ├── SettingsEnvTest.php           # getSiteId()/getSiteKey() literal + $ENV_VAR resolution
    ├── WidgetSettingsTest.php        # inherited widget settings (dateRange, limit) serialise + round-trip
    ├── ApiTest.php                   # public Api methods via cache-seeded (no-HTTP) responses
    └── ApiHttpErrorsTest.php         # _request()/ping() over a mocked Guzzle client: success + failure paths
```

## Running

From the parent Craft project (not from inside the plugin folder):

```bash
ddev exec vendor/bin/pest plugins/craft-clicky-analytics/tests \
  --test-directory=plugins/craft-clicky-analytics/tests
```

To run a single suite:

```bash
ddev exec vendor/bin/pest plugins/craft-clicky-analytics/tests/Feature
ddev exec vendor/bin/pest plugins/craft-clicky-analytics/tests/Integration
```

To filter to a specific test file:

```bash
ddev exec vendor/bin/pest plugins/craft-clicky-analytics/tests \
  --test-directory=plugins/craft-clicky-analytics/tests --filter=DateRanges
```

## Adding tests

- Pure mapper/enum/helper and model-validation tests belong in `Feature/`; they run faster and need no DB.
- Anything that touches `Craft::$app`, `Craft::t()`, `App::parseEnv()`, or the plugin settings belongs in `Integration/`.

### Notes

- **Active colour scheme.** `Palette` reads the scheme from the plugin settings
  singleton, which holds the environment's configured value (not necessarily the
  `clicky` default). `getSettings()` is memoised, so mutating `colorScheme` within a
  test sticks, so each `PaletteTest` case sets the scheme it needs explicitly via the
  `useScheme()` helper rather than assuming a default.
- **DateRanges.** Expected dates are computed against the same site time zone the
  helper reads (`siteDay()` in `DateRangesTest.php`), so assertions stay correct as
  the clock advances.

### Testing the Api two ways

The `Api` service is driven from both sides of its HTTP call:

- **Cache-seeded, no network (`ApiTest.php`).** `Api::_request()` checks
  `Craft::$app->getCache()` and returns a cached response *before* it ever reaches the
  Guzzle client, so most tests just seed the cache:
  1. `clickyApi()` swaps in a fresh `ArrayCache` and sets known credentials.
  2. `seedClicky($types, $date, $params, $blocks)` writes a synthetic decoded response
     under the exact key `_request()` computes:
     `'clicky-analytics:' . md5(Json::encode([$siteId, $types, $date, $params]))`.
  3. The public method runs its real parsing/consolidation/metric logic against the
     seeded payload, never touching the network.

  This drives `getStats`, `getOverview`, `getTopBrowsers`, `getTopCountries`,
  `getDailySeries` and friends end to end.

- **Mocked HTTP client (`ApiHttpErrorsTest.php`).** `Api::setHttpClient()` injects a
  Guzzle client backed by a `MockHandler`, so the real request/response/error-parsing
  in `_request()` and `ping()` runs against synthetic HTTP responses, including the
  failure paths (timeouts, non-JSON bodies, Clicky `{error: …}` payloads) the
  cache-seeding approach can't reach.

The pure private transformation helpers are additionally pinned directly via
reflection in `Feature/ApiHelpersTest.php`.

## What's not covered

The dashboard widgets' rendering, the `PageStats` field, and the controllers depend on
a CP/HTTP request context and are left to manual QA (the service logic behind them is
tested directly). The same applies to `ClickyAnalytics::_registerTrackingCode()`'s
gating (devMode, site-request, Live Preview): only the pure `TrackingCode::html()`
markup builder it calls is unit-tested; the gating itself needs a full Craft request
context to exercise.

## Static Analysis

```bash
ddev composer phpstan --working-dir=plugins/craft-clicky-analytics
```

## Coding Standards

```bash
ddev composer check-cs --working-dir=plugins/craft-clicky-analytics
ddev composer fix-cs --working-dir=plugins/craft-clicky-analytics
```
