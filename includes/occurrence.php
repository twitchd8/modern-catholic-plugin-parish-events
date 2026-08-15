<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds stable dated occurrence and individual calendar-download routes.
 */
function modern_catholic_events_add_occurrence_rewrites() {
    add_rewrite_rule( '^events/([^/]+)/(\d{4}-\d{2}-\d{2})/event\.ics$', 'index.php?modern_catholic_event_slug=$matches[1]&modern_catholic_event_date=$matches[2]&modern_catholic_event_ical=1', 'top' );
    add_rewrite_rule( '^events/([^/]+)/(\d{4}-\d{2}-\d{2})/?$', 'index.php?modern_catholic_event_slug=$matches[1]&modern_catholic_event_date=$matches[2]', 'top' );
}
add_action( 'init', 'modern_catholic_events_add_occurrence_rewrites', 10 );

function modern_catholic_events_query_vars( $query_vars ) {
    $query_vars[] = 'modern_catholic_event_slug';
    $query_vars[] = 'modern_catholic_event_date';
    $query_vars[] = 'modern_catholic_event_ical';
    return $query_vars;
}
add_filter( 'query_vars', 'modern_catholic_events_query_vars' );

/**
 * Makes a dated virtual occurrence resolve through the native series post.
 */
function modern_catholic_events_prepare_occurrence_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    $slug = $query->get( 'modern_catholic_event_slug' );
    $date = $query->get( 'modern_catholic_event_date' );
    if ( $slug && $date ) {
        $query->set( 'post_type', 'mc_event' );
        $query->set( 'name', sanitize_title( $slug ) );
        $query->set( 'posts_per_page', 1 );
        $query->is_home              = false;
        $query->is_page              = false;
        $query->is_archive           = false;
        $query->is_post_type_archive = false;
        $query->is_singular          = true;
        $query->is_single            = true;
    }
}
add_action( 'pre_get_posts', 'modern_catholic_events_prepare_occurrence_query' );

/**
 * Resolves the effective occurrence and bounds future virtual URLs.
 */
function modern_catholic_events_resolve_request() {
    global $modern_catholic_events_current_occurrence, $wp_query;

    $slug = get_query_var( 'modern_catholic_event_slug' );
    $date = get_query_var( 'modern_catholic_event_date' );
    if ( $slug && $date ) {
        $requested = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
        $horizon   = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' )->setTime( 23, 59, 59 );
        if ( ! $requested || $requested > $horizon ) {
            $wp_query->set_404();
            status_header( 404 );
            return;
        }

        $modern_catholic_events_current_occurrence = modern_catholic_events_find_occurrence( $slug, $date );
        if ( ! $modern_catholic_events_current_occurrence ) {
            $wp_query->set_404();
            status_header( 404 );
            return;
        }

        $wp_query->is_404      = false;
        $wp_query->is_singular = true;
        $wp_query->is_single   = true;
        $wp_query->is_archive  = false;
        $wp_query->is_home     = false;
        $wp_query->is_page     = false;
        $wp_query->is_post_type_archive = false;
        return;
    }

    if ( is_singular( 'mc_event' ) ) {
        $post = get_queried_object();
        if ( $post && ! (int) modern_catholic_events_get_meta( $post->ID, 'series_id' ) ) {
            $start = new DateTimeImmutable( 'today', wp_timezone() );
            $items = modern_catholic_events_get_occurrences( $start->modify( '-1 day' ), $start->modify( '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months' ) );
            foreach ( $items as $occurrence ) {
                if ( (int) $occurrence['series_id'] === (int) $post->ID ) {
                    wp_safe_redirect( $occurrence['permalink'], 301 );
                    exit;
                }
            }
        }
    }
}
add_action( 'template_redirect', 'modern_catholic_events_resolve_request', 1 );

function modern_catholic_events_disable_canonical_redirect( $redirect_url ) {
    return get_query_var( 'modern_catholic_event_slug' ) ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'modern_catholic_events_disable_canonical_redirect' );

/**
 * Returns the effective occurrence for the current request.
 */
function modern_catholic_events_current_occurrence() {
    global $modern_catholic_events_current_occurrence;
    return is_array( $modern_catholic_events_current_occurrence ) ? $modern_catholic_events_current_occurrence : null;
}

/**
 * Returns the theme-compatible occurrence template markup.
 */
function modern_catholic_events_occurrence_template_content() {
    return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
        . '<!-- wp:group {"tagName":"main","className":"modern-catholic-events-occurrence-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->'
        . '<main class="wp-block-group modern-catholic-events-occurrence-main" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--70)">'
        . '<!-- wp:modern-catholic/event-occurrence /-->'
        . '</main><!-- /wp:group -->'
        . '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}

/**
 * Registers the Site Editor template named Event.
 */
function modern_catholic_events_register_occurrence_template() {
    register_block_type( 'modern-catholic/event-occurrence', array( 'api_version' => 3, 'render_callback' => 'modern_catholic_events_render_occurrence' ) );
    if ( function_exists( 'register_block_template' ) ) {
        register_block_template(
            'modern-catholic-plugin-parish-events//single-mc_event',
            array(
                'title'       => __( 'Event', 'modern-catholic-parish-events' ),
                'description' => __( 'Displays one effective event occurrence.', 'modern-catholic-parish-events' ),
                'content'     => modern_catholic_events_occurrence_template_content(),
                'post_types'  => array( 'mc_event' ),
            )
        );
    }
}
add_action( 'init', 'modern_catholic_events_register_occurrence_template', 25 );

/**
 * Uses a portable occurrence template with classic themes.
 */
function modern_catholic_events_classic_occurrence_template( $template ) {
    if ( get_query_var( 'modern_catholic_event_slug' ) && ! wp_is_block_theme() ) {
        return MODERN_CATHOLIC_EVENTS_PATH . 'templates/single-mc_event.php';
    }
    return $template;
}
add_filter( 'template_include', 'modern_catholic_events_classic_occurrence_template', 30 );

/**
 * Formats the visible occurrence date.
 */
function modern_catholic_events_occurrence_date_text( $occurrence ) {
    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );
    if ( $occurrence['all_day'] ) {
        $last = $occurrence['end']->modify( '-1 day' );
        if ( $last->format( 'Y-m-d' ) !== $occurrence['start']->format( 'Y-m-d' ) ) {
            return wp_date( $date_format, $occurrence['start']->getTimestamp(), wp_timezone() ) . '–' . wp_date( $date_format, $last->getTimestamp(), wp_timezone() ) . ' · ' . __( 'All day', 'modern-catholic-parish-events' );
        }
        return wp_date( $date_format, $occurrence['start']->getTimestamp(), wp_timezone() ) . ' · ' . __( 'All day', 'modern-catholic-parish-events' );
    }
    return wp_date( $date_format . ', ' . $time_format, $occurrence['start']->getTimestamp(), wp_timezone() ) . '–' . wp_date( $occurrence['start']->format( 'Y-m-d' ) === $occurrence['end']->format( 'Y-m-d' ) ? $time_format : $date_format . ', ' . $time_format, $occurrence['end']->getTimestamp(), wp_timezone() );
}

/**
 * Renders the effective event occurrence page.
 */
function modern_catholic_events_render_occurrence() {
    $occurrence = modern_catholic_events_current_occurrence();
    if ( ! $occurrence ) {
        return '<p>' . esc_html__( 'This event occurrence could not be found.', 'modern-catholic-parish-events' ) . '</p>';
    }

    $post_id          = $occurrence['post_id'];
    $location_type    = modern_catholic_events_get_meta( $post_id, 'location_type' );
    $venue            = modern_catholic_events_get_meta( $post_id, 'venue_name' );
    $address          = modern_catholic_events_get_meta( $post_id, 'formatted_address' );
    $online_url       = modern_catholic_events_get_meta( $post_id, 'online_url' );
    $registration_url = modern_catholic_events_get_meta( $post_id, 'registration_url' );
    $registration_label = modern_catholic_events_get_meta( $post_id, 'registration_label' ) ?: __( 'Register', 'modern-catholic-parish-events' );
    $contact_name     = modern_catholic_events_get_meta( $post_id, 'contact_name' );
    $contact_email    = modern_catholic_events_get_meta( $post_id, 'contact_email' );
    $contact_phone    = modern_catholic_events_get_meta( $post_id, 'contact_phone' );
    $maps_url         = modern_catholic_events_google_maps_url( $post_id );
    $terms            = wp_get_object_terms( $post_id, 'mc_event_category' );

    ob_start();
    ?>
    <article class="modern-catholic-events-occurrence alignwide is-status-<?php echo esc_attr( $occurrence['event_status'] ); ?>">
        <header class="modern-catholic-events-occurrence__header">
            <?php if ( 'scheduled' !== $occurrence['event_status'] ) : ?><p class="modern-catholic-events-status"><?php echo esc_html( ucfirst( $occurrence['event_status'] ) ); ?></p><?php endif; ?>
            <h1><?php echo esc_html( $occurrence['title'] ); ?></h1>
            <p class="modern-catholic-events-occurrence__date"><time datetime="<?php echo esc_attr( $occurrence['start']->format( DATE_ATOM ) ); ?>"><?php echo esc_html( modern_catholic_events_occurrence_date_text( $occurrence ) ); ?></time></p>
        </header>

        <?php if ( has_post_thumbnail( $post_id ) ) : ?><figure class="modern-catholic-events-occurrence__image"><?php echo get_the_post_thumbnail( $post_id, 'large' ); ?></figure><?php endif; ?>

        <div class="modern-catholic-events-occurrence__layout">
            <div class="modern-catholic-events-occurrence__content"><?php echo apply_filters( 'the_content', $occurrence['content'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <aside class="modern-catholic-events-occurrence__details" aria-label="<?php esc_attr_e( 'Event details', 'modern-catholic-parish-events' ); ?>">
                <?php if ( in_array( $location_type, array( 'in_person', 'hybrid' ), true ) && ( $venue || $address ) ) : ?>
                    <section><h2><?php esc_html_e( 'Location', 'modern-catholic-parish-events' ); ?></h2><?php if ( $venue ) : ?><p><strong><?php echo esc_html( $venue ); ?></strong></p><?php endif; ?><?php if ( $address ) : ?><p><?php echo esc_html( $address ); ?></p><?php endif; ?><?php if ( $maps_url ) : ?><p><a href="<?php echo esc_url( $maps_url ); ?>" rel="external"><?php esc_html_e( 'View in Google Maps', 'modern-catholic-parish-events' ); ?></a></p><?php endif; ?></section>
                <?php elseif ( 'to_be_announced' === $location_type ) : ?><section><h2><?php esc_html_e( 'Location', 'modern-catholic-parish-events' ); ?></h2><p><?php esc_html_e( 'To be announced', 'modern-catholic-parish-events' ); ?></p></section><?php endif; ?>
                <?php if ( in_array( $location_type, array( 'online', 'hybrid' ), true ) && $online_url ) : ?><section><h2><?php esc_html_e( 'Online', 'modern-catholic-parish-events' ); ?></h2><p><a href="<?php echo esc_url( $online_url ); ?>" rel="external nofollow"><?php esc_html_e( 'Join online event', 'modern-catholic-parish-events' ); ?></a></p></section><?php endif; ?>
                <?php if ( $registration_url ) : ?><section><h2><?php esc_html_e( 'Registration', 'modern-catholic-parish-events' ); ?></h2><p><a class="wp-element-button" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_html( $registration_label ); ?></a></p></section><?php endif; ?>
                <?php if ( $contact_name || $contact_email || $contact_phone ) : ?><section><h2><?php esc_html_e( 'Contact', 'modern-catholic-parish-events' ); ?></h2><?php if ( $contact_name ) : ?><p><?php echo esc_html( $contact_name ); ?></p><?php endif; ?><?php if ( $contact_email ) : ?><p><a href="mailto:<?php echo esc_attr( antispambot( $contact_email ) ); ?>"><?php echo esc_html( antispambot( $contact_email ) ); ?></a></p><?php endif; ?><?php if ( $contact_phone ) : ?><p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></p><?php endif; ?></section><?php endif; ?>
                <section><h2><?php esc_html_e( 'Calendar', 'modern-catholic-parish-events' ); ?></h2><p><a href="<?php echo esc_url( modern_catholic_events_feed_url( '', true, $occurrence ) ); ?>"><?php esc_html_e( 'Add This Event to My Calendar', 'modern-catholic-parish-events' ); ?></a></p></section>
            </aside>
        </div>
        <?php if ( ! is_wp_error( $terms ) && $terms ) : ?><footer class="modern-catholic-events-occurrence__categories"><span><?php esc_html_e( 'Event Categories:', 'modern-catholic-parish-events' ); ?></span> <?php foreach ( $terms as $index => $term ) : echo $index ? ', ' : ''; ?><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a><?php endforeach; ?></footer><?php endif; ?>
        <?php if ( current_user_can( 'edit_post', $occurrence['series_id'] ) ) : ?>
            <nav class="modern-catholic-events-occurrence__edit" aria-label="<?php esc_attr_e( 'Edit recurring event', 'modern-catholic-parish-events' ); ?>">
                <span><?php esc_html_e( 'Edit:', 'modern-catholic-parish-events' ); ?></span>
                <a href="<?php echo esc_url( modern_catholic_events_edit_occurrence_url( $occurrence, 'occurrence' ) ); ?>"><?php esc_html_e( 'This occurrence only', 'modern-catholic-parish-events' ); ?></a>
                <a href="<?php echo esc_url( modern_catholic_events_edit_occurrence_url( $occurrence, 'following' ) ); ?>"><?php esc_html_e( 'This and following', 'modern-catholic-parish-events' ); ?></a>
                <a href="<?php echo esc_url( modern_catholic_events_edit_occurrence_url( $occurrence, 'series' ) ); ?>"><?php esc_html_e( 'Entire series', 'modern-catholic-parish-events' ); ?></a>
            </nav>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}
