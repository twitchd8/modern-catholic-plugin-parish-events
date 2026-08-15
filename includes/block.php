<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the dynamic Events block.
 */
function modern_catholic_events_register_block() {
    $script_path = MODERN_CATHOLIC_EVENTS_PATH . 'assets/js/block.js';
    wp_register_script(
        'modern-catholic-events-block-editor',
        MODERN_CATHOLIC_EVENTS_URL . 'assets/js/block.js',
        array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ),
        file_exists( $script_path ) ? filemtime( $script_path ) : MODERN_CATHOLIC_EVENTS_VERSION,
        true
    );

    register_block_type(
        'modern-catholic/events',
        array(
            'api_version'     => 3,
            'editor_script'   => 'modern-catholic-events-block-editor',
            'style'           => 'modern-catholic-events',
            'attributes'      => array(
                'limit'     => array( 'type' => 'number', 'default' => 5 ),
                'start'     => array( 'type' => 'string', 'default' => 'today' ),
                'end'       => array( 'type' => 'string', 'default' => '+3 months' ),
                'view'      => array( 'type' => 'string', 'default' => 'list' ),
                'category'  => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => 'modern_catholic_events_render_block',
        )
    );
}
add_action( 'init', 'modern_catholic_events_register_block', 20 );

/**
 * Renders the dynamic block through the same collection renderer as the shortcode.
 */
function modern_catholic_events_render_block( $attributes ) {
    return modern_catholic_events_render_collection(
        array(
            'limit'    => max( 1, min( 100, isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 5 ) ),
            'start'    => isset( $attributes['start'] ) ? sanitize_text_field( $attributes['start'] ) : 'today',
            'end'      => isset( $attributes['end'] ) ? sanitize_text_field( $attributes['end'] ) : '+3 months',
            'view'     => isset( $attributes['view'] ) && 'calendar' === $attributes['view'] ? 'calendar' : 'list',
            'category' => isset( $attributes['category'] ) ? sanitize_title( $attributes['category'] ) : '',
            'heading'  => false,
        )
    );
}
