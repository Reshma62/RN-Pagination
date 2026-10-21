<?php
/**
 * Plugin Name: RN Pagination
 * Description: One Elementor pagination widget for everything — WordPress archives / blog / search / shop (main query), JetEngine Listing Grid (AJAX) and Elementor Posts / Loop Grid (AJAX).
 * Version:     2.0.0
 * Author:      Reshma Khanam
 * Author URI:  https://devreshmakhanam.com
 * Plugin URI:  https://github.com/Reshma62/rn-pagination
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rn-pagination
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 */

defined( 'ABSPATH' ) || exit;

define( 'RNP_VERSION', '2.0.0' );
define( 'RNP_FILE', __FILE__ );
define( 'RNP_DIR', __DIR__ );
define( 'RNP_URL', plugin_dir_url( __FILE__ ) );

require_once RNP_DIR . '/includes/class-rnp-plugin.php';

add_action( 'plugins_loaded', array( 'RNP_Plugin', 'init' ) );
