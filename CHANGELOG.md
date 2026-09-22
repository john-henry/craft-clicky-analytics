# Release Notes for Clicky Analytics

## 1.0.2 - 2026-09-22 [CRITICAL]

### Security
- Your Clicky Sitekey no longer ends up in the log file or on screen. The sitekey goes to Clicky as part
  of the web address on every request, and when a request came back with an error, the message that came
  with it carried that whole address. It was written to `storage/logs/web.log` as it stood and, if you
  pressed Test connection on the settings screen, printed straight back into the page. Log files get
  mailed about, attached to support tickets and swept up in backups, so treat a sitekey that was in one
  as known and roll it over in your Clicky account. The key is masked now wherever that message goes.

### Fixed
- The Track visitors with JavaScript disabled setting now says that it only applies while the tracking
  code is being injected. It always did, but nothing on the screen said so.
- A Site ID or Sitekey set through an environment variable is now trimmed before it is used. A stray
  space or newline at the end of the value, which is easy to leave in a `.env` file, went straight into
  the Clicky API request and came back as a failure with nothing obvious to point at.
- The plugin's settings now fire Craft's `defineRules` event, so a module can add its own validation to
  them. The settings model was declaring its rules in a way that skipped the event, so a handler you
  attached to it ran against nothing and your rule was never applied.

## 1.0.1 - 2026-07-08

### Fixed
- The Page Stats field was showing the same site-wide numbers on every entry, so a brand new page
  with no traffic still looked like it had hundreds of visitors. Each entry now shows the figures for
  its own page. One thing to know: Clicky only keeps the detailed, per-page data for around the last
  30 days, so these page figures cover that recent window rather than the full range in the heading.

## 1.0.0 - 2026-07-05

First release. Everything below is here from the off.

### Added
- 21 native dashboard widgets you can mix and match: Report (configurable), Live Visitors, Current
  Visitors, Overview, New vs Returning, Top Pages, Top Sources, Top Referrals, Search Engines, Top
  Countries, Top Browsers, Operating Systems, Devices, Entry Pages, Exit Pages, Downloads, Goals,
  Campaigns, Cities, Regions and Screen Resolutions. Each has its own date range and row limit.
- A configurable Report widget: pick any single metric and draw it as a Counter, Line, Bar or Pie.
- A Live Visitors widget with a feed of recent visits, each row showing the country flag, location,
  landing page, browser and OS logos, referrer and dwell time, with a new / returning / both filter.
- A country drilldown on the Top Countries widget: expand a country to load its cities and regions
  inline, toggleable per widget.
- A "consolidate versions" toggle on the Browsers and Operating Systems widgets, so you get one
  "Google Chrome" row instead of one per version.
- A Clicky Analytics Page Stats field: drop it onto an entry type's field layout and each entry shows
  its own analytics (visitors, unique, actions, bounce, average time, traffic sources and recent
  visitors) right in the editor. It stores nothing; the figures are worked out live from the entry's
  URL.
- Self-hosted SVG country flags, browser logos, OS logos and search-engine logos, so the rows look
  the same on every operating system with no call-out to an external CDN.
- Five colour schemes, Clicky Analytics (warm), Ocean (blue), Monochrome, Forest (green) and Berry
  (purple), that re-theme every widget, the field and the charts from the one setting.
- Compact density and slim bar style options, for when you want the widgets and the field to sit
  tighter.
- Optional front-end tracking code injection: the plugin adds the Clicky snippet (with the
  `<noscript>` pixel fallback) just before `</body>` on every front-end page, using your Site ID.
  It's skipped automatically when `devMode` is on and on Live Preview or share-token requests, so
  local development and draft previews never end up in your live stats.
- A settings screen for your Site ID and Sitekey (both take environment variables) with a Test
  connection button, plus default date range, cache duration and colour scheme.
- A `config/clicky-analytics.php` config file (copy it from `src/config.php`) to manage any setting
  in code. Anything set there overrides the control-panel value and turns that field read-only.
- "Open in Clicky" deep links on every ranked row, so you can jump straight to the full report.
- A `craft.clickyAnalytics` Twig variable for reading the same figures in your own templates.
- An "Add Clicky Analytics dashboard widgets" user permission for non-admins.
- Per-report response caching with a duration you set (or 0 to turn it off).
