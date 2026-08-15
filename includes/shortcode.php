<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders [modern_catholic_events] through the central occurrence service.
 *
 * @param array $attributes Shortcode attributes.
 * @return string
 */
function modern_catholic_events_shortcode( $attributes ) {
    $attributes = shortcode_atts(
        array(
            'limit'    => 5,
            'start'    => 'today',
            'end'      => '+3 months',
            'view'     => 'list',
            'category' => '',
        ),
        $attributes,
        'modern_catholic_events'
    );

    return modern_catholic_events_render_collection(
        array(
            'limit'    => max( 1, min( 100, (int) $attributes['limit'] ) ),
            'start'    => sanitize_text_field( $attributes['start'] ),
            'end'      => sanitize_text_field( $attributes['end'] ),
            'view'     => in_array( $attributes['view'], array( 'list', 'calendar' ), true ) ? $attributes['view'] : 'list',
            'category' => sanitize_title( $attributes['category'] ),
            'heading'  => false,
        )
    );
}
add_shortcode( 'modern_catholic_events', 'modern_catholic_events_shortcode' );
