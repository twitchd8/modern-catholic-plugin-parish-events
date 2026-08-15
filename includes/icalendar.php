<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds public subscription and download routes.
 */
function modern_catholic_events_add_icalendar_rewrites() {
    add_rewrite_rule( '^events/calendar-download\.ics$', 'index.php?modern_catholic_events_ical=1&modern_catholic_events_download=1', 'top' );
    add_rewrite_rule( '^events/calendar\.ics$', 'index.php?modern_catholic_events_ical=1', 'top' );
    add_rewrite_rule( '^events/category/([^/]+)/calendar-download\.ics$', 'index.php?modern_catholic_events_ical=1&modern_catholic_events_category=$matches[1]&modern_catholic_events_download=1', 'top' );
    add_rewrite_rule( '^events/category/([^/]+)/calendar\.ics$', 'index.php?modern_catholic_events_ical=1&modern_catholic_events_category=$matches[1]', 'top' );
}
add_action( 'init', 'modern_catholic_events_add_icalendar_rewrites', 10 );

function modern_catholic_events_icalendar_query_vars( $query_vars ) {
    $query_vars[] = 'modern_catholic_events_ical';
    $query_vars[] = 'modern_catholic_events_download';
    $query_vars[] = 'modern_catholic_events_category';
    return $query_vars;
}
add_filter( 'query_vars', 'modern_catholic_events_icalendar_query_vars' );

/**
 * Returns a public feed, download, or individual occurrence URL.
 *
 * @param string     $category Category slug.
 * @param bool       $download Force attachment download.
 * @param array|bool $occurrence Individual occurrence or false.
 * @return string
 */
function modern_catholic_events_feed_url( $category = '', $download = false, $occurrence = false ) {
    if ( is_array( $occurrence ) ) {
        return trailingslashit( $occurrence['permalink'] ) . 'event.ics';
    }

    if ( $category ) {
        $path = 'events/category/' . sanitize_title( $category ) . '/calendar' . ( $download ? '-download' : '' ) . '.ics';
    } else {
        $path = 'events/calendar' . ( $download ? '-download' : '' ) . '.ics';
    }
    $url = home_url( '/' . $path );
    if ( ! $download && 0 === strpos( $url, 'https://' ) ) {
        return 'webcal://' . substr( $url, 8 );
    }
    if ( ! $download && 0 === strpos( $url, 'http://' ) ) {
        return 'webcal://' . substr( $url, 7 );
    }
    return $url;
}

/**
 * Escapes an RFC 5545 text value.
 */
function modern_catholic_events_icalendar_escape( $value ) {
    $value = wp_strip_all_tags( (string) $value );
    $value = str_replace( array( "\r\n", "\r", "\n" ), '\\n', $value );
    return str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $value );
}

/**
 * Folds one UTF-8 content line to at most 75 octets per RFC 5545.
 */
function modern_catholic_events_icalendar_fold( $line ) {
    $segments = array();
    $prefix   = '';
    while ( strlen( $line ) > ( $prefix ? 74 : 75 ) ) {
        $length     = $prefix ? 74 : 75;
        $segment    = function_exists( 'mb_strcut' ) ? mb_strcut( $line, 0, $length, 'UTF-8' ) : substr( $line, 0, $length );
        $segments[] = $prefix . $segment;
        $line       = substr( $line, strlen( $segment ) );
        $prefix     = ' ';
    }
    $segments[] = $prefix . $line;
    return implode( "\r\n", $segments );
}

/**
 * Serializes content lines with mandatory CRLF endings.
 */
function modern_catholic_events_icalendar_lines( $lines ) {
    return implode( "\r\n", array_map( 'modern_catholic_events_icalendar_fold', $lines ) ) . "\r\n";
}

/**
 * Returns the stable calendar UID for a series.
 */
function modern_catholic_events_icalendar_uid( $occurrence ) {
    $host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost';
    return sanitize_key( $occurrence['series_uid'] ) . '@' . strtolower( $host );
}

/**
 * Formats DTSTART/DTEND values in local site time.
 */
function modern_catholic_events_icalendar_date_line( $name, $date, $all_day ) {
    if ( $all_day ) {
        return $name . ';VALUE=DATE:' . $date->format( 'Ymd' );
    }
    return $name . ';TZID=' . modern_catholic_events_icalendar_escape( wp_timezone_string() ) . ':' . $date->format( 'Ymd\THis' );
}

/**
 * Maps an editorial event status to RFC 5545.
 */
function modern_catholic_events_icalendar_status( $status ) {
    if ( 'canceled' === $status ) {
        return 'CANCELLED';
    }
    if ( 'postponed' === $status ) {
        return 'TENTATIVE';
    }
    return 'CONFIRMED';
}

/**
 * Produces an RRULE value from normalized recurrence fields.
 */
function modern_catholic_events_icalendar_rrule( $post_id ) {
    $frequency = modern_catholic_events_get_meta( $post_id, 'recurrence_frequency' );
    if ( ! in_array( $frequency, array( 'daily', 'weekly', 'monthly', 'yearly' ), true ) ) {
        return '';
    }
    $parts = array( 'FREQ=' . strtoupper( $frequency ), 'INTERVAL=' . max( 1, (int) modern_catholic_events_get_meta( $post_id, 'recurrence_interval' ) ) );
    if ( 'weekly' === $frequency ) {
        $weekdays = modern_catholic_events_sanitize_weekdays( modern_catholic_events_get_meta( $post_id, 'recurrence_weekdays' ) );
        if ( $weekdays ) {
            $parts[] = 'BYDAY=' . implode( ',', $weekdays );
        }
    } elseif ( 'monthly' === $frequency ) {
        if ( 'nth_weekday' === modern_catholic_events_get_meta( $post_id, 'monthly_mode' ) ) {
            $parts[] = 'BYDAY=' . (int) modern_catholic_events_get_meta( $post_id, 'monthly_week' ) . modern_catholic_events_get_meta( $post_id, 'monthly_weekday' );
        } else {
            $parts[] = 'BYMONTHDAY=' . max( 1, min( 31, (int) modern_catholic_events_get_meta( $post_id, 'monthly_day' ) ) );
        }
    }

    $end_type = modern_catholic_events_get_meta( $post_id, 'recurrence_end_type' );
    if ( 'count' === $end_type ) {
        $parts[] = 'COUNT=' . max( 1, (int) modern_catholic_events_get_meta( $post_id, 'recurrence_count' ) );
    } elseif ( 'date' === $end_type ) {
        $end_date = modern_catholic_events_sanitize_date( modern_catholic_events_get_meta( $post_id, 'recurrence_end_date' ) );
        if ( $end_date ) {
            $until   = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $end_date . ' 23:59:59', wp_timezone() )->setTimezone( new DateTimeZone( 'UTC' ) );
            $parts[] = 'UNTIL=' . $until->format( 'Ymd\THis\Z' );
        }
    }
    return implode( ';', $parts );
}

/**
 * Returns VEVENT lines for one effective occurrence.
 */
function modern_catholic_events_icalendar_occurrence_lines( $occurrence, $include_recurrence_id = true ) {
    $post_id    = $occurrence['post_id'];
    $categories = wp_get_object_terms( $post_id, 'mc_event_category', array( 'fields' => 'names' ) );
    $location   = modern_catholic_events_get_meta( $post_id, 'venue_name' );
    $address    = modern_catholic_events_get_meta( $post_id, 'formatted_address' );
    $contact    = modern_catholic_events_get_meta( $post_id, 'contact_email' );
    $contact_name = modern_catholic_events_get_meta( $post_id, 'contact_name' );
    $modified   = $occurrence['last_modified_gmt'] ? strtotime( $occurrence['last_modified_gmt'] . ' UTC' ) : time();

    $lines = array(
        'BEGIN:VEVENT',
        'UID:' . modern_catholic_events_icalendar_uid( $occurrence ),
        'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
        'LAST-MODIFIED:' . gmdate( 'Ymd\THis\Z', $modified ),
        'SEQUENCE:' . (int) $occurrence['sequence'],
    );
    if ( $include_recurrence_id ) {
        $original = $occurrence['original_start'];
        $lines[] = $occurrence['all_day'] ? 'RECURRENCE-ID;VALUE=DATE:' . $original->format( 'Ymd' ) : 'RECURRENCE-ID;TZID=' . modern_catholic_events_icalendar_escape( wp_timezone_string() ) . ':' . $original->format( 'Ymd\THis' );
    }
    $lines[] = modern_catholic_events_icalendar_date_line( 'DTSTART', $occurrence['start'], $occurrence['all_day'] );
    $lines[] = modern_catholic_events_icalendar_date_line( 'DTEND', $occurrence['end'], $occurrence['all_day'] );
    $lines[] = 'STATUS:' . modern_catholic_events_icalendar_status( $occurrence['event_status'] );
    $lines[] = 'SUMMARY:' . modern_catholic_events_icalendar_escape( $occurrence['title'] );
    $lines[] = 'DESCRIPTION:' . modern_catholic_events_icalendar_escape( $occurrence['excerpt'] ?: $occurrence['content'] );
    if ( $location || $address ) {
        $lines[] = 'LOCATION:' . modern_catholic_events_icalendar_escape( implode( ', ', array_filter( array( $location, $address ) ) ) );
    }
    $lines[] = 'URL:' . esc_url_raw( $occurrence['permalink'] );
    if ( ! is_wp_error( $categories ) && $categories ) {
        $lines[] = 'CATEGORIES:' . implode( ',', array_map( 'modern_catholic_events_icalendar_escape', $categories ) );
    }
    if ( $contact ) {
        $lines[] = 'ORGANIZER' . ( $contact_name ? ';CN=' . modern_catholic_events_icalendar_escape( $contact_name ) : '' ) . ':mailto:' . sanitize_email( $contact );
    }
    $lines[] = 'END:VEVENT';
    return $lines;
}

/**
 * Returns a master VEVENT plus RRULE/RDATE/EXDATE.
 */
function modern_catholic_events_icalendar_master_lines( $series, $first_occurrence ) {
    $start = modern_catholic_events_get_datetime( $series->ID, 'start' );
    if ( $start ) {
        $master_occurrence = modern_catholic_events_build_occurrence( $series, $start );
        if ( $master_occurrence ) {
            $first_occurrence = $master_occurrence;
        }
    }

    $lines = modern_catholic_events_icalendar_occurrence_lines( $first_occurrence, false );
    array_pop( $lines );
    $rrule = modern_catholic_events_icalendar_rrule( $series->ID );
    if ( $rrule ) {
        $lines[] = 'RRULE:' . $rrule;
    }
    $additional = modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $series->ID, 'additional_dates' ) );
    $excluded   = modern_catholic_events_sanitize_date_list( modern_catholic_events_get_meta( $series->ID, 'excluded_dates' ) );
    $all_day    = (bool) modern_catholic_events_get_meta( $series->ID, 'all_day' );
    if ( $start && $additional ) {
        $values = array();
        foreach ( $additional as $date ) {
            $item = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() )->setTime( (int) $start->format( 'H' ), (int) $start->format( 'i' ) );
            $values[] = $all_day ? $item->format( 'Ymd' ) : $item->format( 'Ymd\THis' );
        }
        $lines[] = 'RDATE' . ( $all_day ? ';VALUE=DATE' : ';TZID=' . modern_catholic_events_icalendar_escape( wp_timezone_string() ) ) . ':' . implode( ',', $values );
    }
    if ( $start && $excluded ) {
        $values = array();
        foreach ( $excluded as $date ) {
            $item = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() )->setTime( (int) $start->format( 'H' ), (int) $start->format( 'i' ) );
            $values[] = $all_day ? $item->format( 'Ymd' ) : $item->format( 'Ymd\THis' );
        }
        $lines[] = 'EXDATE' . ( $all_day ? ';VALUE=DATE' : ';TZID=' . modern_catholic_events_icalendar_escape( wp_timezone_string() ) ) . ':' . implode( ',', $values );
    }
    $lines[] = 'END:VEVENT';
    return $lines;
}

/**
 * Builds a complete bounded calendar or individual event download.
 */
function modern_catholic_events_build_icalendar( $category = '', $single = false ) {
    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Modern Catholic//Parish Events ' . MODERN_CATHOLIC_EVENTS_VERSION . '//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . modern_catholic_events_icalendar_escape( get_bloginfo( 'name' ) . ' ' . __( 'Events', 'modern-catholic-parish-events' ) ),
        'X-WR-TIMEZONE:' . modern_catholic_events_icalendar_escape( wp_timezone_string() ),
    );

    if ( is_array( $single ) ) {
        $lines = array_merge( $lines, modern_catholic_events_icalendar_occurrence_lines( $single, false ) );
    } else {
        $start       = new DateTimeImmutable( '-30 days', wp_timezone() );
        $end         = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' )->setTime( 23, 59, 59 );
        $occurrences = modern_catholic_events_get_occurrences( $start, $end, array( 'category' => $category ) );

        if ( $category ) {
            foreach ( $occurrences as $occurrence ) {
                $lines = array_merge( $lines, modern_catholic_events_icalendar_occurrence_lines( $occurrence, false ) );
            }
        } else {
            $grouped = array();
            foreach ( $occurrences as $occurrence ) {
                $grouped[ $occurrence['series_id'] ][] = $occurrence;
            }
            foreach ( $grouped as $series_id => $series_occurrences ) {
                $series = get_post( $series_id );
                if ( ! $series ) {
                    continue;
                }
                $lines = array_merge( $lines, modern_catholic_events_icalendar_master_lines( $series, $series_occurrences[0] ) );
                foreach ( $series_occurrences as $occurrence ) {
                    if ( $occurrence['override_id'] ) {
                        $lines = array_merge( $lines, modern_catholic_events_icalendar_occurrence_lines( $occurrence, true ) );
                    }
                }
            }
        }
    }

    $lines[] = 'END:VCALENDAR';
    return modern_catholic_events_icalendar_lines( $lines );
}

/**
 * Serves public calendar endpoints before page rendering.
 */
function modern_catholic_events_serve_icalendar() {
    $single = false;
    if ( get_query_var( 'modern_catholic_event_ical' ) ) {
        $single = modern_catholic_events_find_occurrence( get_query_var( 'modern_catholic_event_slug' ), get_query_var( 'modern_catholic_event_date' ) );
        if ( ! $single ) {
            status_header( 404 );
            exit;
        }
    } elseif ( ! get_query_var( 'modern_catholic_events_ical' ) ) {
        return;
    }

    $category = sanitize_title( get_query_var( 'modern_catholic_events_category' ) );
    $download = (bool) get_query_var( 'modern_catholic_events_download' ) || (bool) $single;
    $filename = $single ? sanitize_file_name( $single['slug'] . '-' . substr( $single['recurrence_id'], 0, 10 ) . '.ics' ) : sanitize_file_name( ( $category ?: 'all-events' ) . '.ics' );

    nocache_headers();
    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: ' . ( $download ? 'attachment' : 'inline' ) . '; filename="' . $filename . '"' );
    header( 'X-Content-Type-Options: nosniff' );
    echo modern_catholic_events_build_icalendar( $category, $single ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action( 'template_redirect', 'modern_catholic_events_serve_icalendar', 0 );
