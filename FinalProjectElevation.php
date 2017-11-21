<?php
/*
Plugin Name: Final Project Elevation
Plugin URI: 
Description: Reads the Google Maps Elevation API and provide sunset and sunrise time for the day.
Version: 1.0
Author: Dhaval Bhatt
Author URI: https://www.dbhatt75-01.me
Text Domain: elevation-plugin
License: GPLv3
 */

register_activation_hook(__FILE__, 'final_project_elevation_install');

function final_project_elevation_install(){
    global $wp_version;

    if( version_compare($wp_version, '4.1', '<')){
		wp_die('This plugin requires WordPress Version 4.1 or higher.');
	}
}

register_deactivation_hook(__FILE__, 'final_project_elevation_deactivate');

function final_project_elevation_deactivate(){
    //do something when deactivating
}

/**
 * @param $apikey
 * @param $lat
 * @param $lon
 *
 * Constructs the Google Maps Elevation URL
 * @return string
 */
function get_google_reverse_elevation_url($apikey, $lat, $lon){
    //https://maps.googleapis.com/maps/api/elevation/json?locations=39.7391536,-104.9847034&key=YOUR_API_KEY
    $google_url = 'https://maps.googleapis.com/maps/api/elevation/json?';
    $google_url .= 'locations=' . $lat . ',' . $lon;
    $google_url .= '&key=' . $apikey;

    return $google_url;
}

/**
 * @param $url
 * Gets JSON from the Google Reverse Geocode API
 * @return array|mixed|object|string
 */
function get_google_reverse_elevation_json($url){

	$request = wp_remote_get( $url );

	if( is_wp_error( $request ) ) {
		return 'could not obtain data'; // Bail early
	}else {

		//retreive message body from web service
		$body = wp_remote_retrieve_body( $request );

		//obtain JSON - as object or array
		$data = json_decode( $body, true );

		return $data;
	}
}

/**
 * @param $apikey
 * @param $lat
 * @param $lon
 *
 * Constructs the sunrise-sunset URL
 *
 * @return string
 */
function get_sun_url($lat, $lon){

	//'https://api.sunrise-sunset.org/json?
	$darksky_url = 'https://api.sunrise-sunset.org/json?';
	$darksky_url .= 'lat=' .$lat . '&';
	$darksky_url .= 'lng=' . $lon;
	

	return $darksky_url;
}

/**
 * @param $url
 * Gets JSON from the sunrise-sunset API
 * @return array|mixed|object|string
 */
function get_sun_json($url){

	$request = wp_remote_get( $url );

	if( is_wp_error( $request ) ) {
		return 'could not obtain data'; // Bail early
	}else {

		//retreive message body from web service
		$body = wp_remote_retrieve_body( $request );

		//obtain JSON - as object or array
		$data = json_decode( $body, true );

		return $data;
	}
}

add_action( 'widgets_init', 'final_project_elevation_create_widgets' );

function final_project_elevation_create_widgets() {
	register_widget( 'Final_Project_Elevation' );
}

class Final_Project_Elevation extends WP_Widget {
	// Construction function
	function __construct () {
		parent::__construct( 'Final_Project_Elevation', 'FinalProject Elevation',
			array( 'description' =>
				       'Displays elevation from Google Maps Elevation API' ) );
	}

	/**
	 * @param array $instance
     * Code to show the administrative interface for the Widget
	 */
	function form( $instance ) {
		// Retrieve previous values from instance
		// or set default values if not present

		$darksky_api_lat = ( !empty( $instance['darksky_api_lat'] ) ?
			esc_attr( $instance['darksky_api_lat'] ) : 'ERROR');

		$darksky_api_lon = ( !empty( $instance['darksky_api_lon'] ) ?
			esc_attr( $instance['darksky_api_lon'] ) :
			'ERROR' );

		$google_maps_api_key = ( !empty( $instance['google_maps_api_key'] ) ?
			esc_attr( $instance['google_maps_api_key'] ) :
			'ERROR' );

		$widget_title = ( !empty( $instance['widget_title'] ) ?
			esc_attr( $instance['widget_title'] ) :
			'Elevation' );

        ?>
        <!-- Display fields to specify title and item count -->
        <p>
            <label for="<?php echo
			$this->get_field_id( 'widget_title' ); ?>">
				<?php echo 'Widget Title:'; ?>
                <input type="text"
                       id="<?php echo
				       $this->get_field_id( 'widget_title' );?>"
                       name="<?php
				       echo $this->get_field_name( 'widget_title' ); ?>"
                       value="<?php echo $widget_title; ?>" />
            </label>
        </p>
         <p>
            <label for="<?php echo
			$this->get_field_id( 'darksky_api_lat' ); ?>">
				<?php echo 'Latitude:'; ?>
                <input type="text"
                       id="<?php echo
				       $this->get_field_id( 'darksky_api_lat' );?>"
                       name="<?php
				       echo $this->get_field_name( 'darksky_api_lat' ); ?>"
                       value="<?php echo $darksky_api_lat; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
			$this->get_field_id( 'darksky_api_lon' ); ?>">
				<?php echo 'Longitude:'; ?>
                <input type="text"
                       id="<?php echo
				       $this->get_field_id( 'darksky_api_lon' );?>"
                       name="<?php
				       echo $this->get_field_name( 'darksky_api_lon' ); ?>"
                       value="<?php echo $darksky_api_lon; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
			$this->get_field_id( 'google_maps_api_key' ); ?>">
				<?php echo 'Google Maps API Key:'; ?>
                <input type="text"
                       id="<?php echo
				       $this->get_field_id( 'google_maps_api_key' );?>"
                       name="<?php
				       echo $this->get_field_name( 'google_maps_api_key' ); ?>"
                       value="<?php echo $google_maps_api_key; ?>" />
            </label>
        </p>
        <script>
            jQuery(document).ready(function(){
                if(navigator.geolocation){
                    navigator.geolocation.getCurrentPosition(showLocation);
                }else{
                    console.log('Geolocation is not supported by this browser.');
                    jQuery('#location').html('Geolocation is not supported by this browser.');
                }
            });

            function showLocation(position){
                var latitude = position.coords.latitude;

                console.log("latitude: " + latitude);

                document.getElementById('<?php echo $this->get_field_id( 'darksky_api_lat' ); ?>')
                    .setAttribute('value', latitude);

                var longitude = position.coords.longitude;

                console.log("longitude: " + longitude);

                document.getElementById('<?php echo $this->get_field_id( 'darksky_api_lon' ); ?>')
                    .setAttribute('value', longitude);

            }
        </script>

	<?php }

	/**
	 * @param array $new_instance
	 * @param array $instance
	 *
     * Code to update the admin interface for the widget
     *
	 * @return array
	 */
	function update( $new_instance, $instance ) {

		$instance['widget_title'] =
			sanitize_text_field( $new_instance['widget_title'] );

		$instance['darksky_api_lat'] =
			sanitize_text_field( $new_instance['darksky_api_lat'] );

		$instance['darksky_api_lon'] =
			sanitize_text_field( $new_instance['darksky_api_lon'] );

		$instance['google_maps_api_key'] =
			sanitize_text_field( $new_instance['google_maps_api_key'] );

		return $instance;
	}

	/**
	 * @param array $args
	 * @param array $instance
     *
     * Code for the display of the widget
     *
	 */
	function widget( $args, $instance ) {

        // Extract members of args array as individual variables
        extract( $args );

        $widget_title = ( !empty( $instance['widget_title'] ) ?
            esc_attr( $instance['widget_title'] ) :
            'Elevation' );

		$widget_lat = ( !empty( $instance['darksky_api_lat'] ) ?
			esc_attr( $instance['darksky_api_lat'] ) :
			'0' );

		$widget_lon = ( !empty( $instance['darksky_api_lon'] ) ?
			esc_attr( $instance['darksky_api_lon'] ) :
			'0' );

		$widget_google_maps_api_key = ( !empty( $instance['google_maps_api_key'] ) ?
			esc_attr( $instance['google_maps_api_key'] ) :
			'0' );

		//get URLs
		$url_sun = get_sun_url($widget_lat, $widget_lon);		
		$url_google = get_google_reverse_elevation_url($widget_google_maps_api_key, $widget_lat, $widget_lon);

		//obtain JSON - as object or array
		$data_google = get_google_reverse_elevation_json($url_google);
		$data_sun = get_sun_json($url_sun);

		//$output .= print_r($data_sun);
		//$output .= print_r($data_google);
		//echo '<br>';
				

        // Display widget title
        echo $before_widget . $before_title;
        echo apply_filters( 'widget_title', $widget_title );
        echo $after_title;
		
		echo "From Sea Level: " .(round (($data_google['results'][0]['elevation']/ 3.28),2)) . " ft";
		echo '<br>';
		echo '<br>';
		date_default_timezone_set("America/Phoenix");
		echo "Time In UTC";
		echo '<br>';
		echo "Sunrise: " . $data_sun['results']['sunrise'];
        echo '<br>';
        echo "Sunset: " . $data_sun['results']['sunset'] ;
		echo '<br>';
		//$sunrise_time = $data_sun['results']['sunrise'];
		//echo "Sunrise AZ:";
		//echo $sunrise_time->format('H:i');
		echo '<br>';
        echo $after_widget;
	}
}
?>