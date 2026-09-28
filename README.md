[![Stable Version](https://img.shields.io/packagist/v/johnhenry/craft-clicky-analytics?label=stable&style=for-the-badge)](https://packagist.org/packages/johnhenry/craft-clicky-analytics)
[![Static Badge](https://img.shields.io/badge/free-plugin?style=for-the-badge&logo=craftcms&logoColor=white&logoSize=auto&label=Craft%20Plugin%20Store&labelColor=%23E5422B)](https://plugins.craftcms.com/clicky-analytics?craft5)

![Clicky Analytics for Craft CMS](https://johnhenry.ie/images/plugins/promos/clicky-analytics/1.png)

# Clicky Analytics for Craft CMS

Your [Clicky](https://clicky.com) web analytics in the Craft CMS 5 control panel. When someone asks how
a page is doing, open the entry and its visitors and sources are right there, and the dashboard
widgets show the whole site, all read from the [Clicky API](https://clicky.com/help/api).

## Features

- **21 dashboard widgets:** build your own Craft dashboard from Report (configurable), Live
  Visitors, Current Visitors, Overview, New vs Returning, Top Pages, Top Sources, Top Referrals,
  Search Engines, Top Countries, Top Browsers, Operating Systems, Devices, Entry Pages, Exit Pages,
  Downloads, Goals, Campaigns, Cities, Regions and Screen Resolutions.
- **Configurable Report widget:** choose any metric and how to draw it, Counter, Line, Bar or Pie.
- **Live Visitors widget:** recent visits with country flag, location, landing page, browser & OS
  logos, referrer and dwell time, with a new / returning / both filter.
- **Country drilldown:** expand a country in the Top Countries widget to load its cities and regions
  inline.
- **Page Stats field:** add the “Clicky Analytics Page Stats” field to an entry type's field layout to show
  that entry's own analytics (visitors, traffic sources, recent visitors) inside the entry editor.
- **Per-widget date ranges:** Today, Yesterday, 2 days ago, Last 7/14/28/60/90 days and recent months.
- **Colour schemes:** re-theme every widget, the field and the charts at once, choosing between Clicky
  Analytics (warm), Ocean (blue), Monochrome, Forest (green) or Berry (purple) in the plugin settings.
- **Layout options:** a compact density and a slim bar style, for when you'd rather the widgets and
  the field sat tighter than the comfortable default.
- **Brand logos & flags:** self-hosted SVG country flags, browser logos, OS logos and search-engine
  logos that render identically on every OS.
- **Version consolidation:** optionally merge browser and OS versions (e.g. one “Google Chrome” row
  instead of one per version), per widget.
- **Open in Clicky:** every row deep-links to the matching report in Clicky.
- **Tracking code injection:** let the plugin add the Clicky tracking
  snippet (with `<noscript>` fallback) to every front-end page for you, so there's
  no need to paste it into your layout by hand. Skipped automatically when `devMode` is on.
- **Twig variable:** read any of the same data in your own templates via `craft.clickyAnalytics`. If
  Clicky can't be reached it hands back empty results, so your pages stay up.
- **One permission:** a single "View Clicky Analytics data" permission decides who sees the widgets,
  the Page Stats field and the drilldown.
- **More than one Clicky site:** give each Craft site its own Clicky Site ID and Sitekey in the config
  file, and pick which one each dashboard widget reports on.

## Credits & attribution

Clicky is a trademark of [Roxr Software Ltd.](https://clicky.com); this is an unofficial, community
plugin with nothing to do with them directly. It bundles a few icon sets to identify browsers,
operating systems, search engines and countries:

- Country flags: [flag-icons](https://github.com/lipis/flag-icons) (MIT)
- Browser logos: [browser-logos](https://github.com/alrra/browser-logos) (respective trademarks)
- OS & search-engine logos: [SVG Logos](https://github.com/gilbarbara/logos) (respective trademarks)

All product names, logos and brands belong to their respective owners and are used here only to tell
one browser, OS or country apart from another.

## Documentation

Full documentation is at [johnhenry.ie/plugins/clicky-analytics/docs](https://johnhenry.ie/plugins/clicky-analytics/docs/getting-started/overview).

## Requirements

- Craft CMS 5.0 or later
- PHP 8.2 or later
- A Clicky account with API access (Site ID and Sitekey)

## Accessibility

How accessible the plugin is, what's been checked, and how to report a problem are all in the [accessibility statement](https://github.com/john-henry/craft-clicky-analytics/blob/craft-5/ACCESSIBILITY.md).

## Support

Need a hand? Open an issue on the [GitHub Issues page](https://github.com/john-henry/craft-clicky-analytics/issues).

## License

This plugin is free to use under the MIT License.

---

<a href="https://johnhenry.ie/plugins/" target="_blank">
    <img height="46" src="https://johnhenry.ie/images/plugins/logo.svg" alt="John Henry - Craft CMS Plugins">
</a>
