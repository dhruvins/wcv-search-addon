jQuery( document ).ready(function(){
	//if ( !localStorage.getItem('wcvsa_cords') ) {
		navigator.geolocation.getCurrentPosition(function(location) {
			console.log(location.coords.latitude);
			console.log(location.coords.longitude);
			console.log(location.coords.accuracy);

			let wcvsa_param = woocommerce_params;

			const wcvsa_ajax = wcvsa_param.ajax_url;

			let ajaxData = {
				'action' : 'wcvsa_save_location',
				'cordinates' : JSON.stringify([location.coords.latitude, location.coords.longitude])
			}

			jQuery.post( wcvsa_ajax, ajaxData, function(response) {  
				localStorage.setItem('wcvsa_cords',JSON.stringify([location.coords.latitude, location.coords.longitude]));
			});
		});
	//}

	if ( jQuery('#distance_range').length > 0 ) {
		let slider = document.getElementById("distance_range");
		let output = document.getElementById("distance_range_display");
		output.innerHTML = slider.value;

		slider.oninput = function() {
		  output.innerHTML = this.value;
		}
	}

	if ( typeof(wcvsa_prod_locations) != 'undefined' ) {

		mapboxgl.accessToken = 'pk.eyJ1Ijoic2hhaGRocnV2aW4iLCJhIjoiY2p0cHowMXYyMDdmbjN5bzdmc3VjcDhreCJ9.Yd7zADCyPTykKZRgC1unlg';

		let cords = JSON.parse(localStorage.getItem('wcvsa_cords'));
		let lat = cords[1];
		let lng = cords[0];

		let prods_array = [];
		for( var prod in wcvsa_prod_locations ){
			let prod_feature = {
				"type": "Feature",
				"geometry": {
					"type": "Point",
					"coordinates": wcvsa_prod_locations[prod]['prod_cords']
				},
				"properties": {
					"title": "Activity",
					"description": wcvsa_prod_locations[prod]['prod_title']
				}
			}
			prods_array.push(prod_feature);
		}

		prods_array.push({
			"type": "Feature",
			"geometry": {
				"type": "Point",
				"coordinates": [lat,lng]
			},
			"properties": {
				"title": "",
				"description": 'Current Location'
			}
		});

		var geojson = {
			"type": "FeatureCollection",
			"features": prods_array
		};

		var map = new mapboxgl.Map({
			container: 'map_search',
			style: 'mapbox://styles/mapbox/streets-v11',
			center: [lat,lng],
			zoom: 12
		});

		// add markers to map
		geojson.features.forEach(function(marker) {

			// create a HTML element for each feature
			var el = document.createElement('div');
			el.className = 'marker';

			// make a marker for each feature and add it to the map
			new mapboxgl.Marker(el)
				.setLngLat(marker.geometry.coordinates)
				.setPopup(new mapboxgl.Popup({offset: 25}) // add popups
					.setHTML('<h3>' + marker.properties.title + '</h3><p>' + marker.properties.description + '</p>'))
				.addTo(map);
		});
	}

	if ( typeof(wcvsa_ind_product) != 'undefined' ) {

		mapboxgl.accessToken = 'pk.eyJ1Ijoic2hhaGRocnV2aW4iLCJhIjoiY2p0cHowMXYyMDdmbjN5bzdmc3VjcDhreCJ9.Yd7zADCyPTykKZRgC1unlg';

		let prods_array = [{
			"type": "Feature",
			"geometry": {
				"type": "Point",
				"coordinates": [wcvsa_ind_product['cords'][0], wcvsa_ind_product['cords'][1]]
			},
			"properties": {
				"title": "Activity",
				"description": wcvsa_ind_product['title']
			}
		}];

		var geojson = {
			"type": "FeatureCollection",
			"features": prods_array
		};

		var map = new mapboxgl.Map({
			container: 'map_product',
			style: 'mapbox://styles/mapbox/streets-v11',
			center: [wcvsa_ind_product['cords'][0], wcvsa_ind_product['cords'][1]],
			zoom: 12
		});

		// add markers to map
		geojson.features.forEach(function(marker) {

			// create a HTML element for each feature
			var el = document.createElement('div');
			el.className = 'marker';

			// make a marker for each feature and add it to the map
			new mapboxgl.Marker(el)
				.setLngLat(marker.geometry.coordinates)
				.setPopup(new mapboxgl.Popup({offset: 25}) // add popups
					.setHTML('<h3>' + marker.properties.title + '</h3><p>' + marker.properties.description + '</p>'))
				.addTo(map);
		});
	}
});