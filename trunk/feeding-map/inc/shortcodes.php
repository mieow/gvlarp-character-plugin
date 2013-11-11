<?php

function print_map($atts, $content = null) {
	global $wpdb;

	$output = "";

	// Attributes:
	//		map size
	//		show/hide map key
	extract(shortcode_atts(array (
		"showmapkey"    => 1,
		"height"        => 200,
		"width"         => 400
		), $atts)
	);

	$apikey = get_option('feedingmap_google_api');
	$lat    = get_option('feedingmap_centre_lat');
	$long   = get_option('feedingmap_centre_long');
	$zoom   = get_option('feedingmap_zoom');
	$type   = get_option('feedingmap_map_type');

	// Get Domains
	$sql = "SELECT domains.*, owners.FILL_COLOUR, owners.VISIBLE as SHOWOWNER
			FROM 
				" . FEEDINGMAP_TABLE_PREFIX . "DOMAIN domains,
				" . FEEDINGMAP_TABLE_PREFIX . "OWNER owners
			WHERE domains.VISIBLE = 'Y'";
	$domains = $wpdb->get_results($sql);

	// Define the LatLng coordinates for the polygon's path.
    $output .= "<script type='text/javascript'><!--
	var infoWindow;
	function loadDomains(map) {
		infoWindow = new google.maps.InfoWindow({maxWidth: 200});

		var domains = {\n";
	$initial = '';
	foreach ($domains as $domain) {
		// d#:{id:#, fill:'#xxxxxx', coords:[...]}, ...
		$output .= sprintf('%1$sd%2$d:{id:%2$d,', $initial."\t\t\t", $domain->ID);
		if (empty($initial))
			$initial = ",\n";

		// Domain name & description
		$output .= sprintf("name:'%s',desc:'%s',", htmlentities($domain->NAME, ENT_QUOTES), htmlentities($domain->DESCRIPTION, ENT_QUOTES));

		// Polygon fill colour
		$output .= sprintf("fill:'%s',", $domain->SHOWOWNER == 'Y' ? htmlentities($domain->FILL_COLOUR, ENT_QUOTES) : '#FFFFFF');

		// Coordinate list
		$output .= 'coords:[';
		$coordlist = explode("\n", $domain->COORDINATES);
		foreach($coordlist as $key => $coord) {
			$latlong = explode(',', preg_replace('/\s+/','',$coord));
			if (is_numeric($latlong[0]) && is_numeric($latlong[1]))
				$coordlist[$key] = sprintf('[%s,%s]', $latlong[0], $latlong[1]);
			else
				unset($coordlist[$key]);
		}
		$output .= implode(',', $coordlist);
		$output .= "]";

		// d#:{ ... }
		$output .= '}';
	}
	$output .= "
		};
		for (var tag in domains) {
			var domain = domains[tag];

			// Convert the lat/long pairs to objects
			// Calculate the bounding box of the domain
			domain.bounds = null;
			for (var i = 0; i < domain.coords.length; i++) {
				var pair = domain.coords[i];
				domain.coords[i] = new google.maps.LatLng(pair[0], pair[1]);
				if (domain.bounds)
					domain.bounds.extend(domain.coords[i]);
				else
					domain.bounds = new google.maps.LatLngBounds(domain.coords[i]);
			}

			// Create and add the domain polygon to the map
			var poly = new google.maps.Polygon({
				paths: domain.coords,
				strokeColor: '#000000',
				strokeOpacity: 0.8,
				strokeWeight: 2,
				fillColor: domain.fill,
				fillOpacity: 0.35
			});
			poly.domain = domain;
			poly.setMap(map);
			domains[tag].polygon = poly;

			// Add a listener for the click event.
			domain.listener = google.maps.event.addListener(poly, 'click', function(event) {
				infoWindow.setPosition(this.domain.bounds.getCenter());
				infoWindow.setContent('<div><b>'+this.domain.name+'</b><br>'+this.domain.desc+'</div>');
				infoWindow.open(map);
			});
		}
	}
	--></script>\n";

	$output .= "<input type='hidden' name='feedingmap_apikey' id='feedingmap_apikeyID' value=\"$apikey\">\n";
	$output .= "<input type='hidden' name='feedingmap_clat'   id='feedingmap_clatID'   value=\"$lat\">\n";
	$output .= "<input type='hidden' name='feedingmap_clong'  id='feedingmap_clongID'  value=\"$long\">\n";
	$output .= "<input type='hidden' name='feedingmap_zoom'   id='feedingmap_zoomID'  value=\"$zoom\">\n";
	$output .= "<input type='hidden' name='feedingmap_type'   id='feedingmap_typeID'  value=\"$type\">\n";
	$output .= "<input type='button' name='Reload' value='Reload' onclick=\"initialize()\">\n";
	$output .= "<p id=\"feedingmap_status\">Start</p>\n";
	$output .= "<div id=\"feedingmap\" style=\"height:{$height}px; width:{$width}px\">\n";
	$output .= "<div id=\"map-canvas\" style=\"width: 100%; height: 100%\"></div>\n";
	$output .= "</div>\n";

	return $output;
}
add_shortcode('feeding_map', 'print_map');
?>
