# Development

## Architecture

- `includes/cpt.php`: post type, taxonomy, registered metadata, sanitizers, and capabilities
- `includes/recurrence.php`: bounded recurrence expansion, overrides, splits, stable identifiers, and cache invalidation
- `includes/admin.php`: editor panels, validation, settings, and occurrence edit scopes
- `includes/archive.php`: archive, list, calendar, and category filtering
- `includes/occurrence.php`: dated occurrence routes and templates
- `includes/icalendar.php`: RFC 5545 serialization and public calendar endpoints
- `includes/seo.php`: occurrence metadata, Schema.org, SEO coordination, and sitemap provider
- `includes/rest.php`: public effective-occurrence endpoints
- `includes/shortcode.php` and `includes/block.php`: shared occurrence-service consumers

## Source of truth

Native `mc_event` posts, `_mc_event_*` postmeta, featured media, and `mc_event_category` terms are authoritative. Transients and generated occurrence arrays are disposable indexes and can be rebuilt.

Structured address metadata is authoritative for manually entered locations. `_mc_event_formatted_address` is derived for display, maps, calendar feeds, and metadata output; it is not a second editor-entered address.

An override is an `mc_event` whose `_mc_event_series_id` points to its source series and whose `_mc_event_recurrence_id` stores the original local recurrence date/time. A successor series uses `_mc_event_previous_series_id` to retain the split relationship.

## Validation

Use the active LocalWP PHP configuration and database port for the ATS-WP-DEV site. Validation must cover syntax, activation, metadata schemas, recurrence, overrides/splits, categories, cache invalidation, REST, HTTP templates, calendar output, sitemap output, JSON-LD, editor behavior, responsive layout, and Git status.

The integration runner creates temporary posts and terms and removes them in a `finally` cleanup path.
