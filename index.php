<?php function get_keys(){

    global $keys;

    $keys = array();
    $file_content = explode("\n", file_get_contents("keys"));

    foreach ($file_content as $row){

        $row = explode(" ", $row);
        $keys[$row[0]] = $row[1];

    }
}

function println($str){

    echo $str . "\n";

}

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

	$stations = json_decode(file_get_contents(str_replace(" ", "%20", "https://api.jcdecaux.com/vls/v1/stations?contract=$contract&apiKey=" . $keys["jcd"])), true);
	$min = 100000;
	$closest = null;
	
	foreach ($stations as $key => $value){
		
		//$distance = get_path($loc["position"]["lat"], $loc["position"]["lon"], $value["position"]["lat"], $value["position"]["lng"], "foot", false)["distance"];
		$distance = get_distance($value["position"]["lat"], $value["position"]["lng"], $loc["lat"], $loc["lon"]);
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

	$stations = json_decode(file_get_contents(str_replace(" ", "%20", "https://api.jcdecaux.com/vls/v1/stations?contract=$contract&apiKey=" . $keys["jcd"])), true);
	$min = 100000;
	$closest = null;

	foreach ($stations as $key => $value){

		//$distance = get_path($value["position"]["lat"], $value["position"]["lng"], $loc["position"]["lat"], $loc["position"]["lon"], "foot", false)["distance"];
		$distance = get_distance($value["position"]["lat"], $value["position"]["lng"], $loc["lat"], $loc["lon"]);
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

	$locations = json_decode(file_get_contents(str_replace(" ", "+", "https://nominatim.openstreetmap.org/search?format=json&street=$street&city=$city&state=$state&country=$country&postalcode=$zipcode"), false, stream_context_create(

	        array(
	                "http" => array(
	                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
            )

    )), true);
	
	return array("lat" => $locations["0"]["lat"], "lon" => $locations["0"]["lon"]);
	
}

function get_path($lat1, $lon1, $lat2, $lon2, $vehicle, $detailed){
	
	global $keys;

	return json_decode(file_get_contents("https://graphhopper.com/api/1/route?point=$lat1,$lon1&point=$lat2,$lon2&vehicle=$vehicle&locale=fr&key=" . $keys["gh"] . "&instructions=true&points_encoded=false" . ($detailed === true ? "" : "&instructions=true&calc_points=true")), true)["paths"][0];
	
}?>
<!DOCTYPE html>

<html lang="fr">
	<head>
		<title>Vélo'v Maps</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/style.css">
		<link rel="icon" href="res/velo'v-50x50.png">
	</head>
	<body>
		<div class="container-fluid">
			<div class="row">
				<h1 class="card card-header bg-light col-lg-3 text-center border-left-0 border-top-0 rounded-0 py-1 d-block"><img src="res/logo.png" alt="Vélo'v Logo" class="align-bottom"></h1>
				<h3 class="card card-header bg-light col-lg-9 text-center border-left-0 border-top-0 rounded-0"><?php echo isset($_POST["search"]) ? "Voici votre trajet" : "Entrez vos adresses de départ et d'arrivée"?></h3>
				<form method="post" class="px-0 col-sm-3">
					<div class="card bg-light border-top-0 rounded-0">
						<h5 class="card-header text-center">Adresse de départ</h5>
						<div class="card-body">
							<div class="form-group">
								<label for="street">Rue</label>
								<input type="text" class="form-control" name="street" id="street" placeholder="1 Place de la Comédie" <?php if(isset($_POST["street"])) echo "value='" . $_POST["street"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="city">Ville</label>
								<input type="text" class="form-control" name="city" placeholder="Lyon" <?php if(isset($_POST["city"])) echo "value='" . $_POST["city"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="state">Région</label>
								<input type="text" class="form-control" name="state" placeholder="Auvergne-Rhône-Alpes" <?php if(isset($_POST["state"])) echo "value='" . $_POST["state"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="country">Pays</label>
								<input type="text" class="form-control" name="country" placeholder="France" <?php if(isset($_POST["country"])) echo "value='" . $_POST["country"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="zipcode">Code postal</label>
								<input type="number" class="form-control" name="zipcode" placeholder="69001" <?php if(isset($_POST["zipcode"])) echo "value='" . $_POST["zipcode"] . "'"?>>
							</div>
						</div>
					</div>
					<div class="card bg-light border-top-0 border-bottom-0 rounded-0">
						<h5 class="card-header text-center">Adresse d'arrivée</h5>
						<div class="card-body">
							<div class="form-group">
								<label for="street2">Rue</label>
								<input type="text" class="form-control" id="street2" name="street2" placeholder="228 Avenue du Plateau" <?php if(isset($_POST["street2"])) echo "value='" . $_POST["street2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="city2">Ville</label>
								<input type="text" class="form-control" id="city2" name="city2" placeholder="Lyon" <?php if(isset($_POST["city2"])) echo "value='" . $_POST["city2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="state2">Région</label>
								<input type="text" class="form-control" id="state2" name="state2" placeholder="Auvergne-Rhône-Alpes" <?php if(isset($_POST["state2"])) echo "value='" . $_POST["state2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="country2">Pays</label>
								<input type="text" class="form-control" id="country2" name="country2" placeholder="France" <?php if(isset($_POST["country2"])) echo "value='" . $_POST["country2"] . "'"?>>
							</div>
							<div class="form-group">
								<label for="zipcode2">Code postal</label>
								<input type="number" class="form-control" id="zipcode2" name="zipcode2" placeholder="69009" <?php if(isset($_POST["zipcode2"])) echo "value='" . $_POST["zipcode2"] . "'"?>>
							</div>
							<input type="hidden" name="search" value="1">
                            <div class="form-row">
							    <input type="submit" class="btn btn-danger offset-sm-0 offset-1 col-sm-5 col-4">
							    <input type="reset" class="btn btn-danger offset-2 col-sm-5 col-4">
                            </div>
						</div>
					</div>
				</form>
				<div class="col-sm-9 px-0" id="Map"></div><?php get_keys();
				
                if (isset($_POST["search"])){
			
                    $starting_position = get_loc_from_address($_POST["street"], $_POST["city"], $_POST["state"], $_POST["country"], $_POST["zipcode"]);
					$arrival_position = get_loc_from_address($_POST["street2"], $_POST["city2"], $_POST["state2"], $_POST["country2"], $_POST["zipcode2"]);
					$average_position = array("lat" => ($starting_position["lat"] + $arrival_position["lat"]) / 2, "lon" => ($starting_position["lon"] + $arrival_position["lon"]) / 2);

					$starting_station = get_closest_station_with_bikes($starting_position, "Lyon");
					$arrival_station = get_closest_station_without_bikes($arrival_position, "Lyon");

					$paths = array(
					    get_path($starting_position["lat"], $starting_position["lon"], $starting_station["position"]["lat"], $starting_station["position"]["lng"], "foot", true),
                        get_path($starting_station["position"]["lat"], $starting_station["position"]["lng"], $arrival_station["position"]["lat"], $arrival_station["position"]["lng"], "bike", true),
                        get_path($arrival_station["position"]["lat"], $arrival_station["position"]["lng"], $arrival_position["lat"], $arrival_position["lon"], "foot", true)
                    );

					/*foreach ($paths as $path) {

                        println("<ol class='card px-0 bg-light col-lg-4 mb-0 border-left-0 rounded-0' onClick='toggleHide()'>");
                        println("                  <h2 class='card-header text-center'>Itinéraire de " . number_format($path['distance'] / 1000, 2) . "km</h2>");
                        println("                  <div class='card-body toggle'>");

                        foreach ($path["instructions"] as $key => $value) {

                            println("                         	<li class='card-text mx-3'>" . $value["text"] . "</li>");

                        }

                        println("                     </div>\n               </ol>");

                    }*/

					$path1 = get_path($starting_position["lat"], $starting_position["lon"], $starting_station["position"]["lat"], $starting_station["position"]["lng"], "foot", true);
                    $path2 = get_path($starting_station["position"]["lat"], $starting_station["position"]["lng"], $arrival_station["position"]["lat"], $arrival_station["position"]["lng"], "bike", true);
                    $path3 = get_path($arrival_station["position"]["lat"], $arrival_station["position"]["lng"], $arrival_position["lat"], $arrival_position["lon"], "foot", true);

                    $distance1 = number_format($path1["distance"] / 1000, 2);
                    $distance2 = number_format($path2["distance"] / 1000, 2);
                    $distance3 = number_format($path3["distance"] / 1000, 2);
				
                    println("<ol class='card px-0 bg-light col-lg-4 mb-0 rounded-0' onClick='toggleHide()'>");
                    println("                  <h2 class='card-header text-center'>Itinéraire de " . $distance1 . "km</h2>");
                    println("                  <div class='card-body toggle'>");
				
                    foreach($path1["instructions"] as $key => $value){
						
                        println("                         	<li class='card-text mx-3'>" . $value["text"] . "</li>");
						
                    }
                    
                    println("                     </div>\n               </ol>\n");

                    println("<ol class='card px-0 bg-light col-lg-4 mb-0 border-left-0 rounded-0' onClick='toggleHide()'>");
                    println("                  <h2 class='card-header text-center'>Itinéraire de " . $distance2 . "km</h2>");
                    println("                  <div class='card-body toggle'>");

                    foreach($path2["instructions"] as $key => $value){

                        println("                         	<li class='card-text mx-3'>" . $value["text"] . "</li>");

                    }

                    println("                     </div>\n               </ol>\n");

                    println("<ol class='card px-0 bg-light col-lg-4 mb-0 border-left-0 rounded-0' onClick='toggleHide()'>");
                    println("                  <h2 class='card-header text-center'>Itinéraire de " . $distance3 . "km</h2>");
                    println("                  <div class='card-body toggle'>");

                    foreach($path3["instructions"] as $key => $value){

                        println("                         	<li class='card-text mx-3'>" . $value["text"] . "</li>");

                    }

                    println("                     </div>\n               </ol>\n");
				
				}?>
			</div>
		</div>
		<script src="https://www.openlayers.org/api/OpenLayers.js"></script>
		<script>
			map = new OpenLayers.Map("Map");
			
			var fromProjection = new OpenLayers.Projection('EPSG:4326'); // Transform from WGS 1984
			var toProjection = new OpenLayers.Projection('EPSG:900913'); // to Spherical Mercator Projection
			var zoom = 13;
			
			var mapnik = new OpenLayers.Layer.OSM();
			map.addLayer(mapnik);
		
			<?php if (isset($_POST["search"])){

			    echo "var startingPos = new OpenLayers.LonLat(" . $starting_position["lon"] . "," . $starting_position["lat"] . ").transform( fromProjection, toProjection);
			var arrivalPos = new OpenLayers.LonLat(" . $arrival_position["lon"] . "," . $arrival_position["lat"] . ").transform( fromProjection, toProjection);";

			    if ($starting_position != NULL){

			        echo "\n        var startingStationPos = new OpenLayers.LonLat(" . $starting_station["position"]["lng"] . "," . $starting_station["position"]["lat"] . ").transform( fromProjection, toProjection);";

			        if ($arrival_position != NULL)

			            echo "\n        var arrivalStationPos = new OpenLayers.LonLat(" . $arrival_station["position"]["lng"] . "," . $arrival_station["position"]["lat"] . ").transform(fromProjection, toProjection);";

			    }

			    echo "var osm = new OpenLayers.Layer.OSM();
			    map.addLayer(osm);
			
			    var lineLayer = new OpenLayers.Layer.Vector('Line Layer');
                 
			    map.addLayer(lineLayer);
			
			    var markers = new OpenLayers.Layer.Markers('Markers');
			    map.addLayer(markers);
                
                var size = new OpenLayers.Size(20,31);
                var sizef = new OpenLayers.Size(22, 31);
                var offset = new OpenLayers.Pixel(-(size.w / 2), -size.h);
                var offsetf = new OpenLayers.Pixel(-(sizef.w / 2), -sizef.h);
                var startingIcon = new OpenLayers.Icon('res/marker-green-20x31.png', size, offset);
                var arrivalIcon = new OpenLayers.Icon('res/marker-blue-22x31f.png', sizef, offsetf);
                    
                markers.addMarker(new OpenLayers.Marker(startingPos, startingIcon));
                markers.addMarker(new OpenLayers.Marker(arrivalPos, arrivalIcon));";

			    if ($starting_position != NULL){

                    echo "var stationIcon = new OpenLayers.Icon('res/marker-red-20x31.png', size, offset);
                    
                    markers.addMarker(new OpenLayers.Marker(startingStationPos, stationIcon));";

			        if ($arrival_position != NULL) echo "markers.addMarker(new OpenLayers.Marker(arrivalStationPos, stationIcon.clone()));";

			    }

			    echo "var averagePosition = new OpenLayers.LonLat(" . $average_position["lon"] . "," . $average_position["lat"] . ").transform(fromProjection, toProjection);
			
			    map.setCenter(averagePosition, zoom);
			    map.addControl(new OpenLayers.Control.DrawFeature(lineLayer, OpenLayers.Handler.Path));    
			                                     
			    var points1 = new Array(";

			    foreach ($paths[0]["points"]["coordinates"] as $key => $value){

			        if ($key != 0) echo ",";

			        echo "\n				new OpenLayers.Geometry.Point(" . $value[0] . ", " . $value[1] . ").transform(fromProjection, toProjection)";

			    }

			    echo "\n			);
			    
			    var points2 = new Array(";

			    foreach ($paths[1]["points"]["coordinates"] as $key => $value){

			        if ($key != 0) echo ",";

			        echo "\n				new OpenLayers.Geometry.Point(" . $value[0] . ", " . $value[1] . ").transform(fromProjection, toProjection)";

			    }

			    echo "\n			);
			    
			    var points3 = new Array(";

			    foreach ($paths[2]["points"]["coordinates"] as $key => $value){

                    if ($key != 0) echo ",";

                    echo "\n				new OpenLayers.Geometry.Point(" . $value[0] . ", " . $value[1] . ").transform(fromProjection, toProjection)";

                }

			    echo "\n			);

			    var line1 = new OpenLayers.Geometry.LineString(points1);
			    var line2 = new OpenLayers.Geometry.LineString(points2);
			    var line3 = new OpenLayers.Geometry.LineString(points3);

			    var stationStyle = { 
			    	strokeColor: '#c04141',
				    strokeOpacity: 0.75,
				    strokeWidth: 5
			    };
			    
			    var otherStyle = {
			    	strokeColor: '#0033cc',
				    strokeOpacity: 0.75,
				    strokeWidth: 5
			    };

			    var path1 = new OpenLayers.Feature.Vector(line1, null, otherStyle);
			    var path2 = new OpenLayers.Feature.Vector(line2, null, stationStyle);
			    var path3 = new OpenLayers.Feature.Vector(line3, null, otherStyle);
			    lineLayer.addFeatures([path1, path2, path3]);";

			} else {

			    echo
"               var position = new OpenLayers.LonLat(4.8320114,45.7578137).transform(fromProjection, toProjection);
		        map.setCenter(position, zoom);";

			}?>
		</script>
    <script src="js/toggleHide.js"></script>
	</body>
</html>
