<?php
/**
 * Plugin Name:       Be API Key Figure block
 * Description:       Key Figure block for WordPress.
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Version:           1.0.0
 * Author:            Be API Technical team
 * Author URI:        https://beapi.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockparty-key-figure
 * Domain Path:       /languages
 */

namespace Blockparty\Key_Figure;

define( 'BLOCKPARTY_KEY_FIGURE_VERSION', '1.2.0' );
define( 'BLOCKPARTY_KEY_FIGURE_URL', plugin_dir_url( __FILE__ ) );
define( 'BLOCKPARTY_KEY_FIGURE_DIR', plugin_dir_path( __FILE__ ) );

define(
	'BLOCKPARTY_KEY_FIGURE_NUMBER_FORMAT_LOCALES',
	[
		'fr-FR',
		'en-EN',
		'de-DE',
	]
);

/**
 * Init
 *
 * @return void
 */
function init() {
	load_plugin_textdomain( 'blockparty-key-figure', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	register_block_type( __DIR__ . '/build' );

	wp_set_script_translations( 'blockparty-key-figure-editor-script', 'blockparty-key-figure', plugin_dir_path( __FILE__ ) . '/languages' );

	$constants = [
		'numberFormatOptions' => apply_filters(
			'blockparty_key_figure_number_format_options',
			[]
		),
		'numberFormatLocales' => apply_filters(
			'blockparty_key_figure_number_format_locales',
			BLOCKPARTY_KEY_FIGURE_NUMBER_FORMAT_LOCALES
		),
	];
	wp_localize_script( 'blockparty-key-figure-editor-script', 'beapiKeyFigureBlock', $constants );

	do_action( 'blockparty_key_figure_block_init' );
}

add_action( 'init', __NAMESPACE__ . '\\init' );
