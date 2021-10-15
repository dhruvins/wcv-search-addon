<?php
/**
 * Plugin Name: Search Activity Widget for WC Vendors
 * Plugin URI: https://imaginate-solutions.com/
 * Description: This plugin allows you to search activities based on users current location
 * Version: 1.0.0
 * Author: Dhruvin Shah
 * Author URI: https://imaginate-solutions.com/
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.0
 */

/**
 * Exit if accessed directly
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Base class
 */
if ( !class_exists( 'WCV_Search_Activity_Widget' ) ) {

	/**
	 * Main Plugin Class
	 */
	class WCV_Search_Activity_Widget {
		
		function __construct() {

			add_action( 'woocommerce_product_data_tabs', array( &$this, 'wcvsa_add_tab_panel' ) );
			add_action( 'woocommerce_product_data_panels', array( &$this, 'wcvsa_tab_data' ) );
			add_action( 'woocommerce_process_product_meta', array( &$this, 'wcvsa_product_meta_fields_save' ) );

			add_action( 'pre_get_posts', array( &$this, 'wcvsa_display_products' ), 20 );
			add_action( 'woocommerce_archive_description', array( &$this, 'wcvsa_modify_after_title' ) );

			add_action( 'wp_enqueue_scripts', array( &$this, 'wcvsa_enqueue_script' ) );

			add_action( 'wp_ajax_wcvsa_save_location', array( &$this, 'wcvsa_save_cords' ) );
			add_action( 'wp_ajax_nopriv_wcvsa_save_location', array( &$this, 'wcvsa_save_cords' ) );

			add_action( 'init', array( &$this, 'print_actions' ) );

			add_action( 'woocommerce_after_add_to_cart_form', array( &$this, 'wcvsa_show_map_product' ) );
		}

		function print_actions() {
			
		}

		function wcvsa_add_tab_panel( $tabs ){
			$location_tab = array(
				'wcv_location' => array(
					'label'    => __( 'Location', 'wcv-search-activity' ),
					'target'   => 'wcvsa_location_data',
					'class'    => array(),
					'priority' => 100
				)
			);
			$tabs = array_merge( $tabs, $location_tab );

			return $tabs;
		}

		function wcvsa_tab_data(){

			global $post;

			$post_id = $post->ID;

			include_once( WP_PLUGIN_DIR . '/wcv-search-addon/wcvsa_map_data.php' );
		}

		function wcvsa_product_meta_fields_save( $post_id ){
			$wcvsa_location = isset( $_POST['_wcvsa_location'] ) ? $_POST['_wcvsa_location'] : '';
			update_post_meta( $post_id, '_wcvsa_location', $wcvsa_location );
		}

		function wcvsa_enqueue_script(){

			wp_enqueue_script( 'wcvsa_location', plugins_url('assets/js/wcsav_scripts.js', __FILE__), '', '', true);

			wp_enqueue_style( 'wcvsa_styles', plugins_url('assets/css/wcvsa_styles.css', __FILE__) );
		}

		function wcvsa_display_products( $query ) {

			if ( !isset( $_GET['s'] ) ) {
				return $query;
			}else {

				global $wpdb;

				$current_cords = json_decode( $_COOKIE['cordinates'] );
				$current_lat = $current_cords[0];
				$current_lng = $current_cords[1];

				$products = "SELECT ID, post_title FROM `" . $wpdb->prefix . "posts` WHERE post_type='product' AND post_status='publish'";
				$get_products = $wpdb->get_results( $products );

				$search_string = explode( ' ', $_GET['s'] );

				$product_ids = array();

				$product_details = array();

				foreach ( $get_products as $product ) {
					foreach ( $search_string as $search_item ) {
						if ( stripos( $product->post_title, $search_item ) !== false ) {
							$saved_location = get_post_meta( $product->ID, '_wcvsa_location', true );
							if ( $saved_location !== '' && $saved_location !== false ) {
								$prod_cords = json_decode($saved_location);
								$prod_lat = $prod_cords[1];
								$prod_lng = $prod_cords[0];

								$distance = $this->vincentyGreatCircleDistance(
									$current_lat,
									$current_lng,
									$prod_lat,
									$prod_lng
								);

								if ( !isset( $_POST['distance_range'] ) ) {
									$distance_range = 5;
								}else {
									$distance_range = $_POST['distance_range'];
								}

								if ( $distance <= $distance_range && !in_array( $product->ID, $product_ids ) ) {
									array_push( $product_ids, $product->ID );

									array_push( $product_details, array( 'prod_title' => $product->post_title, 'prod_cords' => $prod_cords ) );
								}
							}
							continue;
						}
					}
				}

				if ( count( $product_ids ) == 0 ) {
					array_push($product_ids, '');
				}

				wp_localize_script(
					'wcvsa_location',
					'wcvsa_prod_locations',
					$product_details
				);

				$query->set( 'post__in', $product_ids );
				return $query;
			}
		}

		function wcvsa_modify_after_title(){
			if ( is_search() ) {

				wp_enqueue_script(
					'wcvsa_mapbox_gl',
					'https://static-assets.mapbox.com/gl-pricing/dist/mapbox-gl.js',
					'',
					'',
					false
				);

				wp_enqueue_style(
					'wcvsa_mapbox_styles',
					'https://static-assets.mapbox.com/gl-pricing/dist/mapbox-gl.css'
				);

				if ( !isset( $_POST['distance_range'] ) ) {
					$distance_range = '5';
				}else {
					$distance_range = $_POST['distance_range'];
				}
				echo '<p class="range_text">Results showing within ' . $distance_range .  ' km radius. Change the radius below to get more results nearby.</p>';

				echo '<form method="post" action="">
				<div class="radius_slidecontainer">
					<input type="range" min="1" max="100" value="'. $distance_range .'" class="radius_slider" id="distance_range" name="distance_range">
					<p>Current Radius: <span id="distance_range_display"></span>km</p>
				</div>';

				echo '<input type="submit" value="Search again" action=""/>
				</form>';

				echo "<div id='map_search'></div>";
			}
		}

		function vincentyGreatCircleDistance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
			// convert from degrees to radians
			$latFrom = deg2rad($latitudeFrom);
			$lonFrom = deg2rad($longitudeFrom);
			$latTo = deg2rad($latitudeTo);
			$lonTo = deg2rad($longitudeTo);

			$lonDelta = $lonTo - $lonFrom;
			$a = pow(cos($latTo) * sin($lonDelta), 2) +
			pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
			$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

			$angle = atan2(sqrt($a), $b);
			return round( ( ( $angle * $earthRadius ) / 1000 ), 2 );
		}

		function wcvsa_save_cords() {
			if ( isset( $_POST['cordinates'] ) ) {
				/*if(!session_id()) {
					session_start();
				}
				$_SESSION['cordinates'] = json_decode($_POST['cordinates']);*/
				setcookie('cordinates', $_POST['cordinates'], time() + (86400), '/');
			}

			wp_die();
		}

		function wcvsa_show_map_product() {

			wp_enqueue_script(
				'wcvsa_mapbox_gl',
				'https://static-assets.mapbox.com/gl-pricing/dist/mapbox-gl.js',
				'',
				'',
				false
			);

			wp_enqueue_style(
				'wcvsa_mapbox_styles',
				'https://static-assets.mapbox.com/gl-pricing/dist/mapbox-gl.css'
			);

			global $product;

			$saved_location = get_post_meta( $product->get_id(), '_wcvsa_location', true );

			if( $saved_location != '' ){
				echo "<div id='map_product'></div>";

				$saved_location = json_decode($saved_location);
				$product_title = $product->get_title();
				$localized_array = array(
					'cords' => $saved_location,
					'title' => $product_title
				);
				wp_localize_script(
					'wcvsa_location',
					'wcvsa_ind_product',
					$localized_array
				);

				$address = '';

				$reverse_geo = file_get_contents( 'https://api.mapbox.com/geocoding/v5/mapbox.places/'.$saved_location[0].','.$saved_location[1].'.json?access_token=pk.eyJ1Ijoic2hhaGRocnV2aW4iLCJhIjoiY2p0cHowMXYyMDdmbjN5bzdmc3VjcDhreCJ9.Yd7zADCyPTykKZRgC1unlg' );
				if ( $reverse_geo != '' ) {
					$address_obj = json_decode($reverse_geo);

					if( count( $address_obj->features ) > 0 ){
						$address = $address_obj->features[0]->place_name;
					}
				}

				echo "<p class='place-name'>Place: " . $address . "</p>";
			}
		}

	}

}

$WCV_Search_Activity_Widget = new WCV_Search_Activity_Widget();