<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds focused editor panels for event details.
 */
function modern_catholic_events_add_meta_boxes() {
    add_meta_box( 'modern-catholic-events-schedule', __( 'Event Schedule', 'modern-catholic-parish-events' ), 'modern_catholic_events_render_schedule_box', 'mc_event', 'normal', 'high' );
    add_meta_box( 'modern-catholic-events-location', __( 'Location', 'modern-catholic-parish-events' ), 'modern_catholic_events_render_location_box', 'mc_event', 'normal', 'default' );
    add_meta_box( 'modern-catholic-events-registration', __( 'Registration and Contact', 'modern-catholic-parish-events' ), 'modern_catholic_events_render_registration_box', 'mc_event', 'normal', 'default' );
    add_meta_box( 'modern-catholic-events-recurrence', __( 'Recurrence', 'modern-catholic-parish-events' ), 'modern_catholic_events_render_recurrence_box', 'mc_event', 'normal', 'default' );
}
add_action( 'add_meta_boxes_mc_event', 'modern_catholic_events_add_meta_boxes' );

function modern_catholic_events_admin_value( $post_id, $key ) {
    return modern_catholic_events_get_meta( $post_id, $key );
}

/**
 * Builds the canonical display address from its structured components.
 *
 * @param array $values Event values keyed without the _mc_event_ prefix.
 * @return string
 */
function modern_catholic_events_derive_formatted_address( $values ) {
    $street   = sanitize_text_field( $values['street_address'] ?? '' );
    $locality = sanitize_text_field( $values['address_locality'] ?? '' );
    $region   = sanitize_text_field( $values['address_region'] ?? '' );
    $postal   = sanitize_text_field( $values['postal_code'] ?? '' );
    $country  = sanitize_text_field( $values['address_country'] ?? '' );

    $region_postal = trim( implode( ' ', array_filter( array( $region, $postal ) ) ) );
    $locality_line = implode( ', ', array_filter( array( $locality, $region_postal ) ) );

    return implode( ', ', array_filter( array( $street, $locality_line, $country ) ) );
}

function modern_catholic_events_input( $post_id, $key, $label, $type = 'text', $attributes = array() ) {
    $value = modern_catholic_events_admin_value( $post_id, $key );
    $id    = 'modern-catholic-event-' . str_replace( '_', '-', $key );
    echo '<p class="modern-catholic-events-field">';
    echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
    echo '<input id="' . esc_attr( $id ) . '" name="modern_catholic_events[' . esc_attr( $key ) . ']" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '"';
    foreach ( $attributes as $attribute => $attribute_value ) {
        echo ' ' . esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
    }
    echo '></p>';
}

function modern_catholic_events_select( $post_id, $key, $label, $options ) {
    $value = modern_catholic_events_admin_value( $post_id, $key );
    $id    = 'modern-catholic-event-' . str_replace( '_', '-', $key );
    echo '<p class="modern-catholic-events-field">';
    echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
    echo '<select id="' . esc_attr( $id ) . '" name="modern_catholic_events[' . esc_attr( $key ) . ']">';
    foreach ( $options as $option_value => $option_label ) {
        echo '<option value="' . esc_attr( $option_value ) . '"' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
    }
    echo '</select></p>';
}

/**
 * Renders date, time, all-day, and status controls.
 */
function modern_catholic_events_render_schedule_box( $post ) {
    wp_nonce_field( 'modern_catholic_events_save', 'modern_catholic_events_nonce' );
    ?>
    <div class="modern-catholic-events-fields modern-catholic-events-fields--schedule">
        <?php modern_catholic_events_input( $post->ID, 'start_date', __( 'Start date', 'modern-catholic-parish-events' ), 'date', array( 'required' => 'required' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'start_time', __( 'Start time', 'modern-catholic-parish-events' ), 'time', array( 'step' => '300' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'end_date', __( 'End date', 'modern-catholic-parish-events' ), 'date' ); ?>
        <?php modern_catholic_events_input( $post->ID, 'end_time', __( 'End time', 'modern-catholic-parish-events' ), 'time', array( 'step' => '300' ) ); ?>
    </div>
    <p>
        <label><input id="modern-catholic-event-all-day" name="modern_catholic_events[all_day]" type="checkbox" value="1" <?php checked( modern_catholic_events_admin_value( $post->ID, 'all_day' ) ); ?>> <strong><?php esc_html_e( 'All-day event', 'modern-catholic-parish-events' ); ?></strong></label>
    </p>
    <div class="modern-catholic-events-fields">
        <?php
        modern_catholic_events_select(
            $post->ID,
            'status',
            __( 'Event status', 'modern-catholic-parish-events' ),
            array(
                'scheduled'   => __( 'Scheduled', 'modern-catholic-parish-events' ),
                'canceled'    => __( 'Canceled', 'modern-catholic-parish-events' ),
                'postponed'   => __( 'Postponed', 'modern-catholic-parish-events' ),
                'rescheduled' => __( 'Rescheduled', 'modern-catholic-parish-events' ),
            )
        );
        modern_catholic_events_input( $post->ID, 'previous_start', __( 'Previous start (when rescheduled)', 'modern-catholic-parish-events' ), 'datetime-local' );
        ?>
    </div>
    <p class="description"><?php esc_html_e( 'Date and time fields accept both picker selection and direct keyboard entry. Times use the WordPress site timezone.', 'modern-catholic-parish-events' ); ?></p>
    <?php
}

/**
 * Renders location fields with manual entry available at all times.
 */
function modern_catholic_events_render_location_box( $post ) {
    modern_catholic_events_select(
        $post->ID,
        'location_type',
        __( 'Location type', 'modern-catholic-parish-events' ),
        array(
            'in_person'       => __( 'In person', 'modern-catholic-parish-events' ),
            'online'          => __( 'Online', 'modern-catholic-parish-events' ),
            'hybrid'          => __( 'Hybrid', 'modern-catholic-parish-events' ),
            'to_be_announced' => __( 'To be announced', 'modern-catholic-parish-events' ),
        )
    );
    ?>
    <div id="modern-catholic-events-places" class="modern-catholic-events-places" data-placeholder="<?php esc_attr_e( 'Search for a venue or address', 'modern-catholic-parish-events' ); ?>"></div>
    <p class="description"><?php esc_html_e( 'Autocomplete is optional. All address fields remain available for manual entry.', 'modern-catholic-parish-events' ); ?></p>
    <div class="modern-catholic-events-fields modern-catholic-events-fields--location">
        <?php modern_catholic_events_input( $post->ID, 'venue_name', __( 'Venue name', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'street_address', __( 'Street address', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'address_locality', __( 'City or locality', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'address_region', __( 'State or region', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'postal_code', __( 'Postal code', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'address_country', __( 'Country', 'modern-catholic-parish-events' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'formatted_address', __( 'Formatted address (generated)', 'modern-catholic-parish-events' ), 'text', array( 'readonly' => 'readonly', 'aria-describedby' => 'modern-catholic-events-formatted-address-help' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'google_place_id', __( 'Google Place ID', 'modern-catholic-parish-events' ), 'text', array( 'readonly' => 'readonly' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'latitude', __( 'Latitude', 'modern-catholic-parish-events' ), 'number', array( 'step' => 'any', 'min' => '-90', 'max' => '90' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'longitude', __( 'Longitude', 'modern-catholic-parish-events' ), 'number', array( 'step' => 'any', 'min' => '-180', 'max' => '180' ) ); ?>
        <?php modern_catholic_events_input( $post->ID, 'online_url', __( 'Online-event URL', 'modern-catholic-parish-events' ), 'url' ); ?>
    </div>
    <p id="modern-catholic-events-formatted-address-help" class="description"><?php esc_html_e( 'The formatted address and public Google Maps link are generated automatically from the structured address or Google Places result.', 'modern-catholic-parish-events' ); ?></p>
    <?php
}

/**
 * Renders registration and public contact fields.
 */
function modern_catholic_events_render_registration_box( $post ) {
    echo '<div class="modern-catholic-events-fields">';
    modern_catholic_events_input( $post->ID, 'registration_url', __( 'Registration URL', 'modern-catholic-parish-events' ), 'url' );
    modern_catholic_events_input( $post->ID, 'registration_label', __( 'Registration button label', 'modern-catholic-parish-events' ) );
    modern_catholic_events_input( $post->ID, 'registration_price', __( 'Registration price (-1 when unknown)', 'modern-catholic-parish-events' ), 'number', array( 'step' => '0.01', 'min' => '-1' ) );
    modern_catholic_events_input( $post->ID, 'registration_currency', __( 'Currency', 'modern-catholic-parish-events' ), 'text', array( 'maxlength' => '3' ) );
    modern_catholic_events_input( $post->ID, 'contact_name', __( 'Contact name', 'modern-catholic-parish-events' ) );
    modern_catholic_events_input( $post->ID, 'contact_email', __( 'Contact email', 'modern-catholic-parish-events' ), 'email' );
    modern_catholic_events_input( $post->ID, 'contact_phone', __( 'Contact phone', 'modern-catholic-parish-events' ), 'tel' );
    echo '</div>';
}

/**
 * Renders RFC 5545-style recurrence controls.
 */
function modern_catholic_events_render_recurrence_box( $post ) {
    modern_catholic_events_select(
        $post->ID,
        'recurrence_frequency',
        __( 'Repeats', 'modern-catholic-parish-events' ),
        array(
            'none'    => __( 'Does not repeat', 'modern-catholic-parish-events' ),
            'daily'   => __( 'Daily', 'modern-catholic-parish-events' ),
            'weekly'  => __( 'Weekly', 'modern-catholic-parish-events' ),
            'monthly' => __( 'Monthly', 'modern-catholic-parish-events' ),
            'yearly'  => __( 'Yearly', 'modern-catholic-parish-events' ),
        )
    );
    echo '<div data-modern-catholic-events-recurrence-section="repeating">';
    modern_catholic_events_input( $post->ID, 'recurrence_interval', __( 'Repeat interval', 'modern-catholic-parish-events' ), 'number', array( 'min' => '1', 'max' => '999' ) );

    $selected_weekdays = modern_catholic_events_sanitize_weekdays( modern_catholic_events_admin_value( $post->ID, 'recurrence_weekdays' ) );
    $weekdays = array( 'MO' => __( 'Monday', 'modern-catholic-parish-events' ), 'TU' => __( 'Tuesday', 'modern-catholic-parish-events' ), 'WE' => __( 'Wednesday', 'modern-catholic-parish-events' ), 'TH' => __( 'Thursday', 'modern-catholic-parish-events' ), 'FR' => __( 'Friday', 'modern-catholic-parish-events' ), 'SA' => __( 'Saturday', 'modern-catholic-parish-events' ), 'SU' => __( 'Sunday', 'modern-catholic-parish-events' ) );
    echo '<div data-modern-catholic-events-recurrence-section="weekly">';
    echo '<fieldset class="modern-catholic-events-weekdays"><legend><strong>' . esc_html__( 'Weekly days', 'modern-catholic-parish-events' ) . '</strong></legend>';
    foreach ( $weekdays as $code => $label ) {
        echo '<label><input type="checkbox" name="modern_catholic_events[recurrence_weekdays][]" value="' . esc_attr( $code ) . '"' . checked( in_array( $code, $selected_weekdays, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
    }
    echo '</fieldset></div>';

    echo '<div data-modern-catholic-events-recurrence-section="monthly">';
    echo '<div class="modern-catholic-events-fields">';
    modern_catholic_events_select( $post->ID, 'monthly_mode', __( 'Monthly pattern', 'modern-catholic-parish-events' ), array( 'monthday' => __( 'On a numerical date', 'modern-catholic-parish-events' ), 'nth_weekday' => __( 'On a numbered weekday', 'modern-catholic-parish-events' ) ) );
    echo '<div data-modern-catholic-events-monthly-section="monthday">';
    modern_catholic_events_input( $post->ID, 'monthly_day', __( 'Day of month', 'modern-catholic-parish-events' ), 'number', array( 'min' => '1', 'max' => '31' ) );
    echo '</div><div data-modern-catholic-events-monthly-section="nth_weekday">';
    modern_catholic_events_select( $post->ID, 'monthly_week', __( 'Week of month', 'modern-catholic-parish-events' ), array( 1 => __( 'First', 'modern-catholic-parish-events' ), 2 => __( 'Second', 'modern-catholic-parish-events' ), 3 => __( 'Third', 'modern-catholic-parish-events' ), 4 => __( 'Fourth', 'modern-catholic-parish-events' ), -1 => __( 'Last', 'modern-catholic-parish-events' ) ) );
    modern_catholic_events_select( $post->ID, 'monthly_weekday', __( 'Weekday', 'modern-catholic-parish-events' ), $weekdays );
    echo '</div></div></div>';

    echo '<div class="modern-catholic-events-fields modern-catholic-events-recurrence-ending">';
    modern_catholic_events_select( $post->ID, 'recurrence_end_type', __( 'Recurrence ends', 'modern-catholic-parish-events' ), array( 'never' => __( 'Never', 'modern-catholic-parish-events' ), 'date' => __( 'On an inclusive date', 'modern-catholic-parish-events' ), 'count' => __( 'After a number of occurrences', 'modern-catholic-parish-events' ) ) );
    echo '<div data-modern-catholic-events-ending-section="date">';
    modern_catholic_events_input( $post->ID, 'recurrence_end_date', __( 'Inclusive end date', 'modern-catholic-parish-events' ), 'date' );
    echo '</div><div data-modern-catholic-events-ending-section="count">';
    modern_catholic_events_input( $post->ID, 'recurrence_count', __( 'Occurrence count', 'modern-catholic-parish-events' ), 'number', array( 'min' => '1', 'max' => '5000' ) );
    echo '</div></div>';

    $additional = implode( "\n", modern_catholic_events_sanitize_date_list( modern_catholic_events_admin_value( $post->ID, 'additional_dates' ) ) );
    $excluded   = implode( "\n", modern_catholic_events_sanitize_date_list( modern_catholic_events_admin_value( $post->ID, 'excluded_dates' ) ) );
    ?>
    <details class="modern-catholic-events-recurrence-advanced">
        <summary><?php esc_html_e( 'Advanced recurrence options', 'modern-catholic-parish-events' ); ?></summary>
        <div class="modern-catholic-events-fields">
            <p class="modern-catholic-events-field"><label for="modern-catholic-event-additional-dates"><strong><?php esc_html_e( 'Additional occurrence dates', 'modern-catholic-parish-events' ); ?></strong></label><textarea id="modern-catholic-event-additional-dates" name="modern_catholic_events[additional_dates]" rows="4" placeholder="YYYY-MM-DD"><?php echo esc_textarea( $additional ); ?></textarea></p>
            <p class="modern-catholic-events-field"><label for="modern-catholic-event-excluded-dates"><strong><?php esc_html_e( 'Excluded dates', 'modern-catholic-parish-events' ); ?></strong></label><textarea id="modern-catholic-event-excluded-dates" name="modern_catholic_events[excluded_dates]" rows="4" placeholder="YYYY-MM-DD"><?php echo esc_textarea( $excluded ); ?></textarea></p>
        </div>
        <p class="description"><?php esc_html_e( 'Enter one date per line. Recurrence expansion is always bounded by the requesting calendar or feed range.', 'modern-catholic-parish-events' ); ?></p>
    </details>
    </div>
    <?php
}

/**
 * Sanitizes a submitted value according to its registered definition.
 */
function modern_catholic_events_sanitize_submitted_value( $value, $definition ) {
    if ( isset( $definition['sanitize_callback'] ) && is_callable( $definition['sanitize_callback'] ) ) {
        $value = call_user_func( $definition['sanitize_callback'], $value );
    } elseif ( 'integer' === $definition['type'] ) {
        $value = (int) $value;
    } elseif ( 'number' === $definition['type'] ) {
        $value = (float) $value;
    } else {
        $value = sanitize_text_field( $value );
    }
    if ( isset( $definition['enum'] ) && ! in_array( $value, $definition['enum'], true ) ) {
        return $definition['default'];
    }
    if ( 'integer' === $definition['type'] ) {
        $value = (int) $value;
        if ( isset( $definition['minimum'] ) ) {
            $value = max( $definition['minimum'], $value );
        }
        if ( isset( $definition['maximum'] ) ) {
            $value = min( $definition['maximum'], $value );
        }
    }
    return $value;
}

/**
 * Validates cross-field schedule constraints.
 *
 * @param array $values Normalized event fields.
 * @return WP_Error|true
 */
function modern_catholic_events_validate_schedule( $values ) {
    if ( empty( $values['start_date'] ) ) {
        return new WP_Error( 'missing_start', __( 'A valid start date is required.', 'modern-catholic-parish-events' ) );
    }

    $all_day    = ! empty( $values['all_day'] );
    $start_time = $all_day ? '00:00' : ( $values['start_time'] ?: '00:00' );
    $end_date   = $values['end_date'] ?: $values['start_date'];
    $end_time   = $all_day ? '23:59' : ( $values['end_time'] ?: $start_time );
    $start      = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $values['start_date'] . ' ' . $start_time, wp_timezone() );
    $end        = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $end_date . ' ' . $end_time, wp_timezone() );

    if ( ! $start || ! $end || $end < $start ) {
        return new WP_Error( 'invalid_end', __( 'The event end must not precede its start.', 'modern-catholic-parish-events' ) );
    }
    if ( 'date' === $values['recurrence_end_type'] && $values['recurrence_end_date'] && $values['recurrence_end_date'] < $values['start_date'] ) {
        return new WP_Error( 'invalid_recurrence_end', __( 'The recurrence end date must not precede the first occurrence.', 'modern-catholic-parish-events' ) );
    }
    return true;
}

/**
 * Saves editor metadata with nonce, capability, and cross-field validation.
 */
function modern_catholic_events_save_meta( $post_id, $post ) {
    if ( ! isset( $_POST['modern_catholic_events_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['modern_catholic_events_nonce'] ) ), 'modern_catholic_events_save' ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }
    if ( ! $post || 'mc_event' !== $post->post_type ) {
        return;
    }

    $submitted  = isset( $_POST['modern_catholic_events'] ) && is_array( $_POST['modern_catholic_events'] ) ? wp_unslash( $_POST['modern_catholic_events'] ) : array();
    $definitions = modern_catholic_events_meta_definitions();
    $values       = array();
    $editable     = array_diff_key( $definitions, array_flip( array( '_mc_event_series_uid', '_mc_event_series_id', '_mc_event_recurrence_id', '_mc_event_previous_series_id', '_mc_event_sequence' ) ) );

    foreach ( $editable as $meta_key => $definition ) {
        $key = substr( $meta_key, strlen( '_mc_event_' ) );
        if ( 'all_day' === $key ) {
            $raw = isset( $submitted[ $key ] );
        } elseif ( 'recurrence_weekdays' === $key ) {
            $raw = isset( $submitted[ $key ] ) ? (array) $submitted[ $key ] : array();
        } else {
            $raw = isset( $submitted[ $key ] ) ? $submitted[ $key ] : $definition['default'];
        }
        $values[ $key ] = modern_catholic_events_sanitize_submitted_value( $raw, $definition );
    }

    $validation = modern_catholic_events_validate_schedule( $values );
    if ( is_wp_error( $validation ) ) {
        set_transient( 'modern_catholic_events_notice_' . get_current_user_id(), $validation->get_error_message(), MINUTE_IN_SECONDS );
        return;
    }

    $values['end_date']         = $values['end_date'] ?: $values['start_date'];
    $values['formatted_address'] = modern_catholic_events_derive_formatted_address( $values );
    foreach ( $values as $key => $value ) {
        update_post_meta( $post_id, '_mc_event_' . $key, $value );
    }
}
add_action( 'save_post_mc_event', 'modern_catholic_events_save_meta', 20, 2 );

/**
 * Shows validation failures without exposing submitted secrets.
 */
function modern_catholic_events_admin_notices() {
    $key     = 'modern_catholic_events_notice_' . get_current_user_id();
    $message = get_transient( $key );
    if ( $message ) {
        delete_transient( $key );
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'modern_catholic_events_admin_notices' );

/**
 * Validates schedule meta in direct REST writes.
 */
function modern_catholic_events_validate_rest_request( $prepared_post, $request ) {
    $meta = $request->get_param( 'meta' );
    if ( ! is_array( $meta ) ) {
        return $prepared_post;
    }
    $values = array();
    foreach ( modern_catholic_events_meta_definitions() as $key => $definition ) {
        $short_key = substr( $key, strlen( '_mc_event_' ) );
        $raw       = array_key_exists( $key, $meta ) ? $meta[ $key ] : ( $prepared_post->ID ? get_post_meta( $prepared_post->ID, $key, true ) : $definition['default'] );
        $values[ $short_key ] = modern_catholic_events_sanitize_submitted_value( $raw, $definition );
    }
    $validation = modern_catholic_events_validate_schedule( $values );
    return is_wp_error( $validation ) ? $validation : $prepared_post;
}
add_filter( 'rest_pre_insert_mc_event', 'modern_catholic_events_validate_rest_request', 10, 2 );

/**
 * Keeps REST-created and REST-updated events on the same derived-address path.
 */
function modern_catholic_events_sync_rest_formatted_address( $post ) {
    if ( ! $post instanceof WP_Post || 'mc_event' !== $post->post_type ) {
        return;
    }

    $values = array();
    foreach ( array( 'street_address', 'address_locality', 'address_region', 'postal_code', 'address_country' ) as $key ) {
        $values[ $key ] = modern_catholic_events_get_meta( $post->ID, $key );
    }
    update_post_meta( $post->ID, '_mc_event_formatted_address', modern_catholic_events_derive_formatted_address( $values ) );
}
add_action( 'rest_after_insert_mc_event', 'modern_catholic_events_sync_rest_formatted_address' );

/**
 * Registers the administrator-only Google Places setting.
 */
function modern_catholic_events_register_settings() {
    register_setting(
        'modern_catholic_events_settings',
        'modern_catholic_events_google_places_api_key',
        array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '', 'show_in_rest' => false )
    );
    add_settings_section( 'modern_catholic_events_google', __( 'Google Places', 'modern-catholic-parish-events' ), '__return_false', 'modern_catholic_events_settings' );
    add_settings_field( 'modern_catholic_events_google_places_api_key', __( 'Maps JavaScript API key', 'modern-catholic-parish-events' ), 'modern_catholic_events_render_api_key_setting', 'modern_catholic_events_settings', 'modern_catholic_events_google' );
}
add_action( 'admin_init', 'modern_catholic_events_register_settings' );

function modern_catholic_events_add_settings_page() {
    add_submenu_page( 'edit.php?post_type=mc_event', __( 'Events Settings', 'modern-catholic-parish-events' ), __( 'Settings', 'modern-catholic-parish-events' ), 'manage_options', 'modern-catholic-events-settings', 'modern_catholic_events_render_settings_page' );
}
add_action( 'admin_menu', 'modern_catholic_events_add_settings_page' );

function modern_catholic_events_render_api_key_setting() {
    $value = get_option( 'modern_catholic_events_google_places_api_key', '' );
    echo '<input class="regular-text" type="password" autocomplete="new-password" name="modern_catholic_events_google_places_api_key" value="' . esc_attr( $value ) . '">';
    echo '<p class="description">' . esc_html__( 'Restrict this browser key to the site domain and enable Places API (New). Events remain fully editable without a key.', 'modern-catholic-parish-events' ) . '</p>';
}

function modern_catholic_events_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    echo '<div class="wrap"><h1>' . esc_html__( 'Events Settings', 'modern-catholic-parish-events' ) . '</h1><form method="post" action="options.php">';
    settings_fields( 'modern_catholic_events_settings' );
    do_settings_sections( 'modern_catholic_events_settings' );
    submit_button();
    echo '</form></div>';
}

/**
 * Performs an administrator-requested occurrence edit scope action.
 */
function modern_catholic_events_handle_edit_occurrence() {
    $series_id     = isset( $_GET['series_id'] ) ? absint( $_GET['series_id'] ) : 0;
    $recurrence_id = isset( $_GET['recurrence_id'] ) ? modern_catholic_events_sanitize_datetime( wp_unslash( $_GET['recurrence_id'] ) ) : '';
    $scope         = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : '';
    check_admin_referer( 'modern_catholic_events_edit_occurrence_' . $series_id . '_' . $recurrence_id );
    if ( ! $series_id || ! $recurrence_id || ! current_user_can( 'edit_post', $series_id ) ) {
        wp_die( esc_html__( 'You are not allowed to edit this occurrence.', 'modern-catholic-parish-events' ) );
    }

    if ( 'occurrence' === $scope ) {
        $target = modern_catholic_events_create_override( $series_id, $recurrence_id );
    } elseif ( 'following' === $scope ) {
        $target = modern_catholic_events_split_series( $series_id, $recurrence_id );
    } elseif ( 'series' === $scope ) {
        $target = $series_id;
    } else {
        $target = new WP_Error( 'invalid_scope', __( 'Choose a valid editing scope.', 'modern-catholic-parish-events' ) );
    }

    if ( is_wp_error( $target ) ) {
        wp_die( esc_html( $target->get_error_message() ) );
    }
    wp_safe_redirect( get_edit_post_link( $target, 'raw' ) );
    exit;
}
add_action( 'admin_post_modern_catholic_events_edit_occurrence', 'modern_catholic_events_handle_edit_occurrence' );

/**
 * Creates a nonce-protected URL for an occurrence edit scope.
 */
function modern_catholic_events_edit_occurrence_url( $occurrence, $scope ) {
    $url = add_query_arg(
        array(
            'action'        => 'modern_catholic_events_edit_occurrence',
            'series_id'     => $occurrence['series_id'],
            'recurrence_id' => $occurrence['recurrence_id'],
            'scope'         => $scope,
        ),
        admin_url( 'admin-post.php' )
    );
    return wp_nonce_url( $url, 'modern_catholic_events_edit_occurrence_' . $occurrence['series_id'] . '_' . $occurrence['recurrence_id'] );
}
