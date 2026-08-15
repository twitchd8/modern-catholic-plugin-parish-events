<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers public event styles.
 */
function modern_catholic_events_register_assets() {
    wp_register_style(
        'modern-catholic-events',
        MODERN_CATHOLIC_EVENTS_URL . 'assets/css/frontend.css',
        array(),
        MODERN_CATHOLIC_EVENTS_VERSION
    );
    wp_enqueue_style( 'modern-catholic-events' );
}
add_action( 'wp_enqueue_scripts', 'modern_catholic_events_register_assets' );

/**
 * Loads editor behavior and the optional current Google Places component.
 */
function modern_catholic_events_admin_assets( $hook_suffix ) {
    $screen = get_current_screen();
    if ( ! $screen || 'mc_event' !== $screen->post_type || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    wp_enqueue_style( 'modern-catholic-events-admin', MODERN_CATHOLIC_EVENTS_URL . 'assets/css/admin.css', array(), MODERN_CATHOLIC_EVENTS_VERSION );
    wp_enqueue_script( 'modern-catholic-events-admin', MODERN_CATHOLIC_EVENTS_URL . 'assets/js/admin.js', array(), MODERN_CATHOLIC_EVENTS_VERSION, true );

    $api_key = get_option( 'modern_catholic_events_google_places_api_key', '' );
    if ( $api_key ) {
        wp_enqueue_script(
            'modern-catholic-events-google-places',
            add_query_arg(
                array(
                    'key'       => $api_key,
                    'libraries' => 'places',
                    'loading'   => 'async',
                    'v'         => 'weekly',
                ),
                'https://maps.googleapis.com/maps/api/js'
            ),
            array( 'modern-catholic-events-admin' ),
            null,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'modern_catholic_events_admin_assets' );

/**
 * Generates a Google Maps search URL without storing a manually entered maps URL.
 *
 * @param int $post_id Event or override post ID.
 * @return string
 */
function modern_catholic_events_google_maps_url( $post_id ) {
    $place_id = modern_catholic_events_get_meta( $post_id, 'google_place_id' );
    $address  = modern_catholic_events_get_meta( $post_id, 'formatted_address' );
    $latitude = (float) modern_catholic_events_get_meta( $post_id, 'latitude' );
    $longitude = (float) modern_catholic_events_get_meta( $post_id, 'longitude' );
    $query    = $address;

    if ( ! $query && ( $latitude || $longitude ) ) {
        $query = $latitude . ',' . $longitude;
    }
    if ( ! $query && ! $place_id ) {
        return '';
    }

    $args = array( 'api' => 1, 'query' => $query ?: $place_id );
    if ( $place_id ) {
        $args['query_place_id'] = $place_id;
    }
    return add_query_arg( $args, 'https://www.google.com/maps/search/' );
}
