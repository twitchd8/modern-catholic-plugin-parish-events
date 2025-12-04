<?php
/**
 * Plugin Name: ParishPress Events
 * Description: Parish events as a custom post type with dates and location.
 * Version: 0.2.0
 * Author: Andrew Schmitt
 * Text Domain: parishpress-events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PARISHPRESS_EVENTS_VERSION', '0.2.0' );
define( 'PARISHPRESS_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PARISHPRESS_EVENTS_URL', plugin_dir_url( __FILE__ ) );

require PARISHPRESS_EVENTS_PATH . 'includes/cpt.php';
require PARISHPRESS_EVENTS_PATH . 'includes/meta.php';
require PARISHPRESS_EVENTS_PATH . 'includes/shortcode.php';
require PARISHPRESS_EVENTS_PATH . 'includes/assets.php';
require PARISHPRESS_EVENTS_PATH . 'includes/block.php';
