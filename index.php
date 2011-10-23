<?php
header('content-type: text/html; charset=utf-8');

class Freetime
{
    private $response = array
    (
        "message" => "Request could not be processed", 
        "status_code" => false
    );    
    
    const radius = 10; //km
    
    const max_locations = 30;
    
    private $response_was_sent = false;
    
    private $categories = array
    (
        "food" => array("restaurant", "fast_food"),
        "party" => array("pub", "nightclub")
    );
    
    public function shutdown_function() 
    {
        $error = error_get_last();
        if ($error)
        {
            $this->send_response("Internal Error: " . $error['message'], false);
        }
    }

    private function get_amenities()
    {
        //sanitize amenities
        if (!isset($_GET['action']) || empty($_GET['action']))
            throw new NoAmenitiesException("No Amenities provided");

        $action = explode(",", strtolower(trim($_GET['action'])));
        $amenities = array();

        foreach ($action as $a)
        {
            if (isset($this->categories[$a]))
                $amenities = array_merge($this->categories[$a], $amenities);
        }

        return array_unique($amenities);

        if (!count($amenities))
            throw new NoAmenitiesException("No Amenities provided");
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
        $url = sprintf("http://www.overpass-api.de/api/xapi?node[bbox=%s][amenity=%s][@meta]", $bbox_string, $amenity_string);
        
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
    
    private function process_result($result, array $lat_long)
    {
        if (empty($result))
            throw new OSMResponseError("The OSM Response was empty");
        
        //dont output the errors
        libxml_use_internal_errors(true);
        $resp = simplexml_load_string($result);
        $converting_error = count(libxml_get_errors()) !== 0;
        
        if ($converting_error)
            throw new OSMResponseError("Malformatted XML in Response of OSM");
        
        if (!count($resp->node))
            throw new OSMResponseError("The OSM Response was empty");

        $tag_mapping = array
        (
            'addr:city' => 'city',
            'addr:housenumber' => 'number',
            'addr:postcode' => 'zip',
            'addr:street' => 'street',
            'amenity' => 'amenity',
            'name' => 'name',
            'note' => 'info',
            'smoking' => 'smoking',
            'website' => 'website',
            'wheelchair' => 'wheelchair',
            'addr:country' => 'country',
            'cuisine' => 'cuisine',
            'opening_hours' => 'opening_hours',
            'phone' => 'phone'
        );

        $locations = array();

        foreach ($resp->node as $n)
        {
            $lat = floatval($n->attributes()->lat);
            $long = floatval($n->attributes()->lon);
            $dist = $this->calc_dist($lat, $lat_long['lat'], $long, $lat_long['long']);
            
            $location = array
            (
                "id" => intval($n->attributes()->id),
                "lat" => $lat,
                "long" => $long,
                "dist" => $dist,
                'additional' => array()
            );

            foreach ($tag_mapping as $tag)
                $location[$tag] = null;

            foreach ($n->tag as $t)
            {
                $key = strtolower(trim($t->attributes()->k));
                $val = (string)$t->attributes()->v;

                if (isset($tag_mapping[$key]))
                    $location[$tag_mapping[$key]] = $val;
                else
                    $location['additional'][$key] = $val;
            }

            //prevent overwriting when we sort after key
            $dist_key = "$dist" . rand(0, time());
            $locations[$dist_key] = $location;
        }
        
        ksort($locations);
        
        if (count($locations) > self::max_locations)
            array_splice($locations, self::max_locations);
        
        return array_values($locations);
    }
    
    private function calc_dist($lat1, $lat2, $long1, $long2)
    {
        return acos(sin(deg2rad($lat1))*sin(deg2rad($lat2))+cos(deg2rad($lat1))*cos(deg2rad($lat2))*cos(deg2rad($long2-$long1)))*6371;
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
            $lat_long = $this->get_geo_position();
            $bounding_box = $this->calculate_bounding_box($lat_long['lat'], $lat_long['long']);
            $amenities = $this->get_amenities();
            $result = $this->query_osm($bounding_box, $amenities);
            $processed = $this->process_result($result, $lat_long);
            $this->send_response($processed, true);
        }
        catch (Exception $ex)
        {
            $this->send_response($ex->getMessage(), false);
        }
    }
}

//------------------------------------------------------------------------------

class NoAmenitiesException extends Exception {};
class InvalidGeoPosition extends Exception {};
class OSMResponseError extends Exception {};

error_reporting(0);
       
new Freetime();