<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function parishpress_events_register_block() {
    $script_path = PARISHPRESS_EVENTS_PATH . 'assets/js/block.js';

    wp_register_script(
        'parishpress-events-block-editor',
        PARISHPRESS_EVENTS_URL . 'assets/js/block.js',
        array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' ),
        file_exists( $script_path ) ? filemtime( $script_path ) : PARISHPRESS_EVENTS_VERSION
    );

    if ( ! wp_style_is( 'parishpress-events-frontend', 'registered' ) ) {
        wp_register_style(
            'parishpress-events-frontend',
            PARISHPRESS_EVENTS_URL . 'assets/css/frontend.css',
            array(),
            PARISHPRESS_EVENTS_VERSION
        );
    }

    register_block_type(
        'parishpress/events',
        array(
            'editor_script'   => 'parishpress-events-block-editor',
            'style'           => 'parishpress-events-frontend',
            'render_callback' => 'parishpress_events_block_render',
            'attributes'      => array(
                'limit' => array( 'type' => 'number', 'default' => 5 ),
            ),
        )
    );
}
add_action( 'init', 'parishpress_events_register_block' );

function parishpress_events_block_render( $attributes ) {
    $limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 5;
    if ( $limit < 1 ) {
        $limit = 5;
    }

    $shortcode = sprintf( '[parishpress_events limit="%d"]', $limit );

    return do_shortcode( $shortcode );
}
