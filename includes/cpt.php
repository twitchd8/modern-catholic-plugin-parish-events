<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the primitive event capabilities granted on activation.
 *
 * @return string[]
 */
function modern_catholic_events_capabilities() {
    return array(
        'edit_event', 'read_event', 'delete_event', 'edit_events', 'edit_others_events',
        'publish_events', 'read_private_events', 'delete_events', 'delete_private_events',
        'delete_published_events', 'delete_others_events', 'edit_private_events',
        'edit_published_events', 'create_events',
    );
}

/**
 * Registers the Event post type and Event Categories taxonomy.
 */
function modern_catholic_events_register_content_types() {
    register_post_type(
        'mc_event',
        array(
            'labels' => array(
                'name'                     => __( 'Events', 'modern-catholic-parish-events' ),
                'singular_name'            => __( 'Event', 'modern-catholic-parish-events' ),
                'add_new'                  => __( 'Add New', 'modern-catholic-parish-events' ),
                'add_new_item'             => __( 'Add New Event', 'modern-catholic-parish-events' ),
                'edit_item'                => __( 'Edit Event', 'modern-catholic-parish-events' ),
                'new_item'                 => __( 'New Event', 'modern-catholic-parish-events' ),
                'view_item'                => __( 'View Event', 'modern-catholic-parish-events' ),
                'view_items'               => __( 'View Events', 'modern-catholic-parish-events' ),
                'search_items'             => __( 'Search Events', 'modern-catholic-parish-events' ),
                'not_found'                => __( 'No events found.', 'modern-catholic-parish-events' ),
                'not_found_in_trash'       => __( 'No events found in Trash.', 'modern-catholic-parish-events' ),
                'parent_item_colon'        => __( 'Parent Event:', 'modern-catholic-parish-events' ),
                'all_items'                => __( 'All Events', 'modern-catholic-parish-events' ),
                'archives'                 => __( 'Event Archives', 'modern-catholic-parish-events' ),
                'attributes'               => __( 'Event Attributes', 'modern-catholic-parish-events' ),
                'insert_into_item'         => __( 'Insert into event', 'modern-catholic-parish-events' ),
                'uploaded_to_this_item'    => __( 'Uploaded to this event', 'modern-catholic-parish-events' ),
                'featured_image'           => __( 'Event image', 'modern-catholic-parish-events' ),
                'set_featured_image'       => __( 'Set event image', 'modern-catholic-parish-events' ),
                'remove_featured_image'    => __( 'Remove event image', 'modern-catholic-parish-events' ),
                'use_featured_image'       => __( 'Use as event image', 'modern-catholic-parish-events' ),
                'filter_items_list'        => __( 'Filter events list', 'modern-catholic-parish-events' ),
                'filter_by_date'           => __( 'Filter events by date', 'modern-catholic-parish-events' ),
                'items_list_navigation'    => __( 'Events list navigation', 'modern-catholic-parish-events' ),
                'items_list'               => __( 'Events list', 'modern-catholic-parish-events' ),
                'item_published'           => __( 'Event published.', 'modern-catholic-parish-events' ),
                'item_published_privately' => __( 'Event published privately.', 'modern-catholic-parish-events' ),
                'item_reverted_to_draft'   => __( 'Event reverted to draft.', 'modern-catholic-parish-events' ),
                'item_scheduled'           => __( 'Event scheduled.', 'modern-catholic-parish-events' ),
                'item_updated'             => __( 'Event updated.', 'modern-catholic-parish-events' ),
                'item_link'                => __( 'Event Link', 'modern-catholic-parish-events' ),
                'item_link_description'    => __( 'A link to an event.', 'modern-catholic-parish-events' ),
                'menu_name'                => __( 'Events', 'modern-catholic-parish-events' ),
                'name_admin_bar'           => __( 'Event', 'modern-catholic-parish-events' ),
            ),
            'public'            => true,
            'show_in_rest'      => true,
            'has_archive'       => 'events',
            'rewrite'           => array( 'slug' => 'events', 'with_front' => false ),
            'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'autosave' ),
            'menu_icon'         => 'dashicons-calendar-alt',
            'hierarchical'      => false,
            'capability_type'   => array( 'event', 'events' ),
            'map_meta_cap'      => true,
            'delete_with_user'  => false,
            'show_in_nav_menus' => true,
            'menu_position'     => 20,
            'rest_base'         => 'events',
            'template_lock'     => false,
        )
    );

    register_taxonomy(
        'mc_event_category',
        array( 'mc_event' ),
        array(
            'labels' => array(
                'name'                       => __( 'Event Categories', 'modern-catholic-parish-events' ),
                'singular_name'              => __( 'Event Category', 'modern-catholic-parish-events' ),
                'search_items'               => __( 'Search Event Categories', 'modern-catholic-parish-events' ),
                'popular_items'              => __( 'Popular Event Categories', 'modern-catholic-parish-events' ),
                'all_items'                  => __( 'All Event Categories', 'modern-catholic-parish-events' ),
                'parent_item'                => __( 'Parent Event Category', 'modern-catholic-parish-events' ),
                'parent_item_colon'          => __( 'Parent Event Category:', 'modern-catholic-parish-events' ),
                'edit_item'                  => __( 'Edit Event Category', 'modern-catholic-parish-events' ),
                'view_item'                  => __( 'View Event Category', 'modern-catholic-parish-events' ),
                'update_item'                => __( 'Update Event Category', 'modern-catholic-parish-events' ),
                'add_new_item'               => __( 'Add New Event Category', 'modern-catholic-parish-events' ),
                'new_item_name'              => __( 'New Event Category Name', 'modern-catholic-parish-events' ),
                'separate_items_with_commas' => __( 'Separate event categories with commas', 'modern-catholic-parish-events' ),
                'add_or_remove_items'        => __( 'Add or remove event categories', 'modern-catholic-parish-events' ),
                'choose_from_most_used'      => __( 'Choose from the most used event categories', 'modern-catholic-parish-events' ),
                'not_found'                  => __( 'No event categories found.', 'modern-catholic-parish-events' ),
                'no_terms'                   => __( 'No event categories', 'modern-catholic-parish-events' ),
                'filter_by_item'             => __( 'Filter by event category', 'modern-catholic-parish-events' ),
                'items_list_navigation'      => __( 'Event Categories list navigation', 'modern-catholic-parish-events' ),
                'items_list'                 => __( 'Event Categories list', 'modern-catholic-parish-events' ),
                'back_to_items'              => __( 'Back to Event Categories', 'modern-catholic-parish-events' ),
                'item_link'                  => __( 'Event Category Link', 'modern-catholic-parish-events' ),
                'item_link_description'      => __( 'A link to an event category.', 'modern-catholic-parish-events' ),
                'menu_name'                  => __( 'Event Categories', 'modern-catholic-parish-events' ),
            ),
            'public'             => true,
            'hierarchical'       => true,
            'show_in_rest'       => true,
            'show_admin_column'  => true,
            'show_in_quick_edit' => true,
            'rewrite'            => array( 'slug' => 'events/category', 'with_front' => false ),
            'rest_base'          => 'event-categories',
        )
    );

    modern_catholic_events_register_meta();
}
add_action( 'init', 'modern_catholic_events_register_content_types', 5 );

/**
 * Gives Event Category archives priority over the Event single attachment rules.
 */
function modern_catholic_events_add_category_rewrites() {
    add_rewrite_rule( '^events/category/([^/]+)/page/([0-9]+)/?$', 'index.php?mc_event_category=$matches[1]&paged=$matches[2]', 'top' );
    add_rewrite_rule( '^events/category/([^/]+)/?$', 'index.php?mc_event_category=$matches[1]', 'top' );
}
add_action( 'init', 'modern_catholic_events_add_category_rewrites', 10 );

/**
 * Returns metadata definitions shared by REST registration and the editor.
 *
 * @return array<string,array<string,mixed>>
 */
function modern_catholic_events_meta_definitions() {
    return array(
        '_mc_event_start_date'            => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
        '_mc_event_start_time'            => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_time' ),
        '_mc_event_end_date'              => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
        '_mc_event_end_time'              => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_time' ),
        '_mc_event_all_day'               => array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
        '_mc_event_status'                => array( 'type' => 'string', 'default' => 'scheduled', 'enum' => array( 'scheduled', 'canceled', 'postponed', 'rescheduled' ) ),
        '_mc_event_previous_start'        => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_datetime' ),
        '_mc_event_location_type'         => array( 'type' => 'string', 'default' => 'to_be_announced', 'enum' => array( 'in_person', 'online', 'hybrid', 'to_be_announced' ) ),
        '_mc_event_venue_name'            => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_formatted_address'     => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_street_address'        => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_address_locality'      => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_address_region'        => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_postal_code'           => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_address_country'       => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_google_place_id'       => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_latitude'              => array( 'type' => 'number', 'default' => 0, 'sanitize_callback' => 'modern_catholic_events_sanitize_latitude' ),
        '_mc_event_longitude'             => array( 'type' => 'number', 'default' => 0, 'sanitize_callback' => 'modern_catholic_events_sanitize_longitude' ),
        '_mc_event_online_url'            => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'esc_url_raw' ),
        '_mc_event_registration_url'      => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'esc_url_raw' ),
        '_mc_event_registration_label'    => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_registration_price'    => array( 'type' => 'number', 'default' => -1, 'sanitize_callback' => 'modern_catholic_events_sanitize_price' ),
        '_mc_event_registration_currency' => array( 'type' => 'string', 'default' => 'USD', 'sanitize_callback' => 'modern_catholic_events_sanitize_currency' ),
        '_mc_event_contact_name'          => array( 'type' => 'string', 'default' => '' ),
        '_mc_event_contact_email'         => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_email' ),
        '_mc_event_contact_phone'         => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_phone' ),
        '_mc_event_recurrence_frequency'  => array( 'type' => 'string', 'default' => 'none', 'enum' => array( 'none', 'daily', 'weekly', 'monthly', 'yearly' ) ),
        '_mc_event_recurrence_interval'   => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'maximum' => 999 ),
        '_mc_event_recurrence_weekdays'   => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'string', 'enum' => array( 'MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU' ) ), 'sanitize_callback' => 'modern_catholic_events_sanitize_weekdays' ),
        '_mc_event_monthly_mode'          => array( 'type' => 'string', 'default' => 'monthday', 'enum' => array( 'monthday', 'nth_weekday' ) ),
        '_mc_event_monthly_day'           => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'maximum' => 31 ),
        '_mc_event_monthly_week'          => array( 'type' => 'integer', 'default' => 1, 'enum' => array( 1, 2, 3, 4, -1 ) ),
        '_mc_event_monthly_weekday'       => array( 'type' => 'string', 'default' => 'MO', 'enum' => array( 'MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU' ) ),
        '_mc_event_recurrence_end_type'   => array( 'type' => 'string', 'default' => 'never', 'enum' => array( 'never', 'date', 'count' ) ),
        '_mc_event_recurrence_end_date'   => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_date' ),
        '_mc_event_recurrence_count'      => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'maximum' => 5000 ),
        '_mc_event_additional_dates'      => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'string', 'format' => 'date' ), 'sanitize_callback' => 'modern_catholic_events_sanitize_date_list' ),
        '_mc_event_excluded_dates'        => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'string', 'format' => 'date' ), 'sanitize_callback' => 'modern_catholic_events_sanitize_date_list' ),
        '_mc_event_series_uid'            => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
        '_mc_event_series_id'             => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
        '_mc_event_recurrence_id'         => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'modern_catholic_events_sanitize_datetime' ),
        '_mc_event_previous_series_id'    => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
        '_mc_event_sequence'              => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
    );
}
/**
 * Registers every event field with an explicit REST schema and authorization.
 */
function modern_catholic_events_register_meta() {
    foreach ( modern_catholic_events_meta_definitions() as $key => $definition ) {
        $schema = array( 'type' => $definition['type'], 'default' => $definition['default'] );
        foreach ( array( 'format', 'enum', 'minimum', 'maximum', 'items' ) as $schema_key ) {
            if ( isset( $definition[ $schema_key ] ) ) {
                $schema[ $schema_key ] = $definition[ $schema_key ];
            }
        }

        register_post_meta(
            'mc_event',
            $key,
            array(
                'type'              => $definition['type'],
                'single'            => true,
                'default'           => $definition['default'],
                'sanitize_callback' => isset( $definition['sanitize_callback'] ) ? $definition['sanitize_callback'] : 'sanitize_text_field',
                'auth_callback'     => 'modern_catholic_events_can_edit_meta',
                'show_in_rest'      => array( 'schema' => $schema ),
            )
        );
    }
}

function modern_catholic_events_can_edit_meta( $allowed, $meta_key, $post_id ) {
    unset( $allowed, $meta_key );
    return current_user_can( 'edit_post', $post_id );
}

function modern_catholic_events_sanitize_date( $value ) {
    $value = sanitize_text_field( $value );
    $date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
    return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
}

function modern_catholic_events_sanitize_time( $value ) {
    $value = sanitize_text_field( $value );
    return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
}

function modern_catholic_events_sanitize_datetime( $value ) {
    $value = sanitize_text_field( $value );
    return preg_match( '/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2})?$/', $value ) ? $value : '';
}

function modern_catholic_events_sanitize_latitude( $value ) {
    return max( -90, min( 90, (float) $value ) );
}

function modern_catholic_events_sanitize_longitude( $value ) {
    return max( -180, min( 180, (float) $value ) );
}

function modern_catholic_events_sanitize_price( $value ) {
    return max( -1, round( (float) $value, 2 ) );
}

function modern_catholic_events_sanitize_currency( $value ) {
    $value = strtoupper( sanitize_text_field( $value ) );
    return preg_match( '/^[A-Z]{3}$/', $value ) ? $value : 'USD';
}

function modern_catholic_events_sanitize_phone( $value ) {
    return preg_replace( '/[^0-9+().\- ext]/i', '', sanitize_text_field( $value ) );
}

function modern_catholic_events_sanitize_weekdays( $value ) {
    $allowed = array( 'MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU' );
    return array_values( array_unique( array_intersect( $allowed, array_map( 'strtoupper', (array) $value ) ) ) );
}

function modern_catholic_events_sanitize_date_list( $value ) {
    $dates = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
    $dates = array_filter( array_map( 'modern_catholic_events_sanitize_date', $dates ) );
    sort( $dates );
    return array_values( array_unique( $dates ) );
}
