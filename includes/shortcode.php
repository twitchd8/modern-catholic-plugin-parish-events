<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [parishpress_events limit="5"]
 */
function parishpress_events_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'limit' => 5,
        ),
        $atts,
        'parishpress_events'
    );

    $q = new WP_Query(
        array(
            'post_type'      => 'pp_event',
            'posts_per_page' => (int) $atts['limit'],
            'meta_key'       => '_pp_event_start',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_pp_event_start',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    ob_start();

    if ( $q->have_posts() ) {
        echo '<ul class="parishpress-events-list">';
        while ( $q->have_posts() ) {
            $q->the_post();
            $start = get_post_meta( get_the_ID(), '_pp_event_start', true );
            $end   = get_post_meta( get_the_ID(), '_pp_event_end', true );
            $loc   = get_post_meta( get_the_ID(), '_pp_event_location', true );

            echo '<li class="parishpress-events-item">';
            echo '<div class="pp-event-header">';
            echo '<strong class="pp-event-title">' . esc_html( get_the_title() ) . '</strong>';
            if ( $start ) {
                echo ' <span class="pp-event-start">' . esc_html( $start ) . '</span>';
            }
            if ( $end ) {
                echo ' – <span class="pp-event-end">' . esc_html( $end ) . '</span>';
            }
            echo '</div>';

            if ( $loc ) {
                echo '<div class="pp-event-location">' . esc_html( $loc ) . '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    } else {
        esc_html_e( 'No upcoming events.', 'parishpress-events' );
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'parishpress_events', 'parishpress_events_shortcode' );
