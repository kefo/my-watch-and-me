<?php

class Run_model extends CI_Model {
	
	/*
CREATE TABLE runs (
	id int(11) NOT NULL AUTO_INCREMENT,
	relativeuri char(40) NOT NULL,
	date DATETIME NOT NULL,
	seconds DOUBLE NOT NULL,
	meters DOUBLE NOT NULL,
	latitude DOUBLE NOT NULL,
	longitude DOUBLE NOT NULL,
	countrycode char(10) NOT NULL,
	statecode char(10) NOT NULL,
	statename varchar(200) NOT NULL,
	postcode char(15) NOT NULL,
	city varchar(200) NOT NULL,
	street varchar(200) NOT NULL,
	neighborhood varchar(200) NOT NULL,
	country varchar(200) NOT NULL,
	PRIMARY KEY (id),
	KEY date (date)
) ENGINE=MYISAM DEFAULT CHARSET="utf8" COLLATE="utf8_general_ci";
	*/

	public function __construct()
	{
		$this->load->database();
		$this->load->helper('shared_funcs');
	}
	
	public function get_run($id = FALSE)
	{
		if ($id === FALSE) return false;
                $relativeURI = "run/" . $id;
		$query = $this->db->get_where('runs', array('relativeuri' => $relativeURI));
		$results = run_augment($query->row_array());
		return $results;
	}
	
	public function add_run($id = "00000000T000000", $file = false)
	{
		$datets = strtotime($id);
		$date = date("Y-m-d H:i:s", $datets);
		$d = array();
		if ($file !== false || !file_exists( $file )) {
			$tcxdom = new DOMDocument();
			$tcxdom->load($file);
			$d  = $this->parseTCX($tcxdom, $file);
		} else {
			return 0;
		}
		if ($d["valid"] == "1") {
			$data = array(
				'date' => $d["date"],
				'relativeuri' => 'run/' . $id,
				'seconds' => $d["seconds"],
				'meters' => $d["meters"],
				'latitude' => $d["lat"],
				'longitude' => $d["long"],
				'countrycode' => (isset($d["location"]["countrycode"]) ? $d["location"]["countrycode"] : ""),
				'statecode' => (isset($d["location"]["statecode"]) ? $d["location"]["statecode"] : ""),
				'statename' => (isset($d["location"]["statename"]) ? $d["location"]["statename"] : ""),
				'postcode' => (isset($d["location"]["postcode"]) ? $d["location"]["postcode"] : ""),
				'city' => (isset($d["location"]["city"]) ? $d["location"]["city"] : ""),
				'street' => (isset($d["location"]["street"]) ? $d["location"]["street"] : ""),
				'neighborhood' => (isset($d["location"]["neighborhood"]) ? $d["location"]["neighborhood"] : ""),
				'country' => (isset($d["location"]["country"]) ? $d["location"]["country"] : "")
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			
			$this->db->from('runs');
			$this->db->where('date', $date);
			if ($this->db->count_all_results() == 0) {
				return $this->db->insert('runs', $data);
			} else {
				return $this->db->update('runs', $data); 
			}
		} else {
			return false;
		}
	}
	
    private function parseTCX($tcxdom, $filename) {
		
        $event = array();
	
	$fname = str_replace(DATAPATH, "", $filename);
	$fname = str_replace("tcx/", "", $fname);
		
	$event["filename"] = $fname;
	$event["eID"] = "";
	$event["id"] = "";
	$event["uri"] = "";
	$event["date"] = "";
	$event["lat"] = "";
	$event["long"] = "";
	$event["meters"] = "";
	$event["seconds"] = "";
		
	$xp = new DOMXPath($tcxdom);
    	$xp->registerNamespace('tcx' , 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');

        $result = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Id");
    	$eID = $result->item(0)->nodeValue;
    	$event["eID"] = $eID;
    
    	$id = strftime("%Y%m%dT%H%M%S", strtotime($eID));
    	$uri = BASEURI.$id;
    	
    	$event["id"] = $id;
    	$event["uri"] = $uri;
    
    	$results = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap/tcx:TotalTimeSeconds");
    	$seconds = 0;
    	foreach ($results as $r) {
            $seconds += $r->nodeValue;
	}
	$event["seconds"] = $seconds;
	
	$results = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap/tcx:DistanceMeters");
	$meters = 0;
	foreach ($results as $r) {
            $meters += $r->nodeValue;
	}
	$event["meters"] = $meters;
	
	$result = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap[1]/tcx:Track[1]/tcx:Trackpoint[1]/tcx:Position/tcx:LatitudeDegrees");
	if ($result->item(0)) {
            $lat = $result->item(0)->nodeValue;
	    $event["lat"] = $lat;
        }
    
    	$result = $xp->query("/tcx:TrainingCenterDatabase/tcx:Activities/tcx:Activity/tcx:Lap[1]/tcx:Track[1]/tcx:Trackpoint[1]/tcx:Position/tcx:LongitudeDegrees");
    	if ($result->item(0)) {
    		$long = $result->item(0)->nodeValue;
    		$event["long"] = $long;
    	}   
    
    	$location = array();
    	if ($event["lat"] != "" && $event["long"] != "") {
            $location = $this->get_lexical_location($event["lat"], $event["long"]);	
	}
	$event["location"] = $location;
	
        //echo $eID."<br />";
        $date = strftime("%Y-%m-%dT%H:%M:%SZ", strtotime($eID)); // also starttime
        if ($event["lat"] != "" && $event["long"] != "") {
            $date = $this->get_datetime_per_timezone($date, $event["lat"], $event["long"]);
        }
        $event["date"] = $date;
        
        $event["valid"] = true;
	if ($seconds < 120) {
            // too short or unable to find a location
            // which strongly suggests something wrong happened
            $event["valid"] = false;
	}
        
        return $event;
    }

    private function get_datetime_per_timezone($date, $lat, $long) {
        
        $glookup = "https://maps.googleapis.com/maps/api/timezone/json?location=" . $lat . "," . $long . "&timestamp=" . strtotime($date) . "&sensor=false";
        $glookup_response = file_get_contents($glookup);
        $glookup_json = json_decode($glookup_response, true);
        //echo "Original date: " . $date . "<br />";
        //echo $glookup_response."<br />";
        $dstOffset = $glookup_json["dstOffset"];
        $baseOffset = $glookup_json["rawOffset"];
        if ($dstOffset != "" && $baseOffset != "") {
            $seconds_diff = intval($baseOffset) + intval($dstOffset);
            $datets = strtotime($date);
            //echo "Original date ts: " . $datets . "<br />";
            $adjusteddts = $datets + $seconds_diff;
            $adjustedDT = date("Y-m-d\TH:i:s", $adjusteddts);
            //echo "Adjusted date: " . $adjustedDT . "<br />";
            //exit;
            return $adjustedDT;
        } else {
            return $date;
        }
    }
    
    private function get_lexical_location($lat, $long) {
		// returns an array of info
		
		$location = array();
		
		$loc = $this->get_geoname_nearestaddress($lat, $long);
		if (count($loc) > 0) {
			foreach($loc as $k=>$l) {
				if ($l != "") {
					$location[$k] = $l;
				}
			}
		}
		
		$loc = $this->get_geoname_neighbourhood($lat, $long);
		if ( isset($loc["neighborhood"]) ) {
			$location["neighborhood"] = $loc["neighborhood"];
			$location["country"] = $loc["country"];
		}
		if ( isset($loc["city"]) ) {
			$location["city"] = $loc["city"];
		}
		
		if ( !isset($location["city"]) && isset($location["postcode"])) {
			$pcode = $location["postcode"];
			$loc = $this->get_geoname_postalcode($pcode);
			if ($loc["city"] != "") {
				$location["city"] = $loc["city"];
			}
		}
		
		if ( !isset($location["city"]) ) {
			$loc = $this->get_geoname_nearbyplace($lat, $long);
			if ($loc["city"] != "") {
				$location["city"] = $loc["city"];
			}
			if ( !isset($location["country"]) && $loc["city"] != "" ) {
				$location["country"] = $loc["country"];
			}
		}
		
		//print_r($location);
		return $location;
	}
	
	
	private function get_geoname_nearbyplace($lat, $long) {

		$location = array();
		
		/* 	
		 * geonames - nearest address
			$geonamedata = file_get_contents("http://api.geonames.org/findNearestAddress?lat=$lat&lng=$long&username=kefo");
		 	example output 
			<geonames>
				<address>
					<street>Phyllis Pl</street>
					<mtfcc>S1400</mtfcc>
					<streetNumber>12</streetNumber>
					<lat>40.82168</lat>
					<lng>-74.56212</lng>
					<distance>0.01</distance>
					<postalcode>07869</postalcode>
					<placename/>
					<adminCode2>027</adminCode2>
					<adminName2>Morris</adminName2>
					<adminCode1>NJ</adminCode1>
					<adminName1>New Jersey</adminName1>
					<countryCode>US</countryCode>
				</address>
			</geonames>
		*/
		
		$geonamedata = file_get_contents("http://api.geonames.org/findNearbyPlaceName?lat=$lat&lng=$long&username=kefo");
		
		$geodom = new DOMDocument();
		$geodom->loadXML($geonamedata);
		$geo = new DOMXPath($geodom);
	
		$test = $geo->query("/geonames/geoname/name");
		if ($test->item(0)) {
	    
			$result = $geo->query("/geonames/geoname/name");
			$city = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/geoname/countryCode");
			$countrycode = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/geoname/countryName");
			$country = $result->item(0)->nodeValue;
			
			$location = array(
				"countrycode"=>$countrycode,
				"city"=>$city,
				"country"=>$country
			);
		}
	
		return $location;
	}

	
	private function get_geoname_nearestaddress($lat, $long) {

		$location = array();
		
		/* 	
		 * geonames - nearest address
			$geonamedata = file_get_contents("http://api.geonames.org/findNearestAddress?lat=$lat&lng=$long&username=kefo");
		 	example output 
			<geonames>
				<address>
					<street>Phyllis Pl</street>
					<mtfcc>S1400</mtfcc>
					<streetNumber>12</streetNumber>
					<lat>40.82168</lat>
					<lng>-74.56212</lng>
					<distance>0.01</distance>
					<postalcode>07869</postalcode>
					<placename/>
					<adminCode2>027</adminCode2>
					<adminName2>Morris</adminName2>
					<adminCode1>NJ</adminCode1>
					<adminName1>New Jersey</adminName1>
					<countryCode>US</countryCode>
				</address>
			</geonames>
		*/
		
		$geonamedata = file_get_contents("http://api.geonames.org/findNearestAddress?lat=$lat&lng=$long&username=kefo");
		
		$geodom = new DOMDocument();
		$geodom->loadXML($geonamedata);
		$geo = new DOMXPath($geodom);
	
		$test = $geo->query("/geonames/address");
		if ($test->item(0)) {
	    
			$result = $geo->query("/geonames/address/countryCode");
			$countrycode = $result->item(0)->nodeValue;
		
			$result = $geo->query("/geonames/address/adminCode1");
			$statecode = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/address/adminName1");
			$statename = $result->item(0)->nodeValue;
	
			$result = $geo->query("/geonames/address/placename");
			$city = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/address/street");
			$street = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/address/postalcode");
			$postcode = $result->item(0)->nodeValue;
			
			$location = array(
				"countrycode"=>$countrycode,
				"statecode"=>$statecode,
				"statename"=>$statename,
				"postcode"=>$postcode,
				"city"=>$city,
				"street"=>$street
			);
		}
	
		return $location;
	}
	
	
	private function get_geoname_neighbourhood($lat, $long) {
	
		$location = array();
		
		/* 	
		 * geonames - neighboUrhood
			http://api.geonames.org/neighbourhood?lat=38.8928711&lng=-77.001013110&maxRows=2&radius=2&username=demo
		 	example output 
			<geonames>
				<neighbourhood>
					<countryCode>US</countryCode>
					<countryName>United States</countryName>
					<adminCode1>DC</adminCode1>
					<adminName1>Washington, D.C.</adminName1>
					<adminCode2/>
					<adminName2>US.DC.</adminName2>
					<city>Washington</city>
					<name>Capitol Hill</name>
				</neighbourhood>
			</geonames>
		*/
		
		$geonamedata = file_get_contents("http://api.geonames.org/neighbourhood?lat=$lat&lng=$long&maxRows=2&radius=2&username=kefo");
		//echo $geonamedata;
		//exit;
		$geodom = new DOMDocument();
		$geodom->loadXML($geonamedata);
		$geo = new DOMXPath($geodom);
		
		$test = $geo->query("/geonames/neighbourhood");
		if ($test->item(0)) {
    
			$result = $geo->query("/geonames/neighbourhood/countryName");
			$country = $result->item(0)->nodeValue;
		
			$result = $geo->query("/geonames/neighbourhood/adminCode1");
			$state = $result->item(0)->nodeValue;

			$result = $geo->query("/geonames/neighbourhood/city");
			$city = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/neighbourhood/name");
			$neighborhood = $result->item(0)->nodeValue;
			
			$location = array(
				"country"=>$country,
				"state"=>$state,
				"city"=>$city,
				"neighborhood"=>$neighborhood
			);
		}
	
		return $location;
	}
	
	
	private function get_geoname_postalcode($postcode) {

		$location = array();
	
		/* 	
		 * geonames - postcode
			http://api.geonames.org/postalCodeSearch?postalcode=07869&country=US&maxRows=10&username=kefo
		 	example output 
			<geonames>
				<totalResultsCount>1</totalResultsCount>
				<code>
					<postalcode>07869</postalcode>
					<name>Randolph</name>
					<countryCode>US</countryCode>
					<lat>40.84556</lat>
					<lng>-74.57252</lng>
					<adminCode1>NJ</adminCode1>
					<adminName1>New Jersey</adminName1>
					<adminCode2>027</adminCode2>
					<adminName2>Morris</adminName2>
					<adminCode3/>
					<adminName3/>
				</code>
			</geonames>
		*/
	
		$geonamedata = file_get_contents("http://api.geonames.org/postalCodeSearch?postalcode=$postcode&country=US&maxRows=10&username=kefo");
		#echo $geonamedata;
		$geodom = new DOMDocument();
		$geodom->loadXML($geonamedata);
		$geo = new DOMXPath($geodom);
	
		$test = $geo->query("/geonames/code");
		if ($test->item(0)) {
		    
			$result = $geo->query("/geonames/code/countryCode");
			$countrycode = $result->item(0)->nodeValue;
		
			$result = $geo->query("/geonames/code/adminCode1");
			$statecode = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/code/adminName1");
			$statename = $result->item(0)->nodeValue;
	
			$result = $geo->query("/geonames/code/name");
			$city = $result->item(0)->nodeValue;
			
			$result = $geo->query("/geonames/code/postalcode");
			$postcode = $result->item(0)->nodeValue;
			
			$location = array(
				"countrycode"=>$countrycode,
				"statecode"=>$statecode,
				"statename"=>$statename,
				"postcode"=>$postcode,
				"city"=>$city
			);
		}
		return $location;
	}	

}

?>
