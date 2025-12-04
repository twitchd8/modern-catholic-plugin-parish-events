<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function parishpress_events_enqueue_assets() {
    wp_enqueue_style(
        'parishpress-events-frontend',
        PARISHPRESS_EVENTS_URL . 'assets/css/frontend.css',
        array(),
        PARISHPRESS_EVENTS_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'parishpress_events_enqueue_assets' );
