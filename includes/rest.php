<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Converts an occurrence to a public REST representation.
 */
function modern_catholic_events_rest_occurrence( $occurrence ) {
    $terms = wp_get_object_terms( $occurrence['post_id'], 'mc_event_category', array( 'fields' => 'slugs' ) );
    return array(
        'id'            => $occurrence['occurrence_uid'],
        'seriesId'      => $occurrence['series_id'],
        'title'         => $occurrence['title'],
        'excerpt'       => $occurrence['excerpt'],
        'start'         => $occurrence['start']->format( DATE_ATOM ),
        'end'           => $occurrence['end']->format( DATE_ATOM ),
        'allDay'        => $occurrence['all_day'],
        'status'        => $occurrence['event_status'],
        'url'           => $occurrence['permalink'],
        'recurrenceId'  => $occurrence['recurrence_id'],
        'categories'    => is_wp_error( $terms ) ? array() : $terms,
    );
}

/**
 * Registers public bounded occurrence query endpoints.
 */
function modern_catholic_events_register_rest_routes() {
    register_rest_route(
        'modern-catholic/v1',
        '/events',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'modern_catholic_events_rest_list',
            'args'                => array(
                'start'    => array( 'type' => 'string', 'format' => 'date', 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
                'end'      => array( 'type' => 'string', 'format' => 'date', 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
                'category' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ),
                'limit'    => array( 'type' => 'integer', 'default' => 100, 'minimum' => 1, 'maximum' => 500 ),
            ),
        )
    );
    register_rest_route(
        'modern-catholic/v1',
        '/events/(?P<slug>[a-z0-9-]+)/(?P<date>\d{4}-\d{2}-\d{2})',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'modern_catholic_events_rest_single',
            'args'                => array(
                'slug' => array( 'sanitize_callback' => 'sanitize_title' ),
                'date' => array( 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'modern_catholic_events_register_rest_routes' );

function modern_catholic_events_rest_list( $request ) {
    $start = $request['start'] ?: wp_date( 'Y-m-d' );
    $end   = $request['end'] ?: ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' )->format( 'Y-m-d' );
    $items = modern_catholic_events_get_occurrences( $start . ' 00:00:00', $end . ' 23:59:59', array( 'category' => $request['category'], 'limit' => (int) $request['limit'] ) );
    return rest_ensure_response( array_map( 'modern_catholic_events_rest_occurrence', $items ) );
}

function modern_catholic_events_rest_single( $request ) {
    $occurrence = modern_catholic_events_find_occurrence( $request['slug'], $request['date'] );
    if ( ! $occurrence ) {
        return new WP_Error( 'event_occurrence_not_found', __( 'The event occurrence was not found.', 'modern-catholic-parish-events' ), array( 'status' => 404 ) );
    }
    return rest_ensure_response( modern_catholic_events_rest_occurrence( $occurrence ) );
}

