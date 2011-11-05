<?php
header('content-type: text/html; charset=utf-8');
error_reporting(0);
require_once "Rating.php";
require_once "ResultProcessor.php";
require_once "Exceptions.php";

class Freetime
{
    private $response = array
    (
        "message" => "Request could not be processed", 
        "status_code" => false
    );    
    
    const radius = 10; //km

    private $response_was_sent = false;
        
    public function shutdown_function() 
    {
        $error = error_get_last();
        if ($error)
        {
            $this->send_response("Internal Error: " . $error['message'], false);
        }
    }

    private function get_amenities($totaltime)
    {
        //sanitize amenities
        if (!isset($_GET['action']) || empty($_GET['action']))
            throw new NoAmenitiesException("No Amenities provided");

        $categories = explode(",", strtolower(trim($_GET['action'])));
        $amenities = ResultProcessor::get_amenities_from_categories($categories, $totaltime);
        
        if (!count($amenities))
            throw new NoAmenitiesException("No Amenities provided");
        
        return $amenities;
    }
    
    private function get_user_id()
    {
        if (isset($_GET['userid']) && strlen(trim($_GET['userid'])) == 40)
            return trim($_GET['userid']);
        
        return false;
    }
    
    private function get_total_time()
    {
        if (isset($_GET['totaltime']) && intval($_GET['totaltime']) > 0)
            return intval($_GET['totaltime']);
        
        return false;
    }
    
    private function get_geo_position()
    {
        if (!isset($_GET['lat']) || !isset($_GET['long']))
            throw new InvalidGeoPosition("Lat AND Long have to be set");
        
        $lat = floatval($_GET['lat']);
        if ($lat < -90 || $lat > 90)
            throw new InvalidGeoPosition("Invalid Latitude. Only values between -90 and 90 are applicable");
            
        $long = floatval($_GET['long']);
        if ($long < -180 || $long > 1800)
            throw new InvalidGeoPosition("Invalid Longitude. Only values between -180 and 180 are applicable");
        
        return array("lat" => $lat, "long" => $long);
    }
    
    private function calculate_bounding_box($lat, $lon)
    {
        $earth_radius = 6371;
        $maxLat = $lat + rad2deg(self::radius / $earth_radius);
        $minLat = $lat - rad2deg(self::radius / $earth_radius);
        $maxLon = $lon + rad2deg(self::radius / $earth_radius / cos(deg2rad($lat)));
        $minLon = $lon - rad2deg(self::radius / $earth_radius / cos(deg2rad($lat)));

        return array
        (
            "center" => array("lat" => $lat, "long" => $lon),
            "nw" => array("lat" => $maxLat, "long" => $minLon), 
            "ne" => array("lat" => $maxLat, "long" => $maxLon), 
            "sw" => array("lat" => $minLat, "long" => $minLon),
            "se" => array("lat" => $minLat, "long" => $maxLon)
        );
    }
    
    private function query_osm(array $bbox, array $amenities)
    {
        //long left,lat top,long right,lat bottom
        $bbox_string = implode(",", array($bbox['sw']['long'], $bbox['sw']['lat'], $bbox['ne']['long'], $bbox['ne']['lat']));
        $amenity_string = implode("|", $amenities);
        $url = sprintf("http://www.overpass-api.de/api/xapi?node[bbox=%s][amenity=%s]", $bbox_string, $amenity_string);
        
        //$this->send_response($url);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        $content = curl_exec($ch);
        
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)
             throw new OSMResponseError("The OSM Response request was invalid");  
        
        curl_close($ch);
            
        return $content;
    }
    
    private function send_response($message = null, $status_code = null)
    {
        if ($this->response_was_sent) 
            return;
        
        if (!is_null($status_code))
            $this->response['status_code'] = $status_code;
        
        if (!is_null($message))
            $this->response['message'] = $message;

        $this->response_was_sent = true;
        
        die(json_encode($this->response));
    }   
    
    public function __construct()
    {
        register_shutdown_function(array($this, 'shutdown_function'));
        
        try 
        {
            $userid = $this->get_user_id();
            $totaltime = $this->get_total_time();
            $lat_long = $this->get_geo_position();
            $bounding_box = $this->calculate_bounding_box($lat_long['lat'], $lat_long['long']);
            $amenities = $this->get_amenities($totaltime);
            $result = $this->query_osm($bounding_box, $amenities);
            $processed = ResultProcessor::process_result($userid, $result, $lat_long);
            $this->send_response($processed, true);
        }
        catch (Exception $ex)
        {
            $this->send_response($ex->getMessage(), false);
        }
    }
}

//------------------------------------------------------------------------------