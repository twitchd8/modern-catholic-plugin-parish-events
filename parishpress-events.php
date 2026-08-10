<?php
/**
 * Plugin Name: Modern Catholic – Parish Events
 * Plugin URI: https://github.com/twitchd8/modern-catholic-plugin-parish-events
 * Description: Parish events for Modern Catholic websites as a custom post type with dates and location.
 * Version: 0.2.0
 * Author: Andrew Schmitt
 * License: GPL-3.0-only
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: parishpress-events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Legacy PARISHPRESS_* constants remain part of the plugin's compatibility surface.
define( 'PARISHPRESS_EVENTS_VERSION', '0.2.0' );
define( 'PARISHPRESS_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PARISHPRESS_EVENTS_URL', plugin_dir_url( __FILE__ ) );

require PARISHPRESS_EVENTS_PATH . 'includes/cpt.php';
require PARISHPRESS_EVENTS_PATH . 'includes/meta.php';
require PARISHPRESS_EVENTS_PATH . 'includes/shortcode.php';
require PARISHPRESS_EVENTS_PATH . 'includes/assets.php';
require PARISHPRESS_EVENTS_PATH . 'includes/block.php';
