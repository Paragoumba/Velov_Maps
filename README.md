#"Documentation"
Velo'v map address: http://iutbg-lamp.univ-lyon1.fr/p1803835/public_html/pi

##GraphHopper's api to get path
Request model    https://graphhopper.com/api/1/route?point=&#123;lat,lon&#125;&point={lat,lon}&vehicle=bike&locale=fr&key={key}&instructions=true&points_encoded=false

Example request  [https://graphhopper.com/api/1/route?point=51.131,12.414&point=48.224,3.867&vehicle=bike&locale=fr&key=ddea48ce-8af3-4fd9-88cc-782a40ab1eda&instructions=true&points_encoded=false]
	  
##OpenStreetMap's api to get map & location
Request model    #1  (Map between left_lon, bottom_lat, right_lon, top_lat) https://www.openstreetmap.org/api/0.6/map?bbox={left_lon},{bottom_lat},{right_lon},{top_lat}

Request model    #2  (Address lat and lon) https://nominatim.openstreetmap.org/search?format=json&street={num street_name}&city={city}&country={country}&postalcode={postal_code}

Example request (#1) (Map between 5.2044, 46.1972, 5.2425, 46.2123 - Bourg-en-Bresse) https://www.openstreetmap.org/api/0.6/map?bbox=5.2044,46.1972,5.2425,46.2123

Example request (#2) (Rue Peter Fink's lat and lon) https://nominatim.openstreetmap.org/search?format=json&street=Rue%20Peter%20Fink&city=Bourg-en-Bresse&country=france&postalcode=01000
	  
##JCDecaux Developer's api to get bike stations of the Vélo'v Grand Lyon station
Request model    #1  (contracts list) https://api.jcdecaux.com/vls/v1/contracts

Request model    #2  (contract's stations list) https://api.jcdecaux.com/vls/v1/stations?contract={contract_name}&apiKey={key}

Request model    #3  (contract's station's infos) https://api.jcdecaux.com/vls/v1/stations/{station_number}?contract={contract_name}?apiKey={key}

Example request (#2) (Lyon's stations) https://api.jcdecaux.com/vls/v1/stations?contract=Lyon&apiKey=80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9

Example request (#3) (Lyon's #10005 - BOULEVARD DU 11 NOVEMBRE's infos) https://api.jcdecaux.com/vls/v1/stations/10005?contract=Lyon&apiKey=80a43e1fbe2a041dad7ce70dc14680d6d02ef4d9
	  