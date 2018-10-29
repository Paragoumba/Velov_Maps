<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);?>
<?php //* Documentation *//
	  // Velo'v map address: http://iutbg-lamp.univ-lyon1.fr/p1803835/public_html/pi
	  // GraphHopper's api to get path
	  // ## Key ddea48ce-8af3-4fd9-88cc-782a40ab1eda ##
	  // Request model    https://graphhopper.com/api/1/route?point={lat,lon}&point={lat,lon}&vehicle=bike&locale=fr&key={key}&instructions=true&points_encoded=false
	  // Example request  https://graphhopper.com/api/1/route?point=51.131,12.414&point=48.224,3.867&vehicle=bike&locale=fr&key=ddea48ce-8af3-4fd9-88cc-782a40ab1eda&instructions=true&points_encoded=false
	  
	  // OpenStreetMap's api to get map & location
	  // Request model    #1  (Map between left_lon, bottom_lat, right_lon, top_lat) https://www.openstreetmap.org/api/0.6/map?bbox={left_lon},{bottom_lat},{right_lon},{top_lat}
	  // Request model    #2  (Address lat and lon) https://nominatim.openstreetmap.org/search?format=json&street={num street_name}&city={city}&country={country}&postalcode={postal_code}
	  // Example request (#1) (Map between 5.2044, 46.1972, 5.2425, 46.2123 - Bourg-en-Bresse) https://www.openstreetmap.org/api/0.6/map?bbox=5.2044,46.1972,5.2425,46.2123
	  // Example request (#2) (Rue Peter Fink's lat and lon) https://nominatim.openstreetmap.org/search?format=json&street=Rue%20Peter%20Fink&city=Bourg-en-Bresse&country=france&postalcode=01000
	  
	  // JCDecaux Developer's api to get bike stations of the Vélo'v Grand Lyon station
	  // ## Key 80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9 ##
	  // Request model    #1  (contracts list) https://api.jcdecaux.com/vls/v1/contracts
	  // Request model    #2  (contract's stations list) https://api.jcdecaux.com/vls/v1/stations?contract={contract_name}&apiKey={key}
	  // Request model    #3  (contract's station's infos) https://api.jcdecaux.com/vls/v1/stations/{station_number}?contract={contract_name}?apiKey={key}
	  // Example request (#2) (Lyon's stations) https://api.jcdecaux.com/vls/v1/stations?contract=Lyon&apiKey=80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9
	  // Example request (#3) (Lyon's #10005 - BOULEVARD DU 11 NOVEMBRE's infos) https://api.jcdecaux.com/vls/v1/stations/10005?contract=Lyon&apiKey=80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9
	  ?>
<?php $keys = array("jcd" => "80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9", "gh" => "ddea48ce-8af3-4fd9-88cc-782a40ab1eda");
function get_distance($lat1, $lon1, $lat2, $lon2){
	 	
		$earth_radius = 6371e3; // metres
		$phi1 = deg2rad($lat1);
		$phi2 = deg2rad($lat2);
		$delta_phi = deg2rad($lat2 - $lat1);
		$delta_lambda = deg2rad($lon2 - $lon1);

		$a = sin($delta_phi / 2) * sin($delta_phi / 2) + cos($phi1) * cos($phi2) * sin($delta_lambda / 2) * sin($delta_lambda / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		$distance = $earth_radius * $c;
		
		return $distance;
	
}

function get_closest_station_with_bikes($loc, $contract){
	
	global $keys;
	
	// var_dump("https://api.jcdecaux.com/vls/v1/stations?contract=$contract&apiKey=" . $keys["jcd"]);
	
	$stations_distance = array();
	$stations = json_decode(file_get_contents(str_replace(" ", "%20", "https://api.jcdecaux.com/vls/v1/stations?contract=$contract&apiKey=" . $keys["jcd"])), true);
	
	$min = 100000;
	$closest = null;
	
	foreach ($stations as $key => $value){
		
		$distance = get_distance($loc["lat"], $loc["lon"], $value["position"]["lat"], $value["position"]["lng"]);
		$available_bikes = $value["available_bikes"];
		$opened = $value["status"] === "OPEN" ? true : false;
		
		if ($opened && $distance < $min && $available_bikes > 2){
			
			$min = $distance;
			$closest = $value;
			
		}
	}
	
	return $closest;
	
}

function get_closest_station_without_bikes($loc, $contract){
	
	global $keys;
	
	$stations_distance = array();
	$stations = json_decode(file_get_contents(str_replace(" ", "%20", "https://api.jcdecaux.com/vls/v1/stations?contract=$contract&apiKey=" . $keys["jcd"])), true);
	
	$min = 100000;
	$closest = null;
	
	foreach ($stations as $key => $value){
		
		$distance = get_distance($loc["lat"], $loc["lon"], $value["position"]["lat"], $value["position"]["lng"]);
		$available_bike_stands = $value["available_bike_stands"];
		$opened = $value["status"] === "OPEN" ? true : false;
		
		if ($opened && $distance < $min && $available_bike_stands > 2){
			
			$min = $distance;
			$closest = $value;
			
		}
	}
	
	return $closest;
	
}

function get_loc_from_address($street, $city, $state, $country, $zipcode){
	
	//var_dump(str_replace(" ", "+", "https://nominatim.openstreetmap.org/search?format=json&street=$street&city=$city&state=$state&country=$country&postalcode=$zipcode"));
	
	$locations = json_decode(file_get_contents(str_replace(" ", "+", "https://nominatim.openstreetmap.org/search?format=json&street=$street&city=$city&state=$state&country=$country&postalcode=$zipcode"), false, stream_context_create(
    array(
        "http" => array(
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
        )
    )
)), true);
	
	return array("lat" => $locations["0"]["lat"], "lon" => $locations["0"]["lon"]);
	
}

function get_path($lat1, $lon1, $lat2, $lon2){
	
	global $keys;

	//var_dump("https://graphhopper.com/api/1/route?point=$lat1,$lon1&point=$lat2,$lon2&vehicle=bike&locale=fr&key=" . $keys["gh"] . "&instructions=true&points_encoded=false");
	
	return json_decode(file_get_contents("https://graphhopper.com/api/1/route?point=$lat1,$lon1&point=$lat2,$lon2&vehicle=bike&locale=fr&key=" . $keys["gh"] . "&instructions=true&points_encoded=false"), true)["paths"][0];
	
}?>
<!DOCTYPE html>

<html>
	<head>
		<title>Velo'v Maps</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
		<link rel="icon" href="bicycle.png">
		<style>
			form div div div.form-group{
				
				margin-bottom: 0.45rem;
				
			}
			form div.card-body{
				
				padding: 0.5rem 1rem;
				
			}
			.ml-6{
				
				margin-left: 5rem;
				
			}
			.form-control {
			
                color: #30363c;
			
            }
		</style>
	</head>
	<body>
		<div class="container-fluid">
			<div class="row">
				<h1 class="card card-header bg-light col-lg-3 text-center" style="border-radius: 0; border-left: none; border-top: none; display: initial; padding-top: 0.25rem; padding-bottom: 0.25rem"><img src="bicycle.png" width="50" height="50" style="vertical-align: bottom"> Velo'v Maps</h1>
				<h3 class="card card-header bg-light col-lg-9 text-center" style="line-height: 38px; border-radius: 0; border-left: none; border-top: none"><?php echo isset($_POST["search"]) ? "There is your path" : "Enter your starting and arrival addresses"?></h3>
				<form method="post" class="px-0 col-lg-3">
					<div class="card bg-light" style="border-radius: 0; border-top: none;">
						<h5 class="card-header text-center">Starting address</h5>
						<div class="card-body">
							<div class="form-group">
								<label for="street">Street</label>
								<input type="text" class="form-control" name="street" id="street" placeholder="1 Place de la Comédie" <?php if(isset($_POST["street"])) echo "value='" . $_POST["street"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="city">City</label>
								<input type="text" class="form-control" name="city" placeholder="Lyon" <?php if(isset($_POST["city"])) echo "value='" . $_POST["city"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="state">State</label>
								<input type="text" class="form-control" name="state" placeholder="Auvergne-Rhône-Alpes" <?php if(isset($_POST["state"])) echo "value='" . $_POST["state"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="country">Country</label>
								<input type="text" class="form-control" name="country" placeholder="France" <?php if(isset($_POST["country"])) echo "value='" . $_POST["country"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="zipcode">Zipcode</label>
								<input type="number" class="form-control" name="zipcode" placeholder="69001" <?php if(isset($_POST["zipcode"])) echo "value='" . $_POST["zipcode"] . "'"?>>
							</div>
						</div>
					</div>
					<div class="card bg-light" style="border-radius: 0; border-top: none; border-bottom: none">
						<h5 class="card-header text-center">Arrival address</h5>
						<div class="card-body">
							<div class="form-group">
								<label for="street2">Street</label>
								<input type="text" class="form-control" id="street2" name="street2" placeholder="228 Avenue du Plateau" <?php if(isset($_POST["street2"])) echo "value='" . $_POST["street2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="city2">City</label>
								<input type="text" class="form-control" id="city2" name="city2" placeholder="Lyon" <?php if(isset($_POST["city2"])) echo "value='" . $_POST["city2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="state2">State</label>
								<input type="text" class="form-control" id="state2" name="state2" placeholder="Auvergne-Rhône-Alpes" <?php if(isset($_POST["state2"])) echo "value='" . $_POST["state2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="country2">Country</label>
								<input type="text" class="form-control" id="country2" name="country2" placeholder="France" <?php if(isset($_POST["country2"])) echo "value='" . $_POST["country2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="zipcode2">Zipcode</label>
								<input type="number" class="form-control" id="zipcode2" name="zipcode2" placeholder="69009" <?php if(isset($_POST["zipcode2"])) echo "value='" . $_POST["zipcode2"] . "'"?>>
							</div>
							<input type="hidden" name="search" value="1">
							<input type="submit" class="btn btn-danger ml-6 w-25">
							<input type="reset" class="btn btn-danger ml-5 w-25">
						</div>
					</div>
				</form>
				<div class="col-lg-9 px-0" id="Map" style="height:942px"></div>
				<?php //97 Rue Molière, 69003 Lyon
				
                if (isset($_POST["search"])){
			
                    $starting_position = get_closest_station_with_bikes(get_loc_from_address($_POST["street"], $_POST["city"], $_POST["state"], $_POST["country"], $_POST["zipcode"]), "Lyon")["position"];
					$arrival_position = get_closest_station_without_bikes(get_loc_from_address($_POST["street2"], $_POST["city2"], $_POST["state2"], $_POST["country2"], $_POST["zipcode2"]), "Lyon")["position"];
					
                    $path = get_path($starting_position["lat"], $starting_position["lng"], $arrival_position["lat"], $arrival_position["lng"]);
			
                    //echo "start: " . $starting_position["lat"] . " : " . $starting_position["lon"] . "<br/>";
					//echo "arrival: " . $arrival_position["lat"] . " : " . $arrival_position["lon"];
			
					//var_dump(get_closest_station_with_bikes($starting_position["lat"], $starting_position["lon"], $_POST["city"])["name"]);
					//var_dump(get_closest_station_without_bikes($arrival_position["lat"], $arrival_position["lon"], $_POST["city"])["name"]);
			
					//echo "<iframe width='425' height='350' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='https://www.openstreetmap.org/export/embed.html?bbox=" . ($starting_position["lon"] < $arrival_position["lon"] ? $starting_position["lon"] : $arrival_position["lon"]) . "," . ($starting_position["lat"] > $arrival_position["lat"] ? $starting_position["lat"] : $arrival_position["lat"]) . "," . ($starting_position["lon"] > $arrival_position["lon"] ? $starting_position["lon"] : $arrival_position["lon"]) . "," . ($starting_position["lat"] < $arrival_position["lat"] ? $starting_position["lat"] : $arrival_position["lat"]) . "&amp;layer=mapnik' style='border: 1px solid black' onLoad='addPath()'></iframe>";
				
                    $distance = number_format($path["distance"] / 1000, 2);
				
                    echo "<ol class='card px-0 bg-light col-lg-12 mb-0' onClick='toggleHide()' transition='display 1s' style='border-radius: 0'>";
                    echo "\n                  <h2 class='card-header text-center'>Itinéraire de " . $distance . "km</h2>";
                    echo "\n                  <div id='divliin' class='card-body offset-lg-3 col-lg-3'>";
				
                    //var_dump($starting_position["lat"]);
                    //var_dump($starting_position["lng"]);
                    //var_dump(count($path));
				
                    foreach($path["instructions"] as $key => $value){
						
                        echo "\n                         	<li class='card-text mx-3'>" . $value["text"] . "</li>";
						
                    }
                    
                    echo "\n                     </div>\n               </ol>\n";
				
				}?>
			</div>
		</div>
		<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
		<script>
			map = new OpenLayers.Map("Map");
			
			var fromProjection = new OpenLayers.Projection('EPSG:4326');   // Transform from WGS 1984
			var toProjection   = new OpenLayers.Projection('EPSG:900913'); // to Spherical Mercator Projection
			var zoom           = 14;
			
			var mapnik         = new OpenLayers.Layer.OSM();
			map.addLayer(mapnik);
		
			<?php if (isset($starting_position)){
			
			/*echo "var lineLayer = new OpenLayers.Layer.Vector("Line Layer"); 

			map.addLayer(lineLayer);                    
			map.addControl(new OpenLayers.Control.DrawFeature(lineLayer, OpenLayers.Handler.Path));                                     
			var points = new Array(
			new OpenLayers.Geometry.Point(lon, lat),

			new OpenLayers.Geometry.Point(lon2, lat2)
			);

			var line = new OpenLayers.Geometry.LineString(points);

			var style = { 

			strokeColor: '#0000ff', 

			strokeOpacity: 0.5,

			strokeWidth: 5
			};

			var lineFeature = new OpenLayers.Feature.Vector(line, null, style);
			lineLayer.addFeatures([lineFeature]);";*/
			
			var_dump($starting_position);
			var_dump($arrival_position);
			
			if ($starting_position != NULL){ echo
		
			"var lat            = " . $starting_position["lat"] . ";
			var lon            = " . $starting_position["lng"] . ";

			var position       = new OpenLayers.LonLat(lon, lat).transform( fromProjection, toProjection);";
			
			if ($arrival_position != NULL) echo
			
			"var lat2            = " . $arrival_position["lat"] . ";
			var lon2            = " . $arrival_position["lng"] . ";

			var position2       = new OpenLayers.LonLat(lon2, lat2).transform( fromProjection, toProjection);";
			
			}
			
			echo "var osm = new OpenLayers.Layer.OSM();
			map.addLayer(osm);
			
			var markers = new OpenLayers.Layer.Markers('Markers');
			map.addLayer(markers);";
			if ($starting_position != NULL){ "echomarkers.addMarker(new OpenLayers.Marker(position));";
			if ($arrival_position != NULL) echo "markers.addMarker(new OpenLayers.Marker(position2));";}
			
			echo "var averagePosition = new OpenLayers.LonLat(" . ($starting_position != NULL ? ($arrival_position != NULL ? ($starting_position["lng"] + $arrival_position["lng"]) / 2 : ($starting_position["lng"]) / 2) : 4.8320114) . ", " . ($starting_position != NULL ? ($arrival_position != NULL ? ($starting_position["lat"] + $arrival_position["lat"]) / 2 : ($starting_position["lat"]) / 2) : 45.7578137) . ").transform(fromProjection, toProjection);
			
			map.setCenter(averagePosition, zoom);
			
			var lineLayer = new OpenLayers.Layer.Vector('Line Layer');
                 
			map.addLayer(lineLayer);
			map.addControl(new OpenLayers.Control.DrawFeature(lineLayer, OpenLayers.Handler.Path));                                     
			var points = new Array(";
				
            //echo "\n				new OpenLayers.Geometry.Point(lon, lat),";
            //echo "\n				new OpenLayers.Geometry.Point(lon2, lat2)";
				
			foreach ($path["points"]["coordinates"] as $key => $value){
					
				if ($key != 0) echo ",";
					
				echo "\n				new OpenLayers.Geometry.Point(" . $value[0] . ", " . $value[1] . ").transform( fromProjection, toProjection)";
					
			}
			
			echo 
			"\n			);

			var line = new OpenLayers.Geometry.LineString(points);

			var style = { 
				strokeColor: '#0033cc',
				strokeOpacity: 0.75,
				strokeWidth: 5
			};

			var lineFeature = new OpenLayers.Feature.Vector(line, null, style);
			lineLayer.addFeatures([lineFeature]);";}
			else echo "var position       = new OpenLayers.LonLat(4.8320114,45.7578137).transform( fromProjection, toProjection);
			map.setCenter(position, zoom);
"?>
			function toggleHide(){
				
				var elt = document.getElementById("divliin");
				
				if (elt.style.getPropertyValue("display") === "none"){
				
                    elt.style.setProperty("display", "", null);
				
				} else {
				
                    elt.style.setProperty("display", "none", null);
				
				}
			}
		</script>
	</body>
</html>
