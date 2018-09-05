<?php
/**
 * Module Name: Photon
 * Module Description: Serve images from our servers
 * Jumpstart Description: Mirrors and serves your images from our free and fast image CDN, improving your site’s performance with no additional load on your servers.
 * Sort Order: 25
 * Recommendation Order: 1
 * First Introduced: 2.0
 * Requires Connection: Yes
 * Auto Activate: No
 * Module Tags: Photos and Videos, Appearance, Recommended
 * Feature: Recommended, Jumpstart, Appearance
 * Additional Search Queries: photon, image, cdn, performance, speed
 */

Jetpack::dns_prefetch( array(
	'//i0.wp.com',
	'//i1.wp.com',
	'//i2.wp.com',
	'//c0.wp.com',
) );

Jetpack_Photon::instance();

class Jetpack_Photon_Static_Assets_CDN {
	public static function go() {
		add_action( 'wp_head', array( __CLASS__, 'cdnize_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'cdnize_assets' ) );
	}

	public static function cdnize_assets() {
		global $wp_scripts, $wp_styles;

//		$known_core_files = self::get_core_checksums();

		if ( ! Jetpack::is_development_version() ) {
			$jetpack_asset_hashes = self::get_jetpack_checksums();
			$jetpack_directory_url = plugins_url( '/', JETPACK__PLUGIN_FILE );

			foreach ( $wp_scripts->registered as $handle => $thing ) {
				if ( wp_startswith( $thing->src, $jetpack_directory_url ) ) {
					$local_path = substr( $thing->src, strlen( $jetpack_directory_url ) );
					if ( isset( $jetpack_asset_hashes[ $local_path ] ) ) {
						$wp_scripts->registered[ $handle ]->src = sprintf('https://c0.wp.com/p/jetpack/%1$s/%2$s', JETPACK__VERSION, $local_path );
						wp_script_add_data( $handle, 'integrity', 'sha256-' . base64_encode( $jetpack_asset_hashes[ $local_path ] ) );
					}
				}
			}
		}
	}

	public static function get_core_checksums() {
		global $wp_version;
		require_once( ABSPATH . 'wp-admin/includes/update.php' );
		return get_core_checksums( $wp_version, get_locale() );
	}

	/**
	 * Returns SHA-256 checksums
	 */
	public static function get_jetpack_checksums() {
		$url = sprintf( 'http://downloads.wordpress.org/plugin-checksums/jetpack/%s.json', '6.4.2' /* JETPACK__VERSION */ );

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$response = wp_remote_get( $url );

		$body = trim( wp_remote_retrieve_body( $response ) );
		$body = json_decode( $body, true );

		$return = array();

		foreach ( $body['files'] as $file => $hashes ) {
			if ( in_array( substr( $file, -3 ), array( 'css', '.js' ) ) ) {
				$return[ $file ] = $hashes['sha256'];
			}
		}

		return $return;
	}
}
Jetpack_Photon_Static_Assets_CDN::go();

