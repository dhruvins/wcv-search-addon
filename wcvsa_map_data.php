<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

$saved_location = get_post_meta( $post_id, '_wcvsa_location', true );
?>

	<script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.js'></script>
	<link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.css' rel='stylesheet' />
	<style>
		#map { position:relative; width:100%; overflow: unset; }
	</style>

	<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.0.0/mapbox-gl-geocoder.min.js'></script>
	<link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.0.0/mapbox-gl-geocoder.css' type='text/css' />

	<div id="wcvsa_location_data" class="panel woocommerce_options_panel">
		<div id='map'></div>
		<input type="hidden" name="_wcvsa_location" id="_wcvsa_location" value="">
	</div>

<script>
mapboxgl.accessToken = 'pk.eyJ1Ijoic2hhaGRocnV2aW4iLCJhIjoiY2p0cHowMXYyMDdmbjN5bzdmc3VjcDhreCJ9.Yd7zADCyPTykKZRgC1unlg';

var center_cords = [-87.6244, 41.8756];
var saved_location = '<?php echo $saved_location; ?>';
if ( saved_location != '' ) {
	center_cords = JSON.parse('<?php echo $saved_location; ?>');
}

var map = new mapboxgl.Map({
	container: 'map',
	style: 'mapbox://styles/mapbox/streets-v11',
	center: center_cords,
	zoom: 13
});

var customData = {
	"features": [
		{
			"type": "Feature",
			"properties": {
				"title": "Columbus Park",
				"description": "A large park in Chicago's Austin neighborhood"
			},
			"geometry": {
				"coordinates": [
					-87.769775,
					41.873683
				],
				"type": "Point"
			}
		}
	],
	"type": "FeatureCollection"
};

map.on("load", function() {
	map.addSource("national-park", {
		"type": "geojson",
		"data": {
			"type": "FeatureCollection",
			"features": [{
				"type": "Feature",
				"geometry": {
					"type": "Point",
					"coordinates": center_cords
				}
			}]
		}
	});

	map.addLayer({
		"id": "park-volcanoes",
		"type": "circle",
		"source": "national-park",
		"paint": {
			"circle-radius": 6,
			"circle-color": "#B42222"
		},
		"filter": ["==", "$type", "Point"],
	});
});

var mapBoxGeoCoder = new MapboxGeocoder({
	accessToken: mapboxgl.accessToken,
	zoom: 14,
	placeholder: "Enter a place or location",
	mapboxgl: mapboxgl
});

mapBoxGeoCoder.on('result',function(data){
	console.log(data);

	var coordinate = data.result.geometry.coordinates;

	$('#_wcvsa_location').val(JSON.stringify(coordinate));
})

map.addControl(mapBoxGeoCoder);
</script>
