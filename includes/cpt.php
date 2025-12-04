<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function parishpress_events_register_cpt() {
    $labels = array(
        'name'          => __( 'Events', 'parishpress-events' ),
        'singular_name' => __( 'Event', 'parishpress-events' ),
        'add_new_item'  => __( 'Add New Event', 'parishpress-events' ),
        'edit_item'     => __( 'Edit Event', 'parishpress-events' ),
        'menu_name'     => __( 'Events', 'parishpress-events' ),
    );

    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'show_in_rest' => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'events' ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'    => 'dashicons-calendar-alt',
    );

    register_post_type( 'pp_event', $args );
}
add_action( 'init', 'parishpress_events_register_cpt' );
