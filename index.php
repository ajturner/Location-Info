<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Location</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="./bootstrap/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 10px;
        padding-bottom: 40px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
      #map {
	      height: 400px;
	      width: 100%;
      }
      input.small {
	      font-size: 8pt; 
	      height: 13px;
      }
    </style>
    <link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

	<link rel="stylesheet" href="http://serverapi.arcgisonline.com/jsapi/arcgis/3.2/js/dojo/dijit/themes/tundra/tundra.css">
	<link rel="stylesheet" href="http://serverapi.arcgisonline.com/jsapi/arcgis/3.2/js/esri/css/esri.css">

  </head>

  <body>

    <div class="container-fluid" id="container">
      <div class="row-fluid">
        <div class="span3">
          <div class="well sidebar-nav">
            <ul class="nav nav-list">
              <li class="nav-header">Location</li>
              <li><input id="loc_latlng" type="text" class="input-medium click-to-select small" /></li>
              <li>Latitude: <span id="loc_latitude"></span></li>
              <li>Longitude: <span id="loc_longitude"></span></li>
              <li>Geohash: <span id="loc_geohash"></span></li>
            </ul>
            <ul class="nav nav-list">
              <li class="nav-header">Context</li>
              <li>Locality: <span id="geoloqi_locality"></span></li>
              <li>Region: <span id="geoloqi_region"></span></li>
              <li>Country: <span id="geoloqi_country"></span></li>
              <li>Timezone: <span id="geoloqi_timezone"></span></li>
            </ul>
          </div><!--/.well -->
        </div><!--/span-->
        <div class="span9">
	      <div id="map"></div>
        </div><!--/span-->
      </div><!--/row-->

      <hr>

      <footer>
        <p>&copy; 2012 by Geoloqi, Inc.</p>
      </footer>

    </div><!--/.fluid-container-->

    <!-- Placed at the end of the document so the pages load faster -->
    <script src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
    <script src="./bootstrap/js/bootstrap.min.js"></script>
    <script src="http://serverapi.arcgisonline.com/jsapi/arcgis/?v=3.2"></script>
    <script src="./geohash.js"></script>

    <script type="text/javascript">
    var data = {
	    latitude: <?= get('latitude', 'false') ?>,
	    longitude: <?= get('longitude', 'false') ?>,
	    radius: <?= get('radius', 0) ?>,
	    geohash: "<?= get('geohash', '') ?>",
	    zoom: <?= get('zoom', 14) ?>
    };
    if(data.geohash) {
    	var decoded = decodeGeoHash(data.geohash);
    	data.latitude = decoded.latitude[2];
    	data.longitude = decoded.longitude[2];
    } else {
	    data.geohash = encodeGeoHash(data.latitude, data.longitude);
    }
    
    
    var map;

	require([
	    "esri/map"
	  ], function(Pin, LatLng, Moveable){
	  var extent = new esri.geometry.Extent({
	    "ymin": data.latitude,
	    "xmin": data.longitude,
	    "ymax": data.latitude,
	    "xmax": data.longitude,
	    "spatialReference":{"wkid":4326}
	  });
      map = new esri.Map("map", {
        extent: esri.geometry.geographicToWebMercator(extent)
      });
      map.addLayer(new esri.layers.ArcGISTiledMapServiceLayer("http://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer"));
      var outline = new esri.symbol.SimpleLineSymbol("solid", new dojo.Color([227, 124, 55]), 1);
      var symbol = new esri.symbol.SimpleMarkerSymbol("circle", 16, outline, new dojo.Color([246, 142, 72]));
      var centerPoint = esri.geometry.geographicToWebMercator(new esri.geometry.Point(data.longitude, data.latitude));
      map.centerAt(centerPoint);
      
      dojo.connect(map, "onLoad", function() {
        pin = new esri.Graphic(centerPoint, symbol);
        map.graphics.add(pin)

        
        if(data.radius) {
		  var circleSymbol = new esri.symbol.SimpleFillSymbol("solid", circleOutline, new dojo.Color([95,200,240,0.25]));
		  var circleOutline = new esri.symbol.SimpleLineSymbol("dashdot",new dojo.Color([45,200,255]), 2);
		  var circle = new esri.geometry.Polygon(map.spatialReference);
	      var ring = [], // point that make up the circle
	          pts = 60, // number of points on the circle
	          angle = 360/pts; // used to compute points on the circle
	      for(var i=1; i<=pts; i++) {
	        // convert angle to raidans
	        var radians = i * angle * Math.PI / 180;
	        // add point to the circle
	        ring.push([centerPoint.x + data.radius * Math.cos(radians), centerPoint.y + data.radius * Math.sin(radians)]);
	      }
	      ring.push(ring[0]); // start point needs to == end point
	      circle.addRing(ring);
	      var circleGraphic = new esri.Graphic(circle, circleSymbol);
          map.graphics.add(circleGraphic);
          map.setExtent(circle.getExtent());
        } else {
		    map.setLevel(data.zoom);
        }
        
      });
            
	});
          
    $(function(){
    	$(".click-to-select").on('click', function(){
	    	$(this).select();
    	});
    
    	$("#map").height(document.height - $("#container").position().top - 30);
    	
    	$("#loc_latitude").text(data.latitude);
    	$("#loc_longitude").text(data.longitude);
    	$("#loc_latlng").val(data.latitude+","+data.longitude);
    	$("#loc_geohash").text(data.geohash);
    	
    	if(data.latitude) {
		    $.getJSON("https://api.geoloqi.com/1/location/context?latitude="+data.latitude+"&longitude="+data.longitude+"&callback=?", 		
		    function(response){
		    	if(response.locality_name) {
			    	$("#geoloqi_locality").text(response.locality_name);
			    	$("#geoloqi_region").text(response.region_name);
			    	$("#geoloqi_country").text(response.country_name);
		    	}
		    });
		    $.getJSON("http://timezone-api.geoloqi.com/timezone/"+data.latitude+"/"+data.longitude+"?callback=?", function(response){
		    	if(response.timezone) {
			    	$("#geoloqi_timezone").text(response.timezone);
		    	}
		    });
	    	
    	}    
    });
    </script>

  </body>
</html>
<?php

function get($k, $default=false) {
	return array_key_exists($k, $_GET) ? $_GET[$k] : $default;
}
