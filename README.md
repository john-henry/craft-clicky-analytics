[![Stable Version](https://img.shields.io/packagist/v/johnhenry/craft-clicky-analytics?label=stable&style=for-the-badge)](https://packagist.org/packages/johnhenry/craft-clicky-analytics)
[![Static Badge](https://img.shields.io/badge/free-plugin?style=for-the-badge&logo=craftcms&logoColor=white&logoSize=auto&label=Craft%20Plugin%20Store&labelColor=%23E5422B)](https://plugins.craftcms.com/clicky-analytics?craft5)

![Clicky Analytics for Craft CMS](https://johnhenry.ie/images/plugins/promos/clicky-analytics/1.png)

# Clicky Analytics for Craft CMS

Clicky Analytics puts your [Clicky](https://clicky.com) figures in the Craft control panel. When
someone asks how a page is doing, open the entry and its visitors and sources are right there. The
dashboard widgets cover the whole site, and all of it comes from the [Clicky API](https://clicky.com/help/api).

## Features

- **21 dashboard widgets:** build your own Craft dashboard from Report, Live Visitors, Current
  Visitors, Overview, New vs Returning, Top Pages, Top Sources, Top Referrals, Top Search Engines,
  Top Countries, Top Browsers, Top Operating Systems, Top Devices, Top Entry Pages, Top Exit Pages,
  Top Downloads, Goals, Campaigns, Top Cities, Top Regions and Screen Resolutions.
- **Report widget:** pick one of 21 metrics and show it as a counter. Ranked reports can also be a bar or pie chart, and visitors or actions a line chart.
- **Live Visitors widget:** recent visits with the country flag, location, landing page, browser and
  OS logos, referrer and time on site. You can show new visitors, returning ones or both.
- **Country drilldown:** open a country in the Top Countries widget to see its cities and regions
  without leaving the widget.
- **Page Stats field:** add the "Clicky Analytics Page Stats" field to an entry type's field layout
  and each entry shows its own visitors, traffic sources and recent visitors in the entry editor.
- **Date ranges for each widget:** Today, Yesterday, 2 days ago, Last 7/14/28/60/90 days and recent
  months.
- **Colour schemes:** change every widget, the field and the charts together in the plugin settings,
  picking from Clicky Analytics (warm), Ocean (blue), Monochrome, Forest (green) or Berry (purple).
- **Layout options:** a compact density and a slim bar style, if you'd rather the widgets and the
  field sat tighter than the default.
- **Flags and logos:** the country flags and the browser, OS and search engine logos are SVGs served
  from your own site, so they look the same on every computer.
- **Version consolidation:** a widget can merge browser and OS versions, so you get one "Google
  Chrome" row instead of one for each version.
- **Open in Clicky:** every row links to the matching report in Clicky.
- **Tracking code:** the plugin can add the Clicky tracking snippet, with its `<noscript>` fallback,
  to every front-end page, so you don't have to paste it into your layout. It leaves it out while
  `devMode` is on and in previews.
- **Twig variable:** read the same data in your own templates through `craft.clickyAnalytics`. If
  Clicky can't be reached it hands back empty results, so your pages stay up.
- **Tools for AI agents:** from 1.2.0, on Craft 5.12 and later and on Craft 6, Craft's MCP server
  gets three read-only tools that let an agent read the overview, any ranked report and a single page's stats.
  They don't hand over any individual visitor data.
- **One permission:** "View Clicky Analytics data and add its dashboard widgets" decides who sees the widgets, the Page Stats field
  and the drilldown.
- **More than one Clicky site:** give each Craft site its own Clicky Site ID and Sitekey in the
  config file, and pick which one each dashboard widget reports on.

## Credits & attribution

Clicky is a trademark of [Roxr Software Ltd.](https://clicky.com). This is an unofficial community
plugin and has nothing to do with them. It bundles a few icon sets to identify browsers, operating
systems, search engines and countries:

- Country flags: [flag-icons](https://github.com/lipis/flag-icons) (MIT)
- Browser logos: [browser-logos](https://github.com/alrra/browser-logos) (respective trademarks)
- OS & search-engine logos: [SVG Logos](https://github.com/gilbarbara/logos) (respective trademarks)

All product names, logos and brands belong to their owners and are used here only to tell one
browser, OS or country from another.

## Documentation

The full documentation is at [johnhenry.ie/plugins/clicky-analytics/docs](https://johnhenry.ie/plugins/clicky-analytics/docs/getting-started/overview).

## Requirements

- Craft CMS 5.0 or later
- PHP 8.2 or later
- A Clicky account with API access (Site ID and Sitekey)

Craft CMS 6 is supported from 1.2.0. On Craft 6 you need PHP 8.5, and your project has
to require `craftcms/yii2-adapter` itself, as the plugin doesn't pull it in. The config file
goes in `config/craft/clicky-analytics.php` on Craft 6.

## Accessibility

How accessible the plugin is, what's been checked and how to report a problem are all in the [accessibility statement](https://github.com/john-henry/craft-clicky-analytics/blob/craft-5/ACCESSIBILITY.md).

## Support

If you need a hand, open an issue on the [GitHub Issues page](https://github.com/john-henry/craft-clicky-analytics/issues).

## License

This plugin is free to use under the MIT License.

---

<a href="https://johnhenry.ie/plugins/" target="_blank">
    <img height="46" src="https://johnhenry.ie/images/plugins/logo.svg" alt="John Henry - Craft CMS Plugins">
</a>
