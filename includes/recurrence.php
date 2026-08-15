<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gets a registered event metadata value.
 *
 * @param int    $post_id Event post ID.
 * @param string $key     Key without the _mc_event_ prefix.
 * @return mixed
 */
function modern_catholic_events_get_meta( $post_id, $key ) {
    return get_post_meta( $post_id, '_mc_event_' . $key, true );
}

/**
 * Builds a local site-timezone date/time from event fields.
 *
 * All-day end values are returned as the exclusive midnight after the final day.
 *
 * @param int    $post_id      Event post ID.
 * @param string $edge         start or end.
 * @param string $date_replace Optional occurrence date.
 * @return DateTimeImmutable|null
 */
function modern_catholic_events_get_datetime( $post_id, $edge = 'start', $date_replace = '' ) {
    $all_day = (bool) modern_catholic_events_get_meta( $post_id, 'all_day' );
    $date    = $date_replace ? $date_replace : modern_catholic_events_get_meta( $post_id, $edge . '_date' );

    if ( ! modern_catholic_events_sanitize_date( $date ) ) {
        return null;
    }

    if ( $all_day ) {
        $value = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
        return 'end' === $edge ? $value->modify( '+1 day' ) : $value;
    }

    $time = modern_catholic_events_get_meta( $post_id, $edge . '_time' );
    if ( ! modern_catholic_events_sanitize_time( $time ) ) {
        $time = 'start' === $edge ? '00:00' : modern_catholic_events_get_meta( $post_id, 'start_time' );
    }
    if ( ! modern_catholic_events_sanitize_time( $time ) ) {
        $time = '00:00';
    }

    return DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, wp_timezone() ) ?: null;
}

/**
 * Returns the effective end for an occurrence beginning on a generated date.
 *
 * @param int               $post_id Event post ID.
 * @param DateTimeImmutable $start   Generated occurrence start.
 * @return DateTimeImmutable
 */
function modern_catholic_events_occurrence_end( $post_id, $start ) {
    $original_start = modern_catholic_events_get_datetime( $post_id, 'start' );
    $original_end   = modern_catholic_events_get_datetime( $post_id, 'end' );

    if ( ! $original_start || ! $original_end || $original_end < $original_start ) {
        return (bool) modern_catholic_events_get_meta( $post_id, 'all_day' ) ? $start->modify( '+1 day' ) : $start;
    }

    return $start->add( $original_start->diff( $original_end ) );
}

/**
 * Converts an RFC weekday code to an ISO weekday number.
 *
 * @param string $weekday RFC weekday code.
 * @return int
 */
function modern_catholic_events_weekday_number( $weekday ) {
    $map = array( 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7 );
    return isset( $map[ $weekday ] ) ? $map[ $weekday ] : 1;
}

/**
 * Finds a monthly nth-weekday date or null when it does not exist.
 *
 * @param DateTimeImmutable $month    First day of month.
 * @param int               $week     1-4 or -1 for last.
 * @param string            $weekday  RFC weekday code.
 * @return DateTimeImmutable|null
 */
function modern_catholic_events_monthly_weekday( $month, $week, $weekday ) {
    $target = modern_catholic_events_weekday_number( $weekday );

    if ( -1 === (int) $week ) {
        $date = $month->modify( 'last day of this month' );
        while ( (int) $date->format( 'N' ) !== $target ) {
            $date = $date->modify( '-1 day' );
        }
        return $date;
    }

    $date = $month;
    while ( (int) $date->format( 'N' ) !== $target ) {
        $date = $date->modify( '+1 day' );
    }
    $date = $date->modify( '+' . ( ( (int) $week - 1 ) * 7 ) . ' days' );
    return $date->format( 'Y-m' ) === $month->format( 'Y-m' ) ? $date : null;
}

/**
 * Generates recurrence start dates for one series within a bounded range.
 *
 * @param int               $post_id     Series post ID.
 * @param DateTimeImmutable $range_start Inclusive requested range start.
 * @param DateTimeImmutable $range_end   Inclusive requested range end.
 * @param bool              $include_adjustments Whether to apply RDATE/EXDATE adjustments.
 * @return DateTimeImmutable[]
 */
function modern_catholic_events_generate_dates( $post_id, $range_start, $range_end, $include_adjustments = true ) {
    $series_start = modern_catholic_events_get_datetime( $post_id, 'start' );
    if ( ! $series_start || $range_end < $series_start ) {
        return array();
    }

    $frequency = modern_catholic_events_get_meta( $post_id, 'recurrence_frequency' ) ?: 'none';
    $interval  = max( 1, (int) modern_catholic_events_get_meta( $post_id, 'recurrence_interval' ) );
    $end_type  = modern_catholic_events_get_meta( $post_id, 'recurrence_end_type' ) ?: 'never';
    $end_date  = modern_catholic_events_sanitize_date( modern_catholic_events_get_meta( $post_id, 'recurrence_end_date' ) );
    $end_count = max( 1, (int) modern_catholic_events_get_meta( $post_id, 'recurrence_count' ) );
    $hard_end  = $range_end;

    if ( 'date' === $end_type && $end_date ) {
        $inclusive_end = DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date, wp_timezone() )->setTime( 23, 59, 59 );
        $hard_end      = $inclusive_end < $hard_end ? $inclusive_end : $hard_end;
    }

    $dates     = array();
    $generated = 0;
    $append    = static function ( $candidate ) use ( &$dates, &$generated, $range_start, $hard_end, $end_type, $end_count ) {
        if ( 'count' === $end_type && $generated >= $end_count ) {
            return false;
        }
        ++$generated;
        if ( $candidate >= $range_start && $candidate <= $hard_end ) {
            $dates[ $candidate->format( 'Y-m-d\TH:i' ) ] = $candidate;
        }
        return true;
    };

    if ( 'none' === $frequency ) {
        $append( $series_start );
    } elseif ( 'daily' === $frequency ) {
        for ( $candidate = $series_start; $candidate <= $hard_end; $candidate = $candidate->modify( '+' . $interval . ' days' ) ) {
            if ( ! $append( $candidate ) ) {
                break;
            }
        }
    } elseif ( 'weekly' === $frequency ) {
        $weekdays  = modern_catholic_events_sanitize_weekdays( modern_catholic_events_get_meta( $post_id, 'recurrence_weekdays' ) );
        $weekdays  = $weekdays ? $weekdays : array( strtoupper( $series_start->format( 'D' ) === 'THU' ? 'TH' : substr( $series_start->format( 'D' ), 0, 2 ) ) );
        $weekdays  = array_map( 'modern_catholic_events_weekday_number', $weekdays );
        $week_zero = $series_start->modify( 'monday this week' )->setTime( 0, 0 );

        for ( $candidate = $series_start->setTime( 0, 0 ); $candidate <= $hard_end; $candidate = $candidate->modify( '+1 day' ) ) {
            $days_since = (int) $week_zero->diff( $candidate )->format( '%r%a' );
            $week_index = (int) floor( $days_since / 7 );
            if ( 0 !== $week_index % $interval || ! in_array( (int) $candidate->format( 'N' ), $weekdays, true ) ) {
                continue;
            }
            $timed = $candidate->setTime( (int) $series_start->format( 'H' ), (int) $series_start->format( 'i' ) );
            if ( ! $append( $timed ) ) {
                break;
            }
        }
    } elseif ( 'monthly' === $frequency ) {
        $month = $series_start->modify( 'first day of this month' )->setTime( 0, 0 );
        for ( $index = 0; $month <= $hard_end; ++$index, $month = $month->modify( '+' . $interval . ' months' ) ) {
            if ( 'nth_weekday' === modern_catholic_events_get_meta( $post_id, 'monthly_mode' ) ) {
                $candidate = modern_catholic_events_monthly_weekday(
                    $month,
                    (int) modern_catholic_events_get_meta( $post_id, 'monthly_week' ),
                    modern_catholic_events_get_meta( $post_id, 'monthly_weekday' )
                );
            } else {
                $day = max( 1, min( 31, (int) modern_catholic_events_get_meta( $post_id, 'monthly_day' ) ) );
                $candidate = checkdate( (int) $month->format( 'm' ), $day, (int) $month->format( 'Y' ) ) ? $month->setDate( (int) $month->format( 'Y' ), (int) $month->format( 'm' ), $day ) : null;
            }
            if ( $candidate ) {
                $candidate = $candidate->setTime( (int) $series_start->format( 'H' ), (int) $series_start->format( 'i' ) );
            }
            if ( ! $candidate || $candidate < $series_start ) {
                continue;
            }
            if ( ! $append( $candidate ) ) {
                break;
            }
        }
    } elseif ( 'yearly' === $frequency ) {
        $month = (int) $series_start->format( 'm' );
        $day   = (int) $series_start->format( 'd' );
        for ( $year = (int) $series_start->format( 'Y' ); $year <= (int) $hard_end->format( 'Y' ); $year += $interval ) {
            if ( ! checkdate( $month, $day, $year ) ) {
                continue;
            }
            $candidate = $series_start->setDate( $year, $month, $day );
            if ( ! $append( $candidate ) ) {
                break;
            }
        }
    }

    if ( $include_adjustments ) {
        foreach ( modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $post_id, 'additional_dates' ) ) as $date ) {
            $candidate = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() )->setTime( (int) $series_start->format( 'H' ), (int) $series_start->format( 'i' ) );
            if ( $candidate >= $range_start && $candidate <= $range_end ) {
                $dates[ $candidate->format( 'Y-m-d\TH:i' ) ] = $candidate;
            }
        }

        $excluded = modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $post_id, 'excluded_dates' ) );
        foreach ( $dates as $key => $candidate ) {
            if ( in_array( $candidate->format( 'Y-m-d' ), $excluded, true ) ) {
                unset( $dates[ $key ] );
            }
        }
    }

    ksort( $dates );
    return array_slice( array_values( $dates ), 0, MODERN_CATHOLIC_EVENTS_MAX_OCCURRENCES );
}

/**
 * Builds a stable occurrence data structure from a series or override post.
 *
 * @param WP_Post           $series        Source series.
 * @param DateTimeImmutable $original_start Original recurrence start.
 * @param WP_Post|null      $override      Linked occurrence override.
 * @return array<string,mixed>|null
 */
function modern_catholic_events_build_occurrence( $series, $original_start, $override = null ) {
    $effective_id = $override ? $override->ID : $series->ID;
    $start        = $override ? modern_catholic_events_get_datetime( $effective_id, 'start' ) : $original_start;
    if ( ! $start ) {
        return null;
    }
    $end = $override ? modern_catholic_events_get_datetime( $effective_id, 'end' ) : modern_catholic_events_occurrence_end( $series->ID, $start );

    $series_uid = modern_catholic_events_get_meta( $series->ID, 'series_uid' );
    if ( ! $series_uid ) {
        $series_uid = 'event-' . $series->ID;
    }

    $recurrence_id = $original_start->format( 'Y-m-d\TH:i' );
    $category_ids  = wp_get_object_terms( $effective_id, 'mc_event_category', array( 'fields' => 'ids' ) );
    $category_ids  = is_wp_error( $category_ids ) ? array() : array_map( 'intval', $category_ids );

    return array(
        'series_id'       => $series->ID,
        'post_id'         => $effective_id,
        'override_id'     => $override ? $override->ID : 0,
        'series_uid'      => $series_uid,
        'occurrence_uid'  => $series_uid . '-' . gmdate( 'Ymd\THis', $original_start->getTimestamp() ),
        'recurrence_id'   => $recurrence_id,
        'original_start'  => $original_start,
        'start'           => $start,
        'end'             => $end ?: $start,
        'all_day'         => (bool) modern_catholic_events_get_meta( $effective_id, 'all_day' ),
        'event_status'    => modern_catholic_events_get_meta( $effective_id, 'status' ) ?: 'scheduled',
        'title'           => get_the_title( $effective_id ),
        'content'         => get_post_field( 'post_content', $effective_id ),
        'excerpt'         => get_the_excerpt( $effective_id ),
        'slug'            => $series->post_name,
        'permalink'       => modern_catholic_events_occurrence_url( $series, $recurrence_id ),
        'category_ids'    => $category_ids,
        'sequence'        => (int) modern_catholic_events_get_meta( $effective_id, 'sequence' ),
        'last_modified_gmt' => get_post_field( 'post_modified_gmt', $effective_id ),
    );
}

/**
 * Gets occurrences from native posts and linked overrides for a bounded range.
 *
 * Frontend calls may read a prewarmed transient but never write one.
 *
 * @param DateTimeInterface|string $range_start Inclusive start.
 * @param DateTimeInterface|string $range_end   Inclusive end.
 * @param array                    $args        category, limit, status, warm_cache.
 * @return array<int,array<string,mixed>>
 */
function modern_catholic_events_get_occurrences( $range_start, $range_end, $args = array() ) {
    $timezone   = wp_timezone();
    $range_start = $range_start instanceof DateTimeInterface ? ( new DateTimeImmutable( '@' . $range_start->getTimestamp() ) )->setTimezone( $timezone ) : new DateTimeImmutable( $range_start, $timezone );
    $range_end   = $range_end instanceof DateTimeInterface ? ( new DateTimeImmutable( '@' . $range_end->getTimestamp() ) )->setTimezone( $timezone ) : new DateTimeImmutable( $range_end, $timezone );
    if ( $range_end < $range_start ) {
        return array();
    }

    $args = wp_parse_args( $args, array( 'category' => '', 'limit' => -1, 'status' => array(), 'warm_cache' => false ) );
    $version   = (int) get_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION, 1 );
    $cache_key = 'mc_events_' . md5( wp_json_encode( array( $range_start->format( DATE_ATOM ), $range_end->format( DATE_ATOM ), $args['category'], $args['status'], $version, wp_timezone_string() ) ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $args['limit'] > -1 ? array_slice( $cached, 0, (int) $args['limit'] ) : $cached;
    }

    $series_posts = get_posts(
        array(
            'post_type'      => 'mc_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                array( 'key' => '_mc_event_series_id', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_mc_event_series_id', 'value' => 0, 'compare' => '=', 'type' => 'NUMERIC' ),
            ),
        )
    );
    if ( ! $series_posts ) {
        return array();
    }

    $series_ids = wp_list_pluck( $series_posts, 'ID' );
    $overrides  = get_posts(
        array(
            'post_type'      => 'mc_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array( 'key' => '_mc_event_series_id', 'value' => $series_ids, 'compare' => 'IN', 'type' => 'NUMERIC' ),
            ),
        )
    );
    $override_map = array();
    foreach ( $overrides as $override ) {
        $series_id     = (int) modern_catholic_events_get_meta( $override->ID, 'series_id' );
        $recurrence_id = modern_catholic_events_get_meta( $override->ID, 'recurrence_id' );
        if ( $series_id && $recurrence_id ) {
            $override_map[ $series_id . '|' . $recurrence_id ] = $override;
        }
    }

    $occurrences   = array();
    $used_overrides = array();
    foreach ( $series_posts as $series ) {
        foreach ( modern_catholic_events_generate_dates( $series->ID, $range_start, $range_end ) as $original_start ) {
            $map_key    = $series->ID . '|' . $original_start->format( 'Y-m-d\TH:i' );
            $override   = isset( $override_map[ $map_key ] ) ? $override_map[ $map_key ] : null;
            $occurrence = modern_catholic_events_build_occurrence( $series, $original_start, $override );
            if ( $override ) {
                $used_overrides[ $override->ID ] = true;
            }
            if ( $occurrence ) {
                $occurrences[] = $occurrence;
            }
        }
    }

    $series_by_id = array();
    foreach ( $series_posts as $series ) {
        $series_by_id[ $series->ID ] = $series;
    }
    foreach ( $overrides as $override ) {
        if ( isset( $used_overrides[ $override->ID ] ) ) {
            continue;
        }
        $series_id     = (int) modern_catholic_events_get_meta( $override->ID, 'series_id' );
        $recurrence_id = modern_catholic_events_get_meta( $override->ID, 'recurrence_id' );
        if ( ! isset( $series_by_id[ $series_id ] ) || ! $recurrence_id ) {
            continue;
        }
        $original = modern_catholic_events_parse_local_value( $recurrence_id );
        if ( $original ) {
            $occurrences[] = modern_catholic_events_build_occurrence( $series_by_id[ $series_id ], $original, $override );
        }
    }

    $category_term = $args['category'] ? get_term_by( 'slug', sanitize_title( $args['category'] ), 'mc_event_category' ) : null;
    $statuses      = array_filter( (array) $args['status'] );
    $occurrences   = array_values(
        array_filter(
            $occurrences,
            static function ( $occurrence ) use ( $range_start, $range_end, $category_term, $statuses ) {
                if ( ! $occurrence || $occurrence['end'] < $range_start || $occurrence['start'] > $range_end ) {
                    return false;
                }
                if ( $category_term && ! in_array( (int) $category_term->term_id, $occurrence['category_ids'], true ) ) {
                    return false;
                }
                return ! $statuses || in_array( $occurrence['event_status'], $statuses, true );
            }
        )
    );

    usort(
        $occurrences,
        static function ( $left, $right ) {
            $comparison = $left['start'] <=> $right['start'];
            return 0 !== $comparison ? $comparison : strcasecmp( $left['title'], $right['title'] );
        }
    );

    $occurrences = array_slice( $occurrences, 0, MODERN_CATHOLIC_EVENTS_MAX_OCCURRENCES );
    if ( $args['warm_cache'] ) {
        set_transient( $cache_key, $occurrences, 2 * DAY_IN_SECONDS );
    }
    return $args['limit'] > -1 ? array_slice( $occurrences, 0, (int) $args['limit'] ) : $occurrences;
}

/**
 * Parses a normalized local recurrence identifier.
 *
 * @param string $value Local date or date-time.
 * @return DateTimeImmutable|null
 */
function modern_catholic_events_parse_local_value( $value ) {
    foreach ( array( '!Y-m-d\TH:i', '!Y-m-d' ) as $format ) {
        $date = DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
        if ( $date && $date->format( false !== strpos( $format, 'H:i' ) ? 'Y-m-d\TH:i' : 'Y-m-d' ) === $value ) {
            return $date;
        }
    }
    return null;
}

/**
 * Returns a stable occurrence URL based on its original recurrence date.
 */
function modern_catholic_events_occurrence_url( $series, $recurrence_id ) {
    $series = is_object( $series ) ? $series : get_post( $series );
    $date   = substr( $recurrence_id, 0, 10 );
    return home_url( user_trailingslashit( 'events/' . $series->post_name . '/' . $date ) );
}

/**
 * Finds one occurrence by series slug and stable recurrence date.
 *
 * @return array<string,mixed>|null
 */
function modern_catholic_events_find_occurrence( $slug, $date ) {
    $series = get_page_by_path( sanitize_title( $slug ), OBJECT, 'mc_event' );
    if ( ! $series || (int) modern_catholic_events_get_meta( $series->ID, 'series_id' ) ) {
        return null;
    }
    $day_start = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
    if ( ! $day_start ) {
        return null;
    }
    $occurrences = modern_catholic_events_get_occurrences( $day_start->modify( '-366 days' ), $day_start->modify( '+366 days' ) );
    foreach ( $occurrences as $occurrence ) {
        if ( (int) $occurrence['series_id'] === (int) $series->ID && substr( $occurrence['recurrence_id'], 0, 10 ) === $date ) {
            return $occurrence;
        }
    }
    return null;
}

/**
 * Copies native post fields, registered metadata, media, and categories.
 *
 * @param int   $source_id Source event.
 * @param array $changes   Post and meta changes.
 * @return int|WP_Error
 */
function modern_catholic_events_copy_event( $source_id, $changes = array() ) {
    $source = get_post( $source_id );
    if ( ! $source || 'mc_event' !== $source->post_type ) {
        return new WP_Error( 'invalid_event', __( 'The source event does not exist.', 'modern-catholic-parish-events' ) );
    }

    $post_changes = isset( $changes['post'] ) ? $changes['post'] : array();
    $new_id = wp_insert_post(
        wp_parse_args(
            $post_changes,
            array(
                'post_type'    => 'mc_event',
                'post_status'  => $source->post_status,
                'post_title'   => $source->post_title,
                'post_content' => $source->post_content,
                'post_excerpt' => $source->post_excerpt,
                'post_author'  => get_current_user_id() ?: $source->post_author,
            )
        ),
        true
    );
    if ( is_wp_error( $new_id ) ) {
        return $new_id;
    }

    foreach ( modern_catholic_events_meta_definitions() as $key => $definition ) {
        $value = get_post_meta( $source_id, $key, true );
        if ( '' !== $value && $value !== $definition['default'] ) {
            update_post_meta( $new_id, $key, $value );
        }
    }
    foreach ( isset( $changes['meta'] ) ? $changes['meta'] : array() as $key => $value ) {
        update_post_meta( $new_id, '_mc_event_' . $key, $value );
    }

    $term_ids = wp_get_object_terms( $source_id, 'mc_event_category', array( 'fields' => 'ids' ) );
    if ( ! is_wp_error( $term_ids ) ) {
        wp_set_object_terms( $new_id, array_map( 'intval', $term_ids ), 'mc_event_category' );
    }
    if ( has_post_thumbnail( $source_id ) ) {
        set_post_thumbnail( $new_id, get_post_thumbnail_id( $source_id ) );
    }
    return $new_id;
}

/**
 * Creates or returns a linked one-occurrence override.
 */
function modern_catholic_events_create_override( $series_id, $recurrence_id ) {
    $existing = get_posts(
        array(
            'post_type' => 'mc_event', 'post_status' => 'any', 'posts_per_page' => 1,
            'meta_query' => array(
                array( 'key' => '_mc_event_series_id', 'value' => (int) $series_id, 'type' => 'NUMERIC' ),
                array( 'key' => '_mc_event_recurrence_id', 'value' => $recurrence_id ),
            ),
        )
    );
    if ( $existing ) {
        return $existing[0]->ID;
    }

    $original = modern_catholic_events_parse_local_value( $recurrence_id );
    $series   = get_post( $series_id );
    if ( ! $series || ! $original ) {
        return new WP_Error( 'invalid_occurrence', __( 'The occurrence could not be found.', 'modern-catholic-parish-events' ) );
    }
    $end = modern_catholic_events_occurrence_end( $series_id, $original );

    return modern_catholic_events_copy_event(
        $series_id,
        array(
            'post' => array( 'post_parent' => $series_id ),
            'meta' => array(
                'series_id'            => $series_id,
                'recurrence_id'        => $recurrence_id,
                'recurrence_frequency' => 'none',
                'start_date'           => $original->format( 'Y-m-d' ),
                'start_time'           => $original->format( 'H:i' ),
                'end_date'             => (bool) modern_catholic_events_get_meta( $series_id, 'all_day' ) ? $end->modify( '-1 day' )->format( 'Y-m-d' ) : $end->format( 'Y-m-d' ),
                'end_time'             => $end->format( 'H:i' ),
            ),
        )
    );
}

/**
 * Splits a series before an occurrence and returns the successor series ID.
 */
function modern_catholic_events_split_series( $series_id, $recurrence_id ) {
    $selected = modern_catholic_events_parse_local_value( $recurrence_id );
    if ( ! $selected ) {
        return new WP_Error( 'invalid_occurrence', __( 'The occurrence could not be found.', 'modern-catholic-parish-events' ) );
    }

    $old_end_type = modern_catholic_events_get_meta( $series_id, 'recurrence_end_type' );
    $old_end_date = modern_catholic_events_get_meta( $series_id, 'recurrence_end_date' );
    $old_count    = modern_catholic_events_get_meta( $series_id, 'recurrence_count' );
    $additional   = modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $series_id, 'additional_dates' ) );
    $excluded     = modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $series_id, 'excluded_dates' ) );
    $split_date   = $selected->format( 'Y-m-d' );
    $series_start = modern_catholic_events_get_datetime( $series_id, 'start' );
    $selected_end = modern_catholic_events_occurrence_end( $series_id, $selected );
    $all_day      = (bool) modern_catholic_events_get_meta( $series_id, 'all_day' );
    $next_count   = $old_count;

    if ( 'count' === $old_end_type && $series_start ) {
        $prior_count = count( modern_catholic_events_generate_dates( $series_id, $series_start, $selected->modify( '-1 second' ), false ) );
        $next_count  = max( 1, (int) $old_count - $prior_count );
    }

    update_post_meta( $series_id, '_mc_event_recurrence_end_type', 'date' );
    update_post_meta( $series_id, '_mc_event_recurrence_end_date', $selected->modify( '-1 day' )->format( 'Y-m-d' ) );
    update_post_meta( $series_id, '_mc_event_additional_dates', array_values( array_filter( $additional, static function ( $date ) use ( $split_date ) { return $date < $split_date; } ) ) );
    update_post_meta( $series_id, '_mc_event_excluded_dates', array_values( array_filter( $excluded, static function ( $date ) use ( $split_date ) { return $date < $split_date; } ) ) );

    $successor = modern_catholic_events_copy_event(
        $series_id,
        array(
            'post' => array( 'post_parent' => 0 ),
            'meta' => array(
                'series_id'             => 0,
                'recurrence_id'         => '',
                'series_uid'            => wp_generate_uuid4(),
                'previous_series_id'    => $series_id,
                'start_date'            => $split_date,
                'start_time'            => $selected->format( 'H:i' ),
                'end_date'              => $all_day ? $selected_end->modify( '-1 day' )->format( 'Y-m-d' ) : $selected_end->format( 'Y-m-d' ),
                'end_time'              => $selected_end->format( 'H:i' ),
                'recurrence_end_type'   => $old_end_type,
                'recurrence_end_date'   => $old_end_date,
                'recurrence_count'      => $next_count,
                'additional_dates'      => array_values( array_filter( $additional, static function ( $date ) use ( $split_date ) { return $date >= $split_date; } ) ),
                'excluded_dates'        => array_values( array_filter( $excluded, static function ( $date ) use ( $split_date ) { return $date >= $split_date; } ) ),
            ),
        )
    );
    modern_catholic_events_invalidate_cache();
    return $successor;
}

/**
 * Increments the rebuildable global data version.
 */
function modern_catholic_events_invalidate_cache() {
    $version = (int) get_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION, 1 );
    update_option( MODERN_CATHOLIC_EVENTS_CACHE_VERSION_OPTION, $version + 1, false );
}

/**
 * Assigns stable identifiers, increments feed sequence, and invalidates caches.
 */
function modern_catholic_events_touch_event( $post_id, $post, $updated ) {
    unset( $updated );
    if ( ! $post || 'mc_event' !== $post->post_type || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }
    if ( ! modern_catholic_events_get_meta( $post_id, 'series_uid' ) ) {
        update_post_meta( $post_id, '_mc_event_series_uid', wp_generate_uuid4() );
    }
    update_post_meta( $post_id, '_mc_event_sequence', (int) modern_catholic_events_get_meta( $post_id, 'sequence' ) + 1 );
    modern_catholic_events_invalidate_cache();
}
add_action( 'save_post_mc_event', 'modern_catholic_events_touch_event', 100, 3 );

/**
 * Invalidates bounded occurrence caches before an event is permanently deleted.
 */
function modern_catholic_events_event_deleted( $post_id, $post ) {
    unset( $post_id );
    if ( $post instanceof WP_Post && 'mc_event' === $post->post_type ) {
        modern_catholic_events_invalidate_cache();
    }
}
add_action( 'before_delete_post', 'modern_catholic_events_event_deleted', 10, 2 );

function modern_catholic_events_terms_changed( $object_id, $terms, $term_taxonomy_ids, $taxonomy ) {
    unset( $terms, $term_taxonomy_ids );
    if ( 'mc_event_category' === $taxonomy && 'mc_event' === get_post_type( $object_id ) ) {
        modern_catholic_events_invalidate_cache();
    }
}
add_action( 'set_object_terms', 'modern_catholic_events_terms_changed', 10, 4 );

/**
 * Prewarms common bounded ranges; correctness never depends on this job.
 */
function modern_catholic_events_warm_common_cache() {
    $start = new DateTimeImmutable( 'today', wp_timezone() );
    modern_catholic_events_get_occurrences( $start, $start->modify( '+3 months' ), array( 'warm_cache' => true ) );
}
add_action( 'modern_catholic_events_daily_cache_warm', 'modern_catholic_events_warm_common_cache' );
