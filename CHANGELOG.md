# Release Notes for Clicky Analytics

## 1.2.0 - 2026-11-27

### Added
- Craft CMS 6 support. Craft 6 needs PHP 8.5, and your project has to require the `craftcms/yii2-adapter` package itself.
- Three read-only tools for AI agents on Craft's MCP server, on Craft 5.12 and later and on Craft 6: `clicky_analytics_overview`, `clicky_analytics_report` and `clicky_analytics_page_stats`.
- Live Visitors marks the visitors who are online now with a green dot.

### Changed
- Line charts in widgets three or more columns wide are shorter.
- Live Visitors is cached for 30 seconds rather than the cache duration, so it keeps up with Visitors Online.

### Fixed
- Ad blockers no longer block the plugin's control panel script, which stopped the country drilldown in Top Countries from opening.
- Page titles with an apostrophe no longer show `&apos;` in the widgets.
- The New and Returning badges in Live Visitors are no longer cut off after a long location.
- The settings screen no longer logs a deprecation warning on Craft 6.
- Screen readers now announce links that open in a new tab in the widgets, the Page Stats field and the settings page.
- Screen readers now announce when a country's cities and regions have loaded in Top Countries, or that they couldn't be loaded.
- The Open in Clicky link in the widgets now shows on touch screens.
- The changelog link for the plugin in the control panel now works.

## 1.1.0 - 2026-09-25 [CRITICAL]

> [!IMPORTANT]
> If your Sitekey is in a variable named `CLICKY_SITEKEY`, Craft has been writing it to your logs. Generate a new Sitekey in Clicky, rename the variable to `CLICKY_SITE_KEY`, and delete your old logs in `storage/logs`.

### Security
- Craft now masks the Clicky Sitekey in logged request details and the control panel's variable suggestions.
- Errors from Clicky no longer carry the Sitekey.
- The shipped config file refers to the Sitekey by variable, so its value no longer appears on the settings screen.
- Clicky widgets, the Page Stats field and the country drilldown now need the view permission, however the widget was added.
- Test connection only resolves the saved variables or ones named `CLICKY_`.
- Visitors' IP addresses, hostnames and coordinates are no longer cached.
- Links to referrers and landing pages now open with `noreferrer`.

### Added
- Give each Craft site its own Clicky Site ID and Sitekey with the `sites` config setting.
- Dashboard widgets have a Clicky site setting, for reporting on a site with its own Site ID and Sitekey.
- `craft.clickyAnalytics.pageStats()` accepts an entry or a full URL.

### Changed
- The "Add Clicky Analytics dashboard widgets" permission is now "View Clicky Analytics data", `clicky-analytics:view-analytics`. Existing grants are moved over.
- Date ranges now follow your Clicky site's time zone rather than Craft's.
- Calendar months now cover the whole month.
- The Page Stats field defaults to Last 28 days.
- Visitors online is cached for 30 seconds rather than fetched on every page.
- Requests to Clicky time out after 5 seconds, unless `config/guzzle.php` sets a timeout.
- After a failed request, calls to Clicky pause for a minute.
- Report widgets are titled with their metric alone.
- Uninstalling the plugin removes its dashboard widgets.

### Fixed
- The Page Stats field no longer shows up in GraphQL schemas, where it could only ever return null.
- A wrong Sitekey no longer passes Test connection.
- A Clicky outage no longer breaks front-end pages that use `craft.clickyAnalytics`.
- The Page Stats field can no longer be made required, which stopped every save.
- The Page Stats field skips unpublished drafts.
- Pages with non-ASCII characters in their URL now match their Clicky stats.
- Two line charts on one dashboard no longer share a colour.
- Line charts in one-column widgets have readable axis labels.
- "1 visitors online now" and "1 views" now read correctly.
- Rows without a drilldown in Top Countries line up with the rest.
- Screen readers now hear which country a drilldown button opens.
- The drilldown's loading and error messages can now be translated.
- Invalid Site IDs, date ranges, colour schemes and Report widget options are now rejected.
- Modules can now add validation rules to the widgets.
- The "Open in Clicky" links now show when they have keyboard focus, not just on hover.
- The visitors chart now has a text version for screen readers.
- The country drilldown toggle now meets the minimum touch target size.
- The live visitors pulse no longer animates when reduced motion is requested.

## 1.0.2 - 2026-09-22 [CRITICAL]

### Security
- Failed Clicky requests no longer write your Sitekey to the logs or show it on screen. If it may have been logged, generate a new one in Clicky.

### Fixed
- The noscript setting now says it only applies while tracking code is injected.
- Site IDs and Sitekeys from environment variables are now trimmed.
- Modules can now add validation rules to the plugin settings.

## 1.0.1 - 2026-07-08

### Fixed
- The Page Stats field now shows each entry's own figures, not site-wide ones. Clicky keeps per-page data for about 30 days.

## 1.0.0 - 2026-07-05

### Added
- 21 dashboard widgets, each with its own date range and row limit.
- A Report widget that draws any metric as a counter, line, bar or pie.
- A Live Visitors widget with a new, returning or both filter.
- A cities and regions drilldown on the Top Countries widget.
- An option to merge browser and OS versions into one row.
- A Page Stats field that shows an entry's own analytics in the editor.
- Self-hosted flags and browser, OS and search engine logos.
- Five colour schemes, a compact density and a slim bar style.
- Optional tracking code injection, skipped in `devMode` and previews.
- A settings screen with Test connection, and a `config/clicky-analytics.php` config file.
- "Open in Clicky" links on every ranked row.
- A `craft.clickyAnalytics` Twig variable.
- A permission for adding Clicky widgets.
- Response caching with a duration you set.
