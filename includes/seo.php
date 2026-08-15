<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Maps an editorial status to Schema.org.
 */
function modern_catholic_events_schema_status( $status ) {
    $map = array(
        'scheduled'   => 'https://schema.org/EventScheduled',
        'canceled'    => 'https://schema.org/EventCancelled',
        'postponed'   => 'https://schema.org/EventPostponed',
        'rescheduled' => 'https://schema.org/EventRescheduled',
    );
    return isset( $map[ $status ] ) ? $map[ $status ] : $map['scheduled'];
}

/**
 * Produces one occurrence-focused Schema.org Event object.
 *
 * @param array $occurrence Effective occurrence.
 * @return array<string,mixed>
 */
function modern_catholic_events_schema_data( $occurrence ) {
    $post_id       = $occurrence['post_id'];
    $location_type = modern_catholic_events_get_meta( $post_id, 'location_type' );
    $venue          = modern_catholic_events_get_meta( $post_id, 'venue_name' );
    $online_url     = modern_catholic_events_get_meta( $post_id, 'online_url' );
    $registration   = modern_catholic_events_get_meta( $post_id, 'registration_url' );
    $price          = (float) modern_catholic_events_get_meta( $post_id, 'registration_price' );
    $currency       = modern_catholic_events_get_meta( $post_id, 'registration_currency' ) ?: 'USD';
    $description    = $occurrence['excerpt'] ?: wp_trim_words( wp_strip_all_tags( $occurrence['content'] ), 40 );
    $all_day        = $occurrence['all_day'];
    $end            = $all_day ? $occurrence['end']->modify( '-1 day' ) : $occurrence['end'];

    $schema = array(
        '@context'            => 'https://schema.org',
        '@type'               => 'Event',
        '@id'                 => $occurrence['permalink'] . '#event',
        'url'                 => $occurrence['permalink'],
        'name'                => $occurrence['title'],
        'description'         => $description,
        'startDate'           => $all_day ? $occurrence['start']->format( 'Y-m-d' ) : $occurrence['start']->format( DATE_ATOM ),
        'endDate'             => $all_day ? $end->format( 'Y-m-d' ) : $end->format( DATE_ATOM ),
        'eventStatus'         => modern_catholic_events_schema_status( $occurrence['event_status'] ),
        'eventAttendanceMode' => 'online' === $location_type ? 'https://schema.org/OnlineEventAttendanceMode' : ( 'hybrid' === $location_type ? 'https://schema.org/MixedEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode' ),
        'organizer'           => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
    );

    if ( 'rescheduled' === $occurrence['event_status'] ) {
        $previous = modern_catholic_events_parse_local_value( modern_catholic_events_get_meta( $post_id, 'previous_start' ) );
        if ( $previous ) {
            $schema['previousStartDate'] = $all_day ? $previous->format( 'Y-m-d' ) : $previous->format( DATE_ATOM );
        }
    }

    $physical_location = null;
    if ( in_array( $location_type, array( 'in_person', 'hybrid' ), true ) ) {
        $physical_location = array(
            '@type'   => 'Place',
            'name'    => $venue ?: modern_catholic_events_get_meta( $post_id, 'formatted_address' ),
            'address' => array_filter(
                array(
                    '@type'           => 'PostalAddress',
                    'streetAddress'    => modern_catholic_events_get_meta( $post_id, 'street_address' ),
                    'addressLocality'  => modern_catholic_events_get_meta( $post_id, 'address_locality' ),
                    'addressRegion'    => modern_catholic_events_get_meta( $post_id, 'address_region' ),
                    'postalCode'       => modern_catholic_events_get_meta( $post_id, 'postal_code' ),
                    'addressCountry'   => modern_catholic_events_get_meta( $post_id, 'address_country' ),
                )
            ),
        );
        $latitude  = (float) modern_catholic_events_get_meta( $post_id, 'latitude' );
        $longitude = (float) modern_catholic_events_get_meta( $post_id, 'longitude' );
        if ( $latitude || $longitude ) {
            $physical_location['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => $latitude, 'longitude' => $longitude );
        }
    }

    $virtual_location = $online_url ? array( '@type' => 'VirtualLocation', 'url' => $online_url ) : null;
    if ( 'hybrid' === $location_type ) {
        $schema['location'] = array_values( array_filter( array( $physical_location, $virtual_location ) ) );
    } elseif ( 'online' === $location_type ) {
        $schema['location'] = $virtual_location;
    } elseif ( $physical_location ) {
        $schema['location'] = $physical_location;
    }

    $image = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( $image ) {
        $schema['image'] = array( $image );
    }
    if ( $registration ) {
        $schema['offers'] = array_filter(
            array(
                '@type'         => 'Offer',
                'url'           => $registration,
                'availability'  => 'https://schema.org/InStock',
                'price'         => $price >= 0 ? $price : null,
                'priceCurrency' => $price >= 0 ? $currency : null,
            ),
            static function ( $value ) { return null !== $value && '' !== $value; }
        );
        if ( 0.0 === $price ) {
            $schema['isAccessibleForFree'] = true;
        }
    }
    return $schema;
}

/**
 * Replaces the document title on a virtual occurrence.
 */
function modern_catholic_events_document_title( $title ) {
    $occurrence = modern_catholic_events_current_occurrence();
    return $occurrence ? $occurrence['title'] . ' – ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'modern_catholic_events_document_title' );

/**
 * Emits canonical, description, social metadata, and one Event JSON-LD object.
 */
function modern_catholic_events_head_metadata() {
    $occurrence = modern_catholic_events_current_occurrence();
    if ( ! $occurrence ) {
        return;
    }
    $description = $occurrence['excerpt'] ?: wp_trim_words( wp_strip_all_tags( $occurrence['content'] ), 32 );
    $image       = get_the_post_thumbnail_url( $occurrence['post_id'], 'full' );
    $schema      = modern_catholic_events_schema_data( $occurrence );
    remove_action( 'wp_head', 'rel_canonical' );
    ?>
    <link rel="canonical" href="<?php echo esc_url( $occurrence['permalink'] ); ?>">
    <meta name="description" content="<?php echo esc_attr( $description ); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc_attr( $occurrence['title'] ); ?>">
    <meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
    <meta property="og:url" content="<?php echo esc_url( $occurrence['permalink'] ); ?>">
    <?php if ( $image ) : ?><meta property="og:image" content="<?php echo esc_url( $image ); ?>"><?php endif; ?>
    <script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
    <?php
}
add_action( 'wp_head', 'modern_catholic_events_head_metadata', 1 );

/**
 * Removes Event objects from common SEO-plugin graphs on occurrence pages.
 *
 * The plugin still leaves all unrelated WebPage, Organization, and breadcrumb nodes intact.
 */
function modern_catholic_events_remove_duplicate_event_schema( $graph ) {
    if ( ! modern_catholic_events_current_occurrence() || ! is_array( $graph ) ) {
        return $graph;
    }
    foreach ( $graph as $key => $node ) {
        if ( is_array( $node ) ) {
            $types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
            if ( in_array( 'Event', $types, true ) || in_array( 'https://schema.org/Event', $types, true ) ) {
                unset( $graph[ $key ] );
            }
        }
    }
    return $graph;
}
add_filter( 'wpseo_schema_graph', 'modern_catholic_events_remove_duplicate_event_schema', 20 );
add_filter( 'rank_math/json_ld', 'modern_catholic_events_remove_duplicate_event_schema', 20 );
add_filter( 'aioseo_schema_output', 'modern_catholic_events_remove_duplicate_event_schema', 20 );

if ( class_exists( 'WP_Sitemaps_Provider' ) ) {
    /**
     * Supplies bounded virtual occurrence URLs to WordPress XML sitemaps.
     */
    class Modern_Catholic_Events_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            // Core's sitemap rewrite accepts lowercase letters, but not hyphens.
            $this->name        = 'events';
            $this->object_type = 'events';
        }

        public function get_url_list( $page_num, $object_subtype = '' ) {
            unset( $object_subtype );
            $start = new DateTimeImmutable( 'today', wp_timezone() );
            $items = modern_catholic_events_get_occurrences( $start, $start->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' ) );
            $size  = wp_sitemaps_get_max_urls( $this->object_type );
            $items = array_slice( $items, ( max( 1, (int) $page_num ) - 1 ) * $size, $size );
            return array_map(
                static function ( $occurrence ) {
                    return array( 'loc' => $occurrence['permalink'], 'lastmod' => mysql2date( DATE_W3C, $occurrence['last_modified_gmt'], false ) );
                },
                $items
            );
        }

        public function get_max_num_pages( $object_subtype = '' ) {
            unset( $object_subtype );
            $start = new DateTimeImmutable( 'today', wp_timezone() );
            $count = count( modern_catholic_events_get_occurrences( $start, $start->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' ) ) );
            return $count > 0 ? (int) ceil( $count / wp_sitemaps_get_max_urls( $this->object_type ) ) : 0;
        }
    }
}

function modern_catholic_events_register_sitemap_provider() {
    if ( class_exists( 'Modern_Catholic_Events_Sitemap_Provider' ) ) {
        wp_register_sitemap_provider( 'events', new Modern_Catholic_Events_Sitemap_Provider() );
    }
}
add_action( 'init', 'modern_catholic_events_register_sitemap_provider' );

/**
 * Removes series permalinks from the core post sitemap.
 *
 * Public discovery is occurrence-focused; the custom provider above supplies
 * the canonical dated leaf URLs instead.
 */
function modern_catholic_events_exclude_series_from_post_sitemap( $post_types ) {
    unset( $post_types['mc_event'] );
    return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'modern_catholic_events_exclude_series_from_post_sitemap' );
