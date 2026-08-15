<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the theme-compatible Events archive block markup.
 */
function modern_catholic_events_archive_template_content() {
    return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
        . '<!-- wp:group {"tagName":"main","className":"modern-catholic-events-archive-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->'
        . '<main class="wp-block-group modern-catholic-events-archive-main" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--70)">'
        . '<!-- wp:modern-catholic/events-archive /-->'
        . '</main><!-- /wp:group -->'
        . '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}

/**
 * Registers the Events and Event Category archive templates.
 */
function modern_catholic_events_register_archive_templates() {
    register_block_type( 'modern-catholic/events-archive', array( 'api_version' => 3, 'render_callback' => 'modern_catholic_events_render_archive' ) );

    if ( function_exists( 'register_block_template' ) ) {
        register_block_template(
            'modern-catholic-plugin-parish-events//archive-mc_event',
            array(
                'title'       => __( 'Events', 'modern-catholic-parish-events' ),
                'description' => __( 'Displays filtered event list and monthly calendar views.', 'modern-catholic-parish-events' ),
                'content'     => modern_catholic_events_archive_template_content(),
                'post_types'  => array( 'mc_event' ),
            )
        );
        register_block_template(
            'modern-catholic-plugin-parish-events//taxonomy-mc_event_category',
            array(
                'title'       => __( 'Event Category', 'modern-catholic-parish-events' ),
                'description' => __( 'Displays one Event Category as a crawlable event index.', 'modern-catholic-parish-events' ),
                'content'     => modern_catholic_events_archive_template_content(),
            )
        );
    }
}
add_action( 'init', 'modern_catholic_events_register_archive_templates', 25 );

/**
 * Uses a portable fallback for classic themes.
 */
function modern_catholic_events_classic_archive_template( $template ) {
    if ( ( is_post_type_archive( 'mc_event' ) || is_tax( 'mc_event_category' ) ) && ! wp_is_block_theme() ) {
        return MODERN_CATHOLIC_EVENTS_PATH . 'templates/archive-mc_event.php';
    }
    return $template;
}
add_filter( 'template_include', 'modern_catholic_events_classic_archive_template', 20 );

/**
 * Safely resolves a date/relative range expression in the site timezone.
 */
function modern_catholic_events_resolve_range_value( $value, $fallback ) {
    $value = sanitize_text_field( $value );
    if ( strlen( $value ) > 40 || ! preg_match( '/^[0-9a-zA-Z+\- :]+$/', $value ) ) {
        $value = $fallback;
    }
    try {
        return new DateTimeImmutable( $value ?: $fallback, wp_timezone() );
    } catch ( Exception $error ) {
        return new DateTimeImmutable( $fallback, wp_timezone() );
    }
}

/**
 * Formats an occurrence date/time for the public list.
 */
function modern_catholic_events_format_occurrence_time( $occurrence ) {
    if ( $occurrence['all_day'] ) {
        $last_day = $occurrence['end']->modify( '-1 day' );
        if ( $last_day->format( 'Y-m-d' ) !== $occurrence['start']->format( 'Y-m-d' ) ) {
            return sprintf(
                /* translators: 1: start date, 2: end date. */
                __( '%1$s–%2$s · All day', 'modern-catholic-parish-events' ),
                wp_date( get_option( 'date_format' ), $occurrence['start']->getTimestamp(), wp_timezone() ),
                wp_date( get_option( 'date_format' ), $last_day->getTimestamp(), wp_timezone() )
            );
        }
        return __( 'All day', 'modern-catholic-parish-events' );
    }

    $time_format = get_option( 'time_format' );
    $start_text  = wp_date( $time_format, $occurrence['start']->getTimestamp(), wp_timezone() );
    if ( $occurrence['end']->format( 'Y-m-d' ) === $occurrence['start']->format( 'Y-m-d' ) ) {
        return $start_text . '–' . wp_date( $time_format, $occurrence['end']->getTimestamp(), wp_timezone() );
    }
    return $start_text . '–' . wp_date( get_option( 'date_format' ) . ', ' . $time_format, $occurrence['end']->getTimestamp(), wp_timezone() );
}

/**
 * Renders one occurrence in the list view.
 */
function modern_catholic_events_render_list_occurrence( $occurrence ) {
    $post_id  = $occurrence['post_id'];
    $location = modern_catholic_events_get_meta( $post_id, 'venue_name' );
    $status   = $occurrence['event_status'];
    ?>
    <article class="modern-catholic-events-list__event is-status-<?php echo esc_attr( $status ); ?>">
        <time class="modern-catholic-events-list__date" datetime="<?php echo esc_attr( $occurrence['start']->format( DATE_ATOM ) ); ?>">
            <span class="modern-catholic-events-list__month"><?php echo esc_html( wp_date( 'M', $occurrence['start']->getTimestamp(), wp_timezone() ) ); ?></span>
            <span class="modern-catholic-events-list__day"><?php echo esc_html( wp_date( 'j', $occurrence['start']->getTimestamp(), wp_timezone() ) ); ?></span>
            <span class="modern-catholic-events-list__weekday"><?php echo esc_html( wp_date( 'D', $occurrence['start']->getTimestamp(), wp_timezone() ) ); ?></span>
        </time>
        <div class="modern-catholic-events-list__details">
            <div class="modern-catholic-events-list__heading">
                <h2 class="modern-catholic-events-list__title"><a href="<?php echo esc_url( $occurrence['permalink'] ); ?>"><?php echo esc_html( $occurrence['title'] ); ?></a></h2>
                <?php if ( 'scheduled' !== $status ) : ?><span class="modern-catholic-events-status"><?php echo esc_html( ucfirst( $status ) ); ?></span><?php endif; ?>
            </div>
            <p class="modern-catholic-events-list__meta">
                <span><?php echo esc_html( modern_catholic_events_format_occurrence_time( $occurrence ) ); ?></span>
                <?php if ( $location ) : ?><span><?php echo esc_html( $location ); ?></span><?php endif; ?>
            </p>
            <?php if ( $occurrence['excerpt'] ) : ?><p class="modern-catholic-events-list__excerpt"><?php echo esc_html( $occurrence['excerpt'] ); ?></p><?php endif; ?>
        </div>
    </article>
    <?php
}

/**
 * Renders a list of effective occurrences.
 */
function modern_catholic_events_render_list( $occurrences ) {
    echo '<section class="modern-catholic-events-list" aria-label="' . esc_attr__( 'Events list', 'modern-catholic-parish-events' ) . '">';
    if ( ! $occurrences ) {
        echo '<p class="modern-catholic-events-empty">' . esc_html__( 'There are no events in this date range.', 'modern-catholic-parish-events' ) . '</p>';
    } else {
        foreach ( $occurrences as $occurrence ) {
            modern_catholic_events_render_list_occurrence( $occurrence );
        }
    }
    echo '</section>';
}

/**
 * Renders a responsive, keyboard-scrollable monthly occurrence calendar.
 */
function modern_catholic_events_render_calendar( $month_start, $occurrences, $category = '' ) {
    $month_start  = $month_start->modify( 'first day of this month' )->setTime( 0, 0 );
    $month_end    = $month_start->modify( 'last day of this month' )->setTime( 23, 59, 59 );
    $events_by_day = array();

    foreach ( $occurrences as $occurrence ) {
        $first = $occurrence['start'] < $month_start ? $month_start : $occurrence['start']->setTime( 0, 0 );
        $last  = $occurrence['end']->setTime( 0, 0 );
        if ( $occurrence['all_day'] ) {
            $last = $last->modify( '-1 day' );
        }
        $last = $last > $month_end ? $month_end->setTime( 0, 0 ) : $last;
        for ( $day = $first->setTime( 0, 0 ); $day <= $last; $day = $day->modify( '+1 day' ) ) {
            $events_by_day[ $day->format( 'Y-m-d' ) ][] = $occurrence;
        }
    }

    $start_of_week = (int) get_option( 'start_of_week', 0 );
    $first_weekday = (int) $month_start->format( 'w' );
    $grid_start    = $month_start->modify( '-' . ( ( $first_weekday - $start_of_week + 7 ) % 7 ) . ' days' );
    $today         = new DateTimeImmutable( 'today', wp_timezone() );
    $archive       = get_post_type_archive_link( 'mc_event' );
    $previous_url  = add_query_arg( array_filter( array( 'event_view' => 'calendar', 'event_month' => $month_start->modify( '-1 month' )->format( 'Y-m' ), 'event_category' => $category ) ), $archive );
    $next_url      = add_query_arg( array_filter( array( 'event_view' => 'calendar', 'event_month' => $month_start->modify( '+1 month' )->format( 'Y-m' ), 'event_category' => $category ) ), $archive );
    ?>
    <section class="modern-catholic-events-calendar" aria-labelledby="modern-catholic-events-calendar-title">
        <nav class="modern-catholic-events-calendar__navigation" aria-label="<?php esc_attr_e( 'Calendar month navigation', 'modern-catholic-parish-events' ); ?>">
            <a class="modern-catholic-events-calendar__month-link" href="<?php echo esc_url( $previous_url ); ?>" rel="prev"><span aria-hidden="true">&larr;</span> <?php echo esc_html( wp_date( 'F', $month_start->modify( '-1 month' )->getTimestamp(), wp_timezone() ) ); ?></a>
            <h2 id="modern-catholic-events-calendar-title"><?php echo esc_html( wp_date( 'F Y', $month_start->getTimestamp(), wp_timezone() ) ); ?></h2>
            <a class="modern-catholic-events-calendar__month-link" href="<?php echo esc_url( $next_url ); ?>" rel="next"><?php echo esc_html( wp_date( 'F', $month_start->modify( '+1 month' )->getTimestamp(), wp_timezone() ) ); ?> <span aria-hidden="true">&rarr;</span></a>
        </nav>
        <div class="modern-catholic-events-calendar__scroll" tabindex="0" role="region" aria-label="<?php echo esc_attr( wp_date( 'F Y', $month_start->getTimestamp(), wp_timezone() ) ); ?>">
            <table class="modern-catholic-events-calendar__table">
                <caption class="screen-reader-text"><?php echo esc_html( wp_date( 'F Y', $month_start->getTimestamp(), wp_timezone() ) ); ?></caption>
                <thead><tr>
                    <?php for ( $index = 0; $index < 7; ++$index ) : $weekday = $grid_start->modify( '+' . $index . ' days' ); ?>
                        <th scope="col"><?php echo esc_html( wp_date( 'D', $weekday->getTimestamp(), wp_timezone() ) ); ?></th>
                    <?php endfor; ?>
                </tr></thead>
                <tbody>
                    <?php for ( $week = 0; $week < 6; ++$week ) : ?><tr>
                        <?php for ( $weekday = 0; $weekday < 7; ++$weekday ) :
                            $date      = $grid_start->modify( '+' . ( ( $week * 7 ) + $weekday ) . ' days' );
                            $date_key  = $date->format( 'Y-m-d' );
                            $in_month  = $date->format( 'Y-m' ) === $month_start->format( 'Y-m' );
                            $is_today  = $date_key === $today->format( 'Y-m-d' );
                            $classes   = 'modern-catholic-events-calendar__day' . ( $in_month ? '' : ' is-outside-month' ) . ( $is_today ? ' is-today' : '' );
                            ?>
                            <td class="<?php echo esc_attr( $classes ); ?>">
                                <time class="modern-catholic-events-calendar__date" datetime="<?php echo esc_attr( $date_key ); ?>"<?php echo $is_today ? ' aria-current="date"' : ''; ?>><?php echo esc_html( $date->format( 'j' ) ); ?></time>
                                <?php if ( $in_month && ! empty( $events_by_day[ $date_key ] ) ) : ?><ul class="modern-catholic-events-calendar__events">
                                    <?php foreach ( $events_by_day[ $date_key ] as $occurrence ) : ?>
                                        <li><a class="is-status-<?php echo esc_attr( $occurrence['event_status'] ); ?>" href="<?php echo esc_url( $occurrence['permalink'] ); ?>"><span class="modern-catholic-events-calendar__event-time"><?php echo esc_html( $occurrence['all_day'] ? __( 'All day', 'modern-catholic-parish-events' ) : ( $occurrence['start']->format( 'Y-m-d' ) === $date_key ? wp_date( get_option( 'time_format' ), $occurrence['start']->getTimestamp(), wp_timezone() ) : __( 'Continues', 'modern-catholic-parish-events' ) ) ); ?></span><span class="modern-catholic-events-calendar__event-title"><?php echo esc_html( $occurrence['title'] ); ?></span></a></li>
                                    <?php endforeach; ?>
                                </ul><?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr><?php endfor; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}

/**
 * Renders collection filters and either view through the central service.
 */
function modern_catholic_events_render_collection( $args = array() ) {
    $args = wp_parse_args( $args, array( 'start' => 'today', 'end' => '+12 months', 'view' => 'list', 'category' => '', 'limit' => -1, 'heading' => true, 'filters' => false ) );
    $start = modern_catholic_events_resolve_range_value( $args['start'], 'today' )->setTime( 0, 0 );
    $end   = modern_catholic_events_resolve_range_value( $args['end'], '+12 months' )->setTime( 23, 59, 59 );
    $occurrences = modern_catholic_events_get_occurrences( $start, $end, array( 'category' => $args['category'], 'limit' => $args['limit'] ) );

    ob_start();
    if ( 'calendar' === $args['view'] ) {
        modern_catholic_events_render_calendar( $start, $occurrences, $args['category'] );
    } else {
        modern_catholic_events_render_list( $occurrences );
    }
    return ob_get_clean();
}

/**
 * Renders the full archive interface.
 */
function modern_catholic_events_render_archive() {
    $view = isset( $_GET['event_view'] ) ? sanitize_key( wp_unslash( $_GET['event_view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $view = 'calendar' === $view ? 'calendar' : 'list';
    $month_value = isset( $_GET['event_month'] ) ? sanitize_text_field( wp_unslash( $_GET['event_month'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $month = preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month_value ) ? DateTimeImmutable::createFromFormat( '!Y-m', $month_value, wp_timezone() ) : new DateTimeImmutable( 'first day of this month', wp_timezone() );
    $taxonomy_term = is_tax( 'mc_event_category' ) ? get_queried_object() : null;
    $category = $taxonomy_term instanceof WP_Term ? $taxonomy_term->slug : ( isset( $_GET['event_category'] ) ? sanitize_title( wp_unslash( $_GET['event_category'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $title = $taxonomy_term instanceof WP_Term ? $taxonomy_term->name : __( 'Events', 'modern-catholic-parish-events' );
    $archive_url = get_post_type_archive_link( 'mc_event' );
    $list_url = add_query_arg( array_filter( array( 'event_view' => 'list', 'event_category' => $category ) ), $archive_url );
    $calendar_url = add_query_arg( array_filter( array( 'event_view' => 'calendar', 'event_month' => $month->format( 'Y-m' ), 'event_category' => $category ) ), $archive_url );
    $terms = get_terms( array( 'taxonomy' => 'mc_event_category', 'hide_empty' => true ) );

    ob_start();
    ?>
    <div class="modern-catholic-events-archive alignwide">
        <header class="modern-catholic-events-archive__header">
            <div><h1><?php echo esc_html( $title ); ?></h1><?php if ( $taxonomy_term instanceof WP_Term && $taxonomy_term->description ) : ?><p class="modern-catholic-events-archive__intro"><?php echo esc_html( $taxonomy_term->description ); ?></p><?php endif; ?></div>
            <nav class="modern-catholic-events-archive__views" aria-label="<?php esc_attr_e( 'Events view', 'modern-catholic-parish-events' ); ?>">
                <a href="<?php echo esc_url( $list_url ); ?>"<?php echo 'list' === $view ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'List', 'modern-catholic-parish-events' ); ?></a>
                <a href="<?php echo esc_url( $calendar_url ); ?>"<?php echo 'calendar' === $view ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Calendar', 'modern-catholic-parish-events' ); ?></a>
            </nav>
        </header>
        <div class="modern-catholic-events-toolbar">
            <form class="modern-catholic-events-filter" method="get" action="<?php echo esc_url( $archive_url ); ?>">
                <input type="hidden" name="event_view" value="<?php echo esc_attr( $view ); ?>">
                <?php if ( 'calendar' === $view ) : ?><input type="hidden" name="event_month" value="<?php echo esc_attr( $month->format( 'Y-m' ) ); ?>"><?php endif; ?>
                <label for="modern-catholic-events-category-filter"><?php esc_html_e( 'Event Category', 'modern-catholic-parish-events' ); ?></label>
                <select id="modern-catholic-events-category-filter" name="event_category">
                    <option value=""><?php esc_html_e( 'All Event Categories', 'modern-catholic-parish-events' ); ?></option>
                    <?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>"<?php selected( $category, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; endif; ?>
                </select>
                <button type="submit"><?php esc_html_e( 'Filter', 'modern-catholic-parish-events' ); ?></button>
            </form>
            <div class="modern-catholic-events-calendar-actions">
                <a href="<?php echo esc_url( modern_catholic_events_feed_url( $category, false, false ) ); ?>"><?php echo esc_html( $category ? __( 'Subscribe to This Category', 'modern-catholic-parish-events' ) : __( 'Subscribe to All Events', 'modern-catholic-parish-events' ) ); ?></a>
                <a href="<?php echo esc_url( modern_catholic_events_feed_url( $category, true, false ) ); ?>"><?php esc_html_e( 'Download Calendar', 'modern-catholic-parish-events' ); ?></a>
            </div>
        </div>
        <?php
        if ( 'calendar' === $view ) {
            echo modern_catholic_events_render_collection( array( 'start' => $month->format( 'Y-m-01' ), 'end' => $month->modify( 'last day of this month' )->format( 'Y-m-d' ), 'view' => 'calendar', 'category' => $category ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo modern_catholic_events_render_collection( array( 'start' => 'today', 'end' => '+' . MODERN_CATHOLIC_EVENTS_PUBLIC_HORIZON_MONTHS . ' months', 'view' => 'list', 'category' => $category ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

