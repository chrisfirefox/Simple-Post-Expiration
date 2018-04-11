<?php
/**
 * Plugin Name: Simple Post Expiration
 * Plugin URL: http://pippinsplugins.com/simple-post-expiration
 * Description: A simple plugin that allows you to set an expiration date on posts. Once a post is expired, "Expired" will be prefixed to the post title.
 * Version: 1.0.1
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Contributors: mordauk, rzen
 * Text Domain: pw-spe
 * Domain Path: languages
 *
 * Simple Post Expiration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Simple Post Expiration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Simple Post Expiration. If not, see <http://www.gnu.org/licenses/>.
*/

define( 'PW_SPE_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' ) ;

if( is_admin() ) {

	require_once dirname( __FILE__ ) . '/includes/metabox.php';
	require_once dirname( __FILE__ ) . '/includes/settings.php';

}

require_once dirname( __FILE__ ) . '/includes/shortcodes.php';
require_once dirname( __FILE__ ) . '/includes/widgets.php';

/**
 * Load our plugin's text domain to allow it to be translated
 *
 * @access  public
 * @since   1.0
*/
function pw_spe_text_domain() {

	// Load the default language files
	load_plugin_textdomain( 'pw-spe', false, plugin_basename(dirname(__FILE__)). '/languages' );

}
add_action( 'init', 'pw_spe_text_domain' );

/**
 * Determines if a post is expired
 *
 * @access public
 * @since 1.0
 * @return bool
 */
function pw_spe_is_expired( $post_id = 0 ) {

	$expires = get_post_meta( $post_id, 'pw_spe_expiration', true );

	if( ! empty( $expires ) ) {

		// Get the current time and the post's expiration date
		$current_time = current_time( 'timestamp' );
		$expiration   = strtotime( $expires, current_time( 'timestamp' ) );

		// Determine if current time is greater than the expiration date
		if( $current_time >= $expiration ) {

			return true;

		}

	}

	return false;

}

/**
 * Filters the post titles
 *
 * @access public
 * @since 1.0
 * @return void
 */
function pw_spe_filter_title( $title = '', $post_id = 0 ) {

	if( pw_spe_is_expired( $post_id ) ) {

		// Post is expired so attach the prefix
		$prefix = get_option( 'pw_spe_prefix', __( 'Expired:', 'pw-spe' ) );
		$title  = $prefix . '&nbsp;' . $title;

	}

	return $title;

}
add_filter( 'the_title', 'pw_spe_filter_title', 100, 2 );

if ( ! wp_next_scheduled( 'pw_spe_expire_entries' ) && ! wp_installing() )
	wp_schedule_event( strtotime('today midnight'), 'daily', 'pw_spe_expire_entries');

add_action( 'pw_spe_expire_entries', 'scheduled_expire_entries' );
function scheduled_expire_entries() {
	$query_args = array(
		'post_type'       => 'any',
		'orderby'         => 'meta_value',
		'meta_key'        => 'pw_spe_expiration',
		'posts_per_page'  => -1,
		'meta_query'      => array(
			array(
				'key'     => 'pw_spe_expiration',
				'value'   => date( 'Y-n-d', current_time( 'timestamp' ) ),
				'compare' => '<',
				'type'    => 'DATETIME'
			)
		)
	);

	$items = get_posts( $query_args );
	foreach( $items as $p ) {
		$p->post_status = "draft";
		wp_update_post( $p, false );
	}
}

// add_action( 'admin_init', 'scheduled_expire_entries' );