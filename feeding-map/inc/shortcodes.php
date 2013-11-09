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
	function loadDomains(map) {
		infoWindow = new google.maps.InfoWindow({maxWidth: 200});";
	
	foreach ($domains as $domain) {
		
		$output .= "\nvar domainCoords" . $domain->ID . " = [\n";
		
		$coordlist = explode("\n",$domain->COORDINATES);
		//$infolat = "";
		//$infolong = "";
		$infolat = $infolong = $infolath = $infolongh = 0;
		$initial = true;
		foreach ($coordlist as $coord) {
			$latlong = explode(",", preg_replace('/\s+/', '', $coord));

			if ($initial) {
				$initial = false;
				$infolat  = $infolath  = $latlong[0];
				$infolong = $infolongh = $latlong[1];
			}
			else {
				if ($latlong[0] < $infolat)  $infolat  = $latlong[0];
				if ($latlong[0] > $infolath) $infolath = $latlong[0];
				if ($latlong[1] < $infolong)  $infolong  = $latlong[1];
				if ($latlong[1] > $infolongh) $infolongh = $latlong[1];
			}
			//if ($infolat == "" || $infolat < $latlong[0]) $infolat = $latlong[0];
			//if ($infolong == "" || $infolong > $latlong[1]) $infolong = $latlong[1];
		
			$output .= "new google.maps.LatLng(" . $latlong[0] . "," . $latlong[1] . "),\n";
		}
		$infolat  = ($infolat  + $infolath ) / 2;
		$infolong = ($infolong + $infolongh) / 2;

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
		google.maps.event.addListener(myDomain{$domain->ID}, 'click', function(event) {
			var myLatLng = new google.maps.LatLng($infolat, $infolong);
			var contentString = '<div><b>" . $domain->NAME . "</b><br>" . $domain->DESCRIPTION . "</div>';
			infoWindow.setPosition(myLatLng); // domainCoords{$domain->ID}[0]
			infoWindow.setContent(contentString);
			infoWindow.open(map);
		});

		\n\n";

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