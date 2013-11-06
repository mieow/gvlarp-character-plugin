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
    $output .= "<script>
	function loadDomains(map) {\n";
	
	foreach ($domains as $domain) {

		$output .= "\nvar domainCoords" . $domain->ID . " = [\n";
		
		$coordlist = explode("\n",$domain->COORDINATES);
		
		foreach ($coordlist as $coord) {
			$latlong = explode(",", preg_replace('/\s+/', '', $coord));
		
			$output .= "new google.maps.LatLng(" . $latlong[0] . "," . $latlong[1] . "),\n";
		}
		
		$startcood = explode(",", preg_replace('/\s+/', '', $coordlist[0]));
		if ($startcood[0] != $latlong[0] && $startcood[1] != $latlong[1])
			$output .= "new google.maps.LatLng(" . $startcood[0] . "," . $startcood[1] . ")\n";
		
		$output .= "];\n\n";
		
		$fill = $domain->SHOWOWNER == 'Y' ? $domain->FILL_COLOUR : '#FFFFFF';
		
		$output .= "// Construct the polygon.
		myDomain{$domain->ID} = new google.maps.Polygon({
			paths: domainCoords{$domain->ID},
			strokeColor: '#000000',
			strokeOpacity: 0.8,
			strokeWeight: 2,
			fillColor: '$fill',
			fillOpacity: 0.35
		});

		myDomain{$domain->ID}.setMap(map);
		
		// Add a listener for the click event.
		google.maps.event.addListener(myDomain{$domain->ID}, 'click', showDomainInfo);

		infoWindow = new google.maps.InfoWindow();\n\n";
		
		

	}
		
	$output .= "}
	</script>\n";
	
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