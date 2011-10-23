<?php
class Freetime
{
    private $response = array
    (
        "message" => "Request could not be processed", 
        "status_code" => false
    );    
    
    const radius = 50; //50km
    
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
        $bbox_string = implode(",", array($bbox['nw']['long'], $bbox['nw']['lat'], $bbox['se']['long'], $bbox['se']['lat']));
        $amenity_string = implode("|", $amenities);
        $url = sprintf("http://www.overpass-api.de/api/xapi?node[bbox=%s][amenity=%s][@meta]", $bbox_string, $amenity_string);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    
    private function process_result($result)
    {
        if (empty($result))
            throw new OSMResponseError("The OSM Response was empty");

        /*
        dummy:
        $result = '<osm version="0.6" generator="Overpass API">
        <note>The data included in this document is from www.openstreetmap.org. It has there been collected by a large group of contributors. For individual attribution of each item please refer to http://www.openstreetmap.org/api/0.6/[node|way|relation]/#id/history </note>
        <meta osm_base="2011-10-17T16\:36\:02Z"/>
          <node id="24924135" lat="49.8800621" lon="8.6453454" version="11" timestamp="2011-06-13T19:02:36Z" changeset="8424331" uid="290680" user="wheelmap_visitor">
            <tag k="addr:city" v="Darmstadt"/>
            <tag k="addr:housenumber" v="41"/>
            <tag k="addr:postcode" v="64293"/>
            <tag k="addr:street" v="Kahlertstraße"/>
            <tag k="amenity" v="pub"/>
            <tag k="name" v="Kneipe 41"/>
            <tag k="note" v="DaLUG Meeting (4st Friday of month 19:30)"/>
            <tag k="smoking" v="no"/>
            <tag k="website" v="http://www.kneipe41.de/"/>
            <tag k="wheelchair" v="limited"/>
          </node>
          <node id="203582455" lat="49.8716253" lon="8.6393520" version="5" timestamp="2011-06-13T19:04:38Z" changeset="8424331" uid="290680" user="wheelmap_visitor">
            <tag k="addr:city" v="Darmstadt"/>
            <tag k="addr:country" v="DE"/>
            <tag k="addr:housenumber" v="69"/>
            <tag k="addr:postcode" v="64295"/>
            <tag k="addr:street" v="Rheinstraße"/>
            <tag k="amenity" v="fast_food"/>
            <tag k="cuisine" v="burger"/>
            <tag k="name" v="McDonalds"/>
            <tag k="website" v="http://www.mcdonalds.com/"/>
            <tag k="wheelchair" v="yes"/>
          </node>
        </osm>';
        */
        
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
            'cuisine' => 'cuisine'
        );

        $locations = array();

        foreach ($resp->node as $n)
        {
            $location = array
            (
                "id" => intval($n->attributes()->id),
                "lat" => floatval($n->attributes()->lat),
                "long" => floatval($n->attributes()->lon),
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

            $locations[] = $location;
        }
        
        return $locations;
    }
    
    private function send_response($message = null, $status_code = null)
    {
        if (!is_null($status_code))
            $this->response['status_code'] = $status_code;
        
        if (!is_null($message))
            $this->response['message'] = $message;

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
            $processed = $this->process_result($result);
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