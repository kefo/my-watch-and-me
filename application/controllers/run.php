<?php

define("BASEURI", "http://3windmills.com/fmr/");

class Run extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('run_model');
                $this->load->library('session');
	}

	public function tcx($id = "")
	{
		$file = DATAPATH . "tcx/" . $id . ".xml";
		//echo $file;
		//exit;
		if ( ! file_exists( $file ))
		{
			// Whoops, we don't have a page for that!
			show_404();
		} else {
			
			$data  = file_get_contents($file);
			header("Content-type: application/xml");
			echo $data;
			
		}
	}
	
	public function add($id = "")
	{
            $uData = $this->session->all_userdata();
            if (!$uData["logged_in"]) {
                Header("Location: " . RELATIVEPATH . "manage/login?status=2");
                exit;
            }
            $file = DATAPATH . "tcx/" . $id . ".xml";
            $added = $this->run_model->add_run($id, $file);
            //echo $file;
            //exit;
            if ($added === 0) {
            	// Whoops, we don't have a page for that!
                show_404();
            } else {
                echo $id . " Added.";
            }
	}
	
	public function geojson($id = "")
	{
		$file = DATAPATH . "tcx/" . $id . ".xml";
		//echo $file;
		//exit;
		if ( ! file_exists( $file ))
		{
			// Whoops, we don't have a page for that!
			show_404();
		} else {
			
			$tcxdom = new DOMDocument();
			$tcxdom->load($file);
			$data  = $this->tcx2gjCoordinates($tcxdom);

			$json = '
				var featureData = {
					"type": "Feature",
					"geometry": {
						"type": "LineString",
						"coordinates": [' . implode(", ", $data["points"]) . ']
					}
				};';
			
			header('Content-Type: application/json; charset=utf-8'); 
			echo $json;
			
		}
	}
	

    public function html($id = "")
    {
        $datets = strtotime($id);
	$date = date("Y-m-d H:i:s", $datets);
		
	$run = $this->run_model->get_run($id);
	//print_r($run);
	//exit;
	$html = '<!DOCTYPE html>
            <html>
            <head>
            <title>Map</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.ie.css" />
<![endif]-->

<script src="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.js"></script>
		<script src="' . RELATIVEPATH . 'run/' . $id . '/feature.geojson"></script>
		</head>
		<body>
		<div id="map" style="width: 1200px; height: 600px"></div>

		<script>

var myStyle = {
    "color": "#ff7800",
    "weight": 8,
    "opacity": 1
};

			var map = L.map("map").setView([' . $run["latitude"] . ', ' . $run["longitude"] . '], 15);
			// L.tileLayer("http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
			L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
				maxZoom: 18
			}).addTo(map);
			L.geoJson(featureData, { style: myStyle }).addTo(map); 
		</script>
		
		</body>
		</html>';
		
	header('Content-Type: text/html; charset=utf-8'); 
	echo $html;
    }
	
	
	private function tcx2gjCoordinates($tcxdom) {
		$xp = new DOMXPath($tcxdom);
    	$xp->registerNamespace('tcx' , 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');
    	
    	$coordinates = array();
    	
    	$lat = "";
    	$long = "";
    	
    	$result = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap[1]/tcx:Track[1]/tcx:Trackpoint[1]/tcx:Position/tcx:LatitudeDegrees");
		if ($result->item(0)) {
	    	$lat = $result->item(0)->nodeValue;
		}
    
    	$result = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap[1]/tcx:Track[1]/tcx:Trackpoint[1]/tcx:Position/tcx:LongitudeDegrees");
    	if ($result->item(0)) {
    		$long = $result->item(0)->nodeValue;
    	}   
    
    	$points = array();
    	if ($lat != "" && $long != "") {
			$coordinates["startLat"] = $lat;
			$coordinates["startLong"] = $long;
			$positions = $xp->query("//tcx:Position");
			foreach($positions as $p) {
				$la = "";
				$lo = "";
				foreach($p->childNodes as $child) {
					if ($child->nodeName == "LatitudeDegrees") {
						$la = $child->nodeValue;
					}
					if ($child->nodeName == "LongitudeDegrees") {
						$lo = $child->nodeValue;
					}
				}
				if ($la != "" && $lo != "") {
					array_push($points, "[" . $lo . ", " . $la . "]");
				}
			}
		}
		$coordinates["points"] = $points;
		return $coordinates;
	}

}
