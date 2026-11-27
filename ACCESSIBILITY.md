# Accessibility statement for Clicky Analytics

Clicky Analytics is a plugin for Craft CMS 5 and 6. This statement sets out how accessible it is, what I know is wrong with it, and how to tell me about anything else.

## What this covers

This statement covers Clicky Analytics 1.2.0:

- the dashboard widgets, including the country drilldown in Top Countries and the online-now marker in Live Visitors
- the Page Stats field, which shows a page's figures on its entry
- the settings page, including the colour scheme picker and connection test

It doesn't cover:

- Craft's own control panel, which Craft reports on at https://craftcms.com/accessibility
- your site's own templates and content
- Clicky's own website, which the plugin links to
- the tools for AI agents on Craft's MCP server, which have no interface of their own

## The standard I'm working to

WCAG 2.2 level AA. Clicky Analytics adds to the Craft control panel, which is an authoring tool, so I also follow ATAG 2.0 Part A where it applies to the parts Clicky Analytics adds. Craft itself works to the same standards and publishes its own reports at https://craftcms.com/accessibility, but those reports don't cover plugins.

## How far it meets it

I believe it meets WCAG 2.2 AA. The review found three issues, and this version fixes all of them. It hasn't been tested with assistive technology yet, though, so I'm not claiming full conformance until it has.

## How it was checked

Last checked in November 2026:

- A review of every control panel template, script and stylesheet against WCAG 2.2 AA, including the WAI-ARIA Authoring Practices for the drilldown, and contrast ratios worked out from the actual colours in the CSS against Craft's own text colours.
- The charts were checked for a text alternative: each line chart has a data table behind it for screen readers, and the donut charts have a legend with the figures.
- Not done yet: testing with screen readers (NVDA, JAWS and VoiceOver), zoom and reflow at 400%, or speech input. This statement will be updated once that's done.

## Known issues

None known right now. If you come across one, please tell me (see below).

## Tell me about a problem

If something in Clicky Analytics is hard or impossible for you to use, open an issue at https://github.com/john-henry/craft-clicky-analytics/issues and put "Accessibility" in the title. Say which screen you were on, what you were trying to do, and which browser and assistive technology you use, if any. I aim to reply within five working days.

## Last reviewed

24 November 2026, for version 1.2.0.
