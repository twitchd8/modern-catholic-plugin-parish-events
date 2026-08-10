<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function parishpress_events_add_meta_box() {
    add_meta_box(
        'parishpress_event_details',
        __( 'Event Details', 'parishpress-events' ),
        'parishpress_events_render_meta_box',
        'mc_event',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'parishpress_events_add_meta_box' );

function parishpress_events_render_meta_box( $post ) {
    wp_nonce_field( 'parishpress_event_details', 'parishpress_events_nonce' );

    $start = get_post_meta( $post->ID, '_pp_event_start', true );
    $end   = get_post_meta( $post->ID, '_pp_event_end', true );
    $loc   = get_post_meta( $post->ID, '_pp_event_location', true );
    ?>
    <p>
        <label for="pp_event_start"><strong><?php esc_html_e( 'Start Date/Time', 'parishpress-events' ); ?></strong></label><br>
        <input type="datetime-local" id="pp_event_start" name="pp_event_start" value="<?php echo esc_attr( $start ); ?>" class="regular-text" />
    </p>
    <p>
        <label for="pp_event_end"><strong><?php esc_html_e( 'End Date/Time', 'parishpress-events' ); ?></strong></label><br>
        <input type="datetime-local" id="pp_event_end" name="pp_event_end" value="<?php echo esc_attr( $end ); ?>" class="regular-text" />
    </p>
    <p>
        <label for="pp_event_location"><strong><?php esc_html_e( 'Location', 'parishpress-events' ); ?></strong></label><br>
        <input type="text" id="pp_event_location" name="pp_event_location" value="<?php echo esc_attr( $loc ); ?>" class="regular-text" />
    </p>
    <?php
}

function parishpress_events_save_meta( $post_id ) {
    if ( ! isset( $_POST['parishpress_events_nonce'] ) ||
         ! wp_verify_nonce( $_POST['parishpress_events_nonce'], 'parishpress_event_details' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $map = array(
        '_pp_event_start'    => 'pp_event_start',
        '_pp_event_end'      => 'pp_event_end',
        '_pp_event_location' => 'pp_event_location',
    );

    foreach ( $map as $meta_key => $field_key ) {
        if ( isset( $_POST[ $field_key ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_mc_event', 'parishpress_events_save_meta' );
