# Modern Catholic Plugin Suite

Part of **Modern Catholic** — modular WordPress tools for Catholic parish websites.

---

# Modern Catholic – Parish Events

![License: GPL-3.0-only](https://img.shields.io/badge/License-GPL--3.0--only-blue.svg)
![WordPress: 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-21759b.svg)
![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bbb.svg)

A simple, flexible WordPress plugin that adds an “Events” custom post type for parish calendars. Supports start/end date-times, event locations, and a shortcode for listing upcoming events inside any theme. Built to integrate seamlessly with modern block-based WordPress sites.

---

## Features

- Custom post type `pp_event` with an archive at `/events` (`show_in_rest: true`)
- Meta box fields for start date/time (`_pp_event_start`), end date/time (`_pp_event_end`), and location (`_pp_event_location`)
- Shortcode `[parishpress_events]` lists events ordered by start date ascending; accepts `limit` to control how many appear
- Block: **Modern Catholic – Parish Events** (`parishpress/events`) exposes the `limit` setting in the editor and renders through the shortcode
- Minimal front-end CSS enqueued only on the public site; inherits your theme’s typography/layout

---

## Shortcode

List upcoming events (default limit 5):

```text
[parishpress_events]
```

Limit how many events display:

```text
[parishpress_events limit="10"]
```

- `limit` (int): Number of events to list (default 5). Events are sorted by `_pp_event_start` ascending.

---

## Block

Add the **Modern Catholic – Parish Events** block in the editor to set the list length visually. The block uses the shortcode renderer for consistent output.

---

## Installation

1. Upload or clone `modern-catholic-parish-events` into `wp-content/plugins/`.
2. Activate **Modern Catholic – Parish Events** from Plugins.
3. Add Events (`pp_event`) with start/end times and a location, then place the shortcode or block where you want the list to appear.

---

## 📝 Changelog

0.2.0

- Initial commit.

---

## 🔑 License

Licensed under the GNU General Public License version 3.0 only (`GPL-3.0-only`).

## Compatibility identifiers

The `pp_event` post type, `_pp_event_*` metadata, `[parishpress_events]`
shortcode, `parishpress/events` block name, and `parishpress-events` text domain
are retained so existing WordPress content remains compatible. New public
branding uses Modern Catholic.

---
