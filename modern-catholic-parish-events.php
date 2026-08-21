<?php
/**
 * Plugin Name: Modern Catholic – Parish Events
 * Plugin URI: https://github.com/twitchd8/modern-catholic-plugin-parish-events
 * Description: Recurring parish events, accessible calendars, subscriptions, and occurrence-level discovery.
 * Version: 1.0.2
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Author: Andrew T. Schmitt
 * License: GPL-3.0-only
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: modern-catholic-parish-events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MODERN_CATHOLIC_EVENTS_VERSION', '1.0.2' );
define( 'MODERN_CATHOLIC_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MODERN_CATHOLIC_EVENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION', 'modern_catholic_events_cache_version' );
define( 'MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS', 12 );
define( 'MODERN_CATHOLIC_EVENTS_MAX_OCCURRENCES', 5000 );

require MODERN_CATHOLIC_EVENTS_PATH . 'includes/cpt.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/recurrence.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/admin.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/assets.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/shortcode.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/block.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/archive.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/occurrence.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/icalendar.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/seo.php';
require MODERN_CATHOLIC_EVENTS_PATH . 'includes/rest.php';

/**
 * Grants event-management capabilities and creates rebuildable runtime state.
 */
function modern_catholic_events_activate() {
    modern_catholic_events_register_content_types();
    modern_catholic_events_add_category_rewrites();
    modern_catholic_events_add_occurrence_rewrites();
    modern_catholic_events_add_icalendar_rewrites();

    foreach ( array( 'administrator', 'editor' ) as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }
        foreach ( modern_catholic_events_capabilities() as $capability ) {
            $role->add_cap( $capability );
        }
    }

    if ( false === get_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION, false ) ) {
        add_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION, 1, '', false );
    }

    $event_ids = get_posts( array( 'post_type' => 'mc_event', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $event_ids as $event_id ) {
        if ( ! modern_catholic_events_get_meta( $event_id, 'series_uid' ) ) {
            update_post_meta( $event_id, '_mc_event_series_uid', wp_generate_uuid4() );
        }
    }

    if ( ! wp_next_scheduled( 'modern_catholic_events_daily_cache_warm' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'modern_catholic_events_daily_cache_warm' );
    }

    flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'modern_catholic_events_activate' );

/**
 * Removes scheduled work and refreshes rewrites without deleting content.
 */
function modern_catholic_events_deactivate() {
    wp_clear_scheduled_hook( 'modern_catholic_events_daily_cache_warm' );
    flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'modern_catholic_events_deactivate' );
