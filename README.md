# Modern Catholic – Parish Events

Native WordPress event publishing for Modern Catholic websites. The plugin provides recurring series, linked occurrence overrides, Event Categories, list and calendar views, public calendar subscriptions, and occurrence-level discovery without using an authoritative custom table.

## Requirements

- WordPress 6.7 or newer
- PHP 7.4 or newer
- A block or classic WordPress theme

## Installation

1. Place `modern-catholic-plugin-parish-events` in `wp-content/plugins/`.
2. Activate **Modern Catholic – Parish Events**.
3. Refresh permalinks if the host does not do so automatically.
4. Create events under **Events → Add New**.

The plugin registers the nonhierarchical `mc_event` post type and hierarchical `mc_event_category` taxonomy. Event Categories are empty by default and are managed through the normal WordPress interface.

## Event fields

The Event editor supports:

- Start/end date and time, all-day mode, and scheduled/canceled/postponed/rescheduled status
- In-person, online, hybrid, and to-be-announced locations
- Venue, structured address, automatically generated formatted address, Google Place ID, coordinates, and online URL
- Registration URL, button label, optional price/currency, and public contact details
- Featured image, body content, excerpt, revisions, and autosave
- Daily, weekly, monthly, yearly, interval, finite, added-date, and excluded-date recurrence

Every event field is registered post metadata with an explicit type, default, sanitizer, authorization callback, and REST schema.

## Recurrence and occurrence editing

Recurrence uses RFC 5545-compatible concepts in the WordPress site timezone. Local wall times remain stable through daylight-saving changes. Occurrences are generated only for bounded requested ranges. The editor reveals only the controls applicable to the selected frequency, monthly pattern, and ending rule; additional and excluded dates remain available under **Advanced recurrence options**.

Authorized editors can choose:

- **This occurrence only** — creates or opens a linked override with the original recurrence identifier.
- **This and following** — ends the original series before that occurrence and creates a linked successor series.
- **Entire series** — edits the source series and invalidates occurrence caches.

Series, overrides, successor series, metadata, media, and category relationships remain native WordPress posts, postmeta, and terms.

## Archive, categories, and occurrence pages

- Events archive: `/events/`
- Event Category archive: `/events/category/{category}/`
- Occurrence: `/events/{event-slug}/{original-date}/`

The Site Editor exposes templates named **Events**, **Event Category**, and **Event**. The archive includes list/calendar switching, month navigation, category filtering, subscription/download controls, empty states, and linked effective occurrences. Classic-theme fallbacks are included.

## Shortcode

```text
[modern_catholic_events limit="10" start="today" end="+3 months" view="list" category="formation"]
```

Supported attributes:

- `limit`: 1–100
- `start`: a date or safe relative expression
- `end`: a date or safe relative expression
- `view`: `list` or `calendar`
- `category`: Event Category slug

## Block

The **Modern Catholic – Parish Events** block uses namespace `modern-catholic/events` and exposes the same limit, date range, view, and category options. Both the block and shortcode call the central occurrence service.

## Calendar subscriptions and downloads

- All Events subscription: `/events/calendar.ics`
- All Events download: `/events/calendar-download.ics`
- Category subscription: `/events/category/{category}/calendar.ics`
- Category download: `/events/category/{category}/calendar-download.ics`
- Individual occurrence download: append `event.ics` to an occurrence URL

Output uses UTF-8, CRLF endings, folded content lines, stable UIDs, timezone identifiers, recurrence rules, added/excluded dates, recurrence identifiers, statuses, sequence values, and public event details. Public feeds include published events only and are bounded to the configured public horizon.

## Google Places configuration

Google Places is optional. Manual address entry works without JavaScript or an API key.

1. Enable Places API (New) and Maps JavaScript API for a Google Cloud project.
2. Create a browser API key restricted to the website origin and required APIs.
3. Save it under **Events → Settings**.

The editor uses the current Place Autocomplete web component and requests only the place identifier, display name, formatted address, address components, and coordinates. Structured address fields are the editable source of truth. The formatted address is a generated read-only preview and is recalculated during manual and REST saves. Public Google Maps links are generated automatically; editors do not enter a maps URL.

## REST, SEO, and sitemaps

- WordPress REST post and taxonomy endpoints expose registered public metadata according to their schemas.
- `GET /wp-json/modern-catholic/v1/events` returns bounded effective occurrences with optional start, end, limit, and category parameters.
- Each occurrence emits one canonical Schema.org `Event`, page metadata, and social metadata.
- Virtual occurrence URLs are added to the WordPress XML sitemap provider for the next 12 months.
- Common SEO-plugin graphs are filtered only to remove a conflicting duplicate `Event` object on occurrence pages.

## Caching and performance

The occurrence service uses a global data version in cache keys. Event, override, status, and category changes increment that version. Visitor rendering may read a prewarmed transient but does not write to the database. WP-Cron prewarms a common range; correctness does not depend on cron or cache availability.

## Security

- Event writes require a nonce and event-editing capability.
- Settings require `manage_options`.
- REST writes use registered metadata authorization and cross-field validation.
- Public output is escaped for context.
- Calendar and occurrence endpoints expose published public event information only.
- No credentials are stored in source or included in release packages.

## Changelog

### 1.0.0

- First production-ready release of Modern Catholic – Parish Events.
- Includes native Event posts and categories, structured event details, recurring series, linked occurrence overrides, split-series editing, stable occurrence URLs, and rebuildable bounded caches.
- Includes dedicated Events, Event Category, and Event templates with responsive list and calendar views, filters, block and shortcode output, and accessible editor controls.
- Includes public all-events, category, and individual RFC 5545 calendar feeds and downloads with stable identifiers, recurrence exceptions, cancellation, and rescheduling support.
- Includes occurrence-level canonical and social metadata, Schema.org Event output, bounded XML sitemap integration, REST endpoints, optional Google Places autocomplete, generated addresses, and automatic Google Maps links.
- Includes LocalWP-tested security, validation, sanitization, migration, recurrence, export, responsive-interface, and discovery behavior with no legacy compatibility layer.

### 0.5.1

- Made structured address fields the editor source of truth and replaced duplicate formatted-address entry with an automatically updated, read-only preview backed by server-side derivation.
- Clear stale Places identifiers and coordinates when an editor manually changes a structured address.
- Added accessible recurrence progressive disclosure for frequency-specific, monthly-pattern, and ending-rule controls.
- Moved additional and excluded dates into a native **Advanced recurrence options** disclosure while retaining a functional no-JavaScript fallback.
- Added automated and LocalWP admin-browser coverage for address derivation, save readback, and recurrence-control visibility.

### 0.5.0

- Replaced all pre-distribution legacy naming with the `mc_event` post type, `_mc_event_*` metadata, `modern_catholic_events_*` PHP API, `MODERN_CATHOLIC_EVENTS_*` constants, `[modern_catholic_events]` shortcode, `modern-catholic/events` block, and `modern-catholic-parish-events` text domain.
- Added the complete Event post type, Event Categories taxonomy, registered metadata schemas, custom capabilities, editor panels, REST validation, and optional Google Places autocomplete with manual fallback.
- Added the shared bounded recurrence engine with daily, weekly, monthly, yearly, interval, count/date limits, added/excluded dates, DST-stable local times, linked occurrence overrides, series splitting, stable identifiers, and rebuildable versioned caches.
- Added dedicated **Events**, **Event Category**, and **Event** templates; responsive list/calendar views; filters; month navigation; occurrence pages; and shared block/shortcode rendering.
- Added all-events, category, and individual RFC 5545 calendar feeds and downloads with stable UIDs, recurrence data, exceptions, cancellation/rescheduling status, sequence tracking, escaping, folding, and CRLF output.
- Added occurrence-level canonical, social, and Schema.org Event metadata; duplicate Event-schema coordination; and a 12-month WordPress XML occurrence sitemap.
- Added integration coverage for recurrence, DST, overrides, splits, categories, calendar feeds, schema output, permissions, validation, REST output, and cache invalidation.
- Migrated the ATS-WP-DEV site's local legacy event metadata to the final schema and removed the temporary migration implementation so no compatibility layer ships.

## License

GNU General Public License v3.0 only (`GPL-3.0-only`).
