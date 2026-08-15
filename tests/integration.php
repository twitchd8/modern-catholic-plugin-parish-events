<?php
/**
 * LocalWP integration runner. Creates and removes temporary WordPress fixtures.
 *
 * Usage: php -c <active-php.ini> tests/integration.php <wordpress-public-root>
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

$wordpress_root = isset( $argv[1] ) ? rtrim( $argv[1], '/\\' ) : '';
if ( ! $wordpress_root || ! file_exists( $wordpress_root . '/wp-load.php' ) ) {
    fwrite( STDERR, "Pass the WordPress public root as the first argument.\n" );
    exit( 1 );
}

require $wordpress_root . '/wp-load.php';

$modern_catholic_events_test_posts = array();
$modern_catholic_events_test_terms = array();
$modern_catholic_events_test_passed = 0;
$modern_catholic_events_test_failed = 0;

function modern_catholic_events_test_assert( $condition, $label ) {
    global $modern_catholic_events_test_passed, $modern_catholic_events_test_failed;
    if ( $condition ) {
        ++$modern_catholic_events_test_passed;
        echo "PASS: {$label}\n";
    } else {
        ++$modern_catholic_events_test_failed;
        echo "FAIL: {$label}\n";
    }
}

function modern_catholic_events_test_create( $title, $meta, $term_ids = array() ) {
    global $modern_catholic_events_test_posts;
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'mc_event',
            'post_status'  => 'publish',
            'post_title'   => '[Events Test] ' . $title,
            'post_content' => 'Temporary event details for automated validation.',
            'post_excerpt' => 'Temporary event excerpt.',
        ),
        true
    );
    if ( is_wp_error( $post_id ) ) {
        throw new RuntimeException( $post_id->get_error_message() );
    }
    $modern_catholic_events_test_posts[] = $post_id;
    foreach ( $meta as $key => $value ) {
        update_post_meta( $post_id, '_mc_event_' . $key, $value );
    }
    if ( $term_ids ) {
        wp_set_object_terms( $post_id, array_map( 'intval', $term_ids ), 'mc_event_category' );
    }
    modern_catholic_events_invalidate_cache();
    return $post_id;
}

function modern_catholic_events_test_range( $start, $end, $args = array() ) {
    return modern_catholic_events_get_occurrences( $start . ' 00:00:00', $end . ' 23:59:59', $args );
}

function modern_catholic_events_test_for_series( $items, $series_id ) {
    return array_values( array_filter( $items, static function ( $item ) use ( $series_id ) { return (int) $item['series_id'] === (int) $series_id; } ) );
}

try {
    $administrator_ids = get_users( array( 'role' => 'administrator', 'fields' => 'ids', 'number' => 1 ) );
    if ( $administrator_ids ) {
        wp_set_current_user( $administrator_ids[0] );
    }

    $category_a = wp_insert_term( '[Events Test] Formation', 'mc_event_category', array( 'slug' => 'events-test-formation' ) );
    $category_b = wp_insert_term( '[Events Test] Community', 'mc_event_category', array( 'slug' => 'events-test-community' ) );
    if ( is_wp_error( $category_a ) || is_wp_error( $category_b ) ) {
        throw new RuntimeException( 'Could not create temporary Event Categories.' );
    }
    $modern_catholic_events_test_terms[] = $category_a['term_id'];
    $modern_catholic_events_test_terms[] = $category_b['term_id'];

    $base_meta = array(
        'start_date' => '2026-09-01', 'start_time' => '18:00', 'end_date' => '2026-09-01', 'end_time' => '20:00',
        'all_day' => false, 'status' => 'scheduled', 'location_type' => 'in_person', 'venue_name' => 'Test Hall',
        'recurrence_frequency' => 'none', 'recurrence_interval' => 1, 'recurrence_end_type' => 'never',
        'series_uid' => wp_generate_uuid4(), 'sequence' => 3,
    );

    $one_time = modern_catholic_events_test_create( 'One-time timed event', $base_meta, array( $category_a['term_id'] ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-02' ), $one_time );
    modern_catholic_events_test_assert( 1 === count( $items ) && '18:00' === $items[0]['start']->format( 'H:i' ) && '20:00' === $items[0]['end']->format( 'H:i' ), 'one-time timed event' );

    $derived_address = modern_catholic_events_derive_formatted_address( array( 'street_address' => '410 S. State St.', 'address_locality' => 'Litchfield', 'address_region' => 'IL', 'postal_code' => '62056', 'address_country' => 'United States' ) );
    modern_catholic_events_test_assert( '410 S. State St., Litchfield, IL 62056, United States' === $derived_address, 'formatted address is derived from structured address components' );
    foreach ( array( 'street_address' => '410 S. State St.', 'address_locality' => 'Litchfield', 'address_region' => 'IL', 'postal_code' => '62056', 'address_country' => 'United States' ) as $address_key => $address_value ) {
        update_post_meta( $one_time, '_mc_event_' . $address_key, $address_value );
    }
    modern_catholic_events_sync_rest_formatted_address( get_post( $one_time ) );
    modern_catholic_events_test_assert( $derived_address === modern_catholic_events_get_meta( $one_time, 'formatted_address' ), 'REST address synchronization stores the derived formatted address' );

    $all_day_meta = array_merge( $base_meta, array( 'start_date' => '2026-09-03', 'end_date' => '2026-09-04', 'all_day' => true, 'series_uid' => wp_generate_uuid4() ) );
    $all_day = modern_catholic_events_test_create( 'All-day event', $all_day_meta );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-03', '2026-09-05' ), $all_day );
    modern_catholic_events_test_assert( 1 === count( $items ) && $items[0]['all_day'] && '2026-09-05' === $items[0]['end']->format( 'Y-m-d' ), 'multi-day all-day event uses exclusive internal end' );

    $daily = modern_catholic_events_test_create( 'Daily recurrence', array_merge( $base_meta, array( 'start_date' => '2026-09-01', 'end_date' => '2026-09-01', 'recurrence_frequency' => 'daily', 'recurrence_end_type' => 'count', 'recurrence_count' => 3, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-10' ), $daily );
    modern_catholic_events_test_assert( 3 === count( $items ), 'daily recurrence finite by count' );

    $interval = modern_catholic_events_test_create( 'Interval recurrence', array_merge( $base_meta, array( 'recurrence_frequency' => 'daily', 'recurrence_interval' => 2, 'recurrence_end_type' => 'count', 'recurrence_count' => 3, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-10' ), $interval );
    modern_catholic_events_test_assert( array( '2026-09-01', '2026-09-03', '2026-09-05' ) === array_map( static function ( $item ) { return $item['start']->format( 'Y-m-d' ); }, $items ), 'custom interval recurrence' );

    $weekly = modern_catholic_events_test_create( 'Weekly recurrence', array_merge( $base_meta, array( 'recurrence_frequency' => 'weekly', 'recurrence_weekdays' => array( 'TU', 'TH' ), 'recurrence_end_type' => 'count', 'recurrence_count' => 4, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-15' ), $weekly );
    modern_catholic_events_test_assert( 4 === count( $items ) && '2026-09-03' === $items[1]['start']->format( 'Y-m-d' ), 'weekly selected days' );
    ob_start();
    modern_catholic_events_render_recurrence_box( get_post( $weekly ) );
    $recurrence_markup = ob_get_clean();
    modern_catholic_events_test_assert( false !== strpos( $recurrence_markup, 'data-modern-catholic-events-recurrence-section="weekly"' ) && false !== strpos( $recurrence_markup, 'data-modern-catholic-events-monthly-section="monthday"' ) && false !== strpos( $recurrence_markup, 'data-modern-catholic-events-ending-section="count"' ) && false !== strpos( $recurrence_markup, '<details class="modern-catholic-events-recurrence-advanced">' ), 'recurrence editor exposes progressive-disclosure targets and advanced exceptions' );

    $monthly = modern_catholic_events_test_create( 'Monthly recurrence', array_merge( $base_meta, array( 'start_date' => '2026-09-15', 'end_date' => '2026-09-15', 'recurrence_frequency' => 'monthly', 'monthly_mode' => 'monthday', 'monthly_day' => 15, 'recurrence_end_type' => 'count', 'recurrence_count' => 3, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-12-31' ), $monthly );
    modern_catholic_events_test_assert( array( '2026-09-15', '2026-10-15', '2026-11-15' ) === array_map( static function ( $item ) { return $item['start']->format( 'Y-m-d' ); }, $items ), 'monthly numerical-date recurrence' );

    $monthly_nth = modern_catholic_events_test_create( 'Monthly nth weekday', array_merge( $base_meta, array( 'start_date' => '2026-09-14', 'end_date' => '2026-09-14', 'recurrence_frequency' => 'monthly', 'monthly_mode' => 'nth_weekday', 'monthly_week' => 2, 'monthly_weekday' => 'MO', 'recurrence_end_type' => 'count', 'recurrence_count' => 2, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-11-30' ), $monthly_nth );
    modern_catholic_events_test_assert( array( '2026-09-14', '2026-10-12' ) === array_map( static function ( $item ) { return $item['start']->format( 'Y-m-d' ); }, $items ), 'monthly numbered-weekday recurrence' );

    $yearly = modern_catholic_events_test_create( 'Yearly recurrence', array_merge( $base_meta, array( 'start_date' => '2026-09-01', 'end_date' => '2026-09-01', 'recurrence_frequency' => 'yearly', 'recurrence_end_type' => 'count', 'recurrence_count' => 2, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-01-01', '2027-12-31' ), $yearly );
    modern_catholic_events_test_assert( 2 === count( $items ) && '2027-09-01' === $items[1]['start']->format( 'Y-m-d' ), 'yearly recurrence' );
    ob_start();
    modern_catholic_events_render_list( $items );
    $year_list_markup = ob_get_clean();
    modern_catholic_events_test_assert( 2 === substr_count( $year_list_markup, 'modern-catholic-events-list__year"' ) && strpos( $year_list_markup, '>2026</h2>' ) < strpos( $year_list_markup, 'Yearly recurrence' ) && false !== strpos( $year_list_markup, '>2027</h2>' ), 'list starts with the current result year and adds a divider at the next year' );
    ob_start();
    modern_catholic_events_render_calendar( new DateTimeImmutable( '2026-12-01', wp_timezone() ), array() );
    $calendar_year_markup = ob_get_clean();
    modern_catholic_events_test_assert( false !== strpos( $calendar_year_markup, 'modern-catholic-events-calendar__heading-month">December</span>' ) && false !== strpos( $calendar_year_markup, 'modern-catholic-events-calendar__heading-year">2026</span>' ) && false !== strpos( $calendar_year_markup, 'January 2027' ), 'calendar heading and cross-year navigation display the year' );

    $finite_date = modern_catholic_events_test_create( 'Finite by date', array_merge( $base_meta, array( 'recurrence_frequency' => 'daily', 'recurrence_end_type' => 'date', 'recurrence_end_date' => '2026-09-03', 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-10' ), $finite_date );
    modern_catholic_events_test_assert( 3 === count( $items ), 'recurrence end date is inclusive' );

    $exceptions = modern_catholic_events_test_create( 'Added and excluded dates', array_merge( $base_meta, array( 'recurrence_frequency' => 'daily', 'recurrence_end_type' => 'count', 'recurrence_count' => 3, 'additional_dates' => array( '2026-09-10' ), 'excluded_dates' => array( '2026-09-02' ), 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-12' ), $exceptions );
    $dates = array_map( static function ( $item ) { return $item['start']->format( 'Y-m-d' ); }, $items );
    modern_catholic_events_test_assert( in_array( '2026-09-10', $dates, true ) && ! in_array( '2026-09-02', $dates, true ), 'added and excluded dates' );

    $dst = modern_catholic_events_test_create( 'DST weekly', array_merge( $base_meta, array( 'start_date' => '2026-03-01', 'start_time' => '01:30', 'end_date' => '2026-03-01', 'end_time' => '02:30', 'recurrence_frequency' => 'weekly', 'recurrence_weekdays' => array( 'SU' ), 'recurrence_end_type' => 'count', 'recurrence_count' => 3, 'series_uid' => wp_generate_uuid4() ) ) );
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-03-01', '2026-03-20' ), $dst );
    modern_catholic_events_test_assert( 3 === count( $items ) && '01:30' === $items[2]['start']->format( 'H:i' ) && $items[0]['start']->format( 'P' ) !== $items[2]['start']->format( 'P' ), 'America/Chicago DST keeps local wall time' );

    $override_id = modern_catholic_events_create_override( $daily, '2026-09-02T18:00' );
    $modern_catholic_events_test_posts[] = $override_id;
    wp_update_post( array( 'ID' => $override_id, 'post_title' => '[Events Test] Changed occurrence' ) );
    update_post_meta( $override_id, '_mc_event_start_date', '2026-09-06' );
    update_post_meta( $override_id, '_mc_event_end_date', '2026-09-06' );
    update_post_meta( $override_id, '_mc_event_status', 'rescheduled' );
    update_post_meta( $override_id, '_mc_event_previous_start', '2026-09-02T18:00' );
    wp_set_object_terms( $override_id, array( $category_b['term_id'] ), 'mc_event_category' );
    modern_catholic_events_invalidate_cache();
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-08' ), $daily );
    $moved = array_values( array_filter( $items, static function ( $item ) { return '2026-09-02T18:00' === $item['recurrence_id']; } ) );
    modern_catholic_events_test_assert( 1 === count( $moved ) && '2026-09-06' === $moved[0]['start']->format( 'Y-m-d' ) && false !== strpos( $moved[0]['title'], 'Changed' ), 'one-occurrence edit and move preserve recurrence identifier' );
    modern_catholic_events_test_assert( false !== strpos( $moved[0]['permalink'], '/2026-09-02/' ), 'moved occurrence retains stable original-date URL' );

    $cancel_id = modern_catholic_events_create_override( $daily, '2026-09-03T18:00' );
    $modern_catholic_events_test_posts[] = $cancel_id;
    update_post_meta( $cancel_id, '_mc_event_status', 'canceled' );
    modern_catholic_events_invalidate_cache();
    $items = modern_catholic_events_test_for_series( modern_catholic_events_test_range( '2026-09-01', '2026-09-08' ), $daily );
    $canceled = array_values( array_filter( $items, static function ( $item ) { return 'canceled' === $item['event_status']; } ) );
    modern_catholic_events_test_assert( 1 === count( $canceled ), 'one-occurrence cancellation leaves series intact' );

    $split_source = modern_catholic_events_test_create( 'Split series', array_merge( $base_meta, array( 'recurrence_frequency' => 'weekly', 'recurrence_weekdays' => array( 'TU' ), 'recurrence_end_type' => 'count', 'recurrence_count' => 6, 'series_uid' => wp_generate_uuid4() ) ) );
    $split_id = modern_catholic_events_split_series( $split_source, '2026-09-15T18:00' );
    $modern_catholic_events_test_posts[] = $split_id;
    modern_catholic_events_test_assert( '2026-09-14' === modern_catholic_events_get_meta( $split_source, 'recurrence_end_date' ) && (int) modern_catholic_events_get_meta( $split_id, 'previous_series_id' ) === $split_source, 'this-and-following split preserves predecessor history' );
    modern_catholic_events_test_assert( 4 === (int) modern_catholic_events_get_meta( $split_id, 'recurrence_count' ) && '2026-09-15' === modern_catholic_events_get_meta( $split_id, 'end_date' ) && '20:00' === modern_catholic_events_get_meta( $split_id, 'end_time' ), 'count-limited split preserves remaining count and occurrence duration' );

    $version_before = (int) get_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION );
    update_post_meta( $weekly, '_mc_event_recurrence_interval', 2 );
    modern_catholic_events_invalidate_cache();
    modern_catholic_events_test_assert( (int) get_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION ) > $version_before, 'entire-series update invalidates cache version' );

    $filtered_a = modern_catholic_events_test_range( '2026-09-01', '2026-09-10', array( 'category' => 'events-test-formation' ) );
    $filtered_b = modern_catholic_events_test_range( '2026-09-01', '2026-09-10', array( 'category' => 'events-test-community' ) );
    modern_catholic_events_test_assert( (bool) modern_catholic_events_test_for_series( $filtered_a, $one_time ), 'category-filtered website results include inherited category' );
    modern_catholic_events_test_assert( 1 === count( modern_catholic_events_test_for_series( $filtered_b, $daily ) ), 'one-off override category replaces inherited category' );

    $ical_all = modern_catholic_events_build_icalendar();
    $ical_category = modern_catholic_events_build_icalendar( 'events-test-community' );
    modern_catholic_events_test_assert( false !== strpos( $ical_all, "\r\nRRULE:FREQ=DAILY" ) && false !== strpos( $ical_all, 'RECURRENCE-ID' ) && false !== strpos( $ical_all, 'STATUS:CANCELLED' ), 'ICS recurrence, override, and cancellation output' );
    $later_weekly = modern_catholic_events_build_occurrence( get_post( $weekly ), new DateTimeImmutable( '2026-09-08 18:00', wp_timezone() ) );
    $master_lines = modern_catholic_events_icalendar_lines( modern_catholic_events_icalendar_master_lines( get_post( $weekly ), $later_weekly ) );
    modern_catholic_events_test_assert( false !== strpos( $master_lines, 'DTSTART;TZID=America/Chicago:20260901T180000' ), 'ICS recurrence master retains original DTSTART when a bounded range begins later' );
    modern_catholic_events_test_assert( false !== strpos( $ical_category, 'Changed occurrence' ) && false === strpos( $ical_category, 'One-time timed event' ), 'category-filtered ICS output' );
    $uid_first = modern_catholic_events_icalendar_uid( $moved[0] );
    $uid_second = modern_catholic_events_icalendar_uid( $moved[0] );
    modern_catholic_events_test_assert( $uid_first === $uid_second && false !== strpos( $ical_all, 'SEQUENCE:' ), 'stable ICS UID and sequence' );
    modern_catholic_events_test_assert( "\r\n" === substr( $ical_all, -2 ) && 0 === preg_match( '/(?<!\r)\n/', $ical_all ), 'ICS uses only CRLF line endings' );

    $schema = modern_catholic_events_schema_data( $moved[0] );
    $schema_json = wp_json_encode( $schema );
    modern_catholic_events_test_assert( is_array( json_decode( $schema_json, true ) ) && 'https://schema.org/EventRescheduled' === $schema['eventStatus'] && isset( $schema['previousStartDate'] ), 'valid rescheduled Event JSON-LD' );
    $canceled_schema = modern_catholic_events_schema_data( $canceled[0] );
    modern_catholic_events_test_assert( 'https://schema.org/EventCancelled' === $canceled_schema['eventStatus'], 'canceled Event JSON-LD' );

    modern_catholic_events_test_assert( '' === modern_catholic_events_sanitize_date( '2026-02-30' ) && '23:59' === modern_catholic_events_sanitize_time( '23:59' ) && '' === modern_catholic_events_sanitize_time( '25:00' ), 'date and time sanitization' );
    $invalid_schedule = modern_catholic_events_validate_schedule( array_merge( $base_meta, array( 'start_date' => '2026-09-02', 'end_date' => '2026-09-01' ) ) );
    modern_catholic_events_test_assert( is_wp_error( $invalid_schedule ), 'end-before-start validation' );
    $nonce = wp_create_nonce( 'modern_catholic_events_save' );
    modern_catholic_events_test_assert( wp_verify_nonce( $nonce, 'modern_catholic_events_save' ) && modern_catholic_events_can_edit_meta( false, '_mc_event_start_date', $one_time ), 'nonce and metadata permission behavior' );

    $request = new WP_REST_Request( 'GET', '/modern-catholic/v1/events' );
    $request->set_param( 'start', '2026-09-01' );
    $request->set_param( 'end', '2026-09-10' );
    $request->set_param( 'category', 'events-test-formation' );
    $request->set_param( 'limit', 100 );
    $response = modern_catholic_events_rest_list( $request );
    modern_catholic_events_test_assert( $response instanceof WP_REST_Response && is_array( $response->get_data() ), 'public REST occurrence response' );
} catch ( Throwable $error ) {
    ++$modern_catholic_events_test_failed;
    echo 'ERROR: ' . $error->getMessage() . "\n";
} finally {
    foreach ( array_unique( array_filter( $modern_catholic_events_test_posts ) ) as $post_id ) {
        wp_delete_post( $post_id, true );
    }
    foreach ( array_unique( array_filter( $modern_catholic_events_test_terms ) ) as $term_id ) {
        wp_delete_term( $term_id, 'mc_event_category' );
    }
    modern_catholic_events_invalidate_cache();
}

echo "RESULT: {$modern_catholic_events_test_passed} passed, {$modern_catholic_events_test_failed} failed\n";
exit( $modern_catholic_events_test_failed ? 1 : 0 );
