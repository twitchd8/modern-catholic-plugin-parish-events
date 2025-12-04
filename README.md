# ParishPress Events

![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)

ParishPress Events adds an **Events** custom post type for parish calendars. Capture start/end date-times and locations, then list upcoming events with a shortcode or block. Minimal styling keeps the output consistent with your theme.

---

## Features

- Custom post type `pp_event` with an archive at `/events` (`show_in_rest: true`)
- Meta box fields for start date/time (`_pp_event_start`), end date/time (`_pp_event_end`), and location (`_pp_event_location`)
- Shortcode `[parishpress_events]` lists events ordered by start date ascending; accepts `limit` to control how many appear
- Block: **ParishPress Events** (`parishpress/events`) exposes the `limit` setting in the editor and renders through the shortcode
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

Add the **ParishPress Events** block in the editor to set the list length visually. The block uses the shortcode renderer for consistent output.

---

## Installation

1. Upload or clone `parishpress-events` into `wp-content/plugins/`.
2. Activate **ParishPress Events** from Plugins.
3. Add Events (`pp_event`) with start/end times and a location, then place the shortcode or block where you want the list to appear.
