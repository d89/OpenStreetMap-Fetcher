<?php
header('content-type: text/html; charset=utf-8');
require_once "config.php";
require_once "Rating.php";
require_once "ResultProcessor.php";

class Freetime
{
    private $response = array
    (
        "message" => "Die Anfrage konnte nicht verarbeitet werden", 
        "status_code" => false
    );    
    
    const radius = 10; //km

    private $response_was_sent = false;
        
    public function shutdown_function() 
    {
        $error = error_get_last();
        if ($error)
        {
            $this->send_response("Interner Fehler: " . $error['message'], false);
        }
    }
        
    private function get_user_id()
    {
        if (isset($_GET['userid']) && strlen(trim($_GET['userid'])) == 32)
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
            throw new InvalidGeoPosition("Ungueltiger Wert fuer den Breitengrad.");
            
        $long = floatval($_GET['long']);
        if ($long < -180 || $long > 1800)
            throw new InvalidGeoPosition("Ungueltiger Wert fuer den Laengengrad.");
        
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

    private function query_osm($xml)
    {
        //$this->send_response(htmlentities($xml, ENT_COMPAT, "UTF-8"));
        
        //see http://www.overpass-api.de/query_form.html
        $ch = curl_init("http://www.overpass-api.de/api/interpreter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $content = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)
             throw new OSMResponseError("Interner Fehler: Die API-Antwort ist invalid.");  
        
        curl_close($ch);
        
        return $content;
    }
    
    private function send_response($message = null, $status_code = null, $requestkey = false)
    {
        if ($this->response_was_sent) 
            return;
        
        if (!is_null($status_code))
            $this->response['status_code'] = $status_code;
        
        if (!is_null($message))
            $this->response['message'] = $message;

        $this->response_was_sent = true;
        
        if ($requestkey)
        {
            $db = DB::get();
            $stmnt = $db->prepare('UPDATE `history` SET successfull = ?, message = ? WHERE requestkey = ?');
            $vals = array(intval($status_code), print_r($message, true), $requestkey);
            $res = $stmnt->execute($vals);
        }
        
        die(json_encode($this->response));
    }   
    
    private function get_request_sections()
    {
        //sanitize sections
        if (!isset($_GET['action']) || empty($_GET['action']))
            throw new NoCategoriesException("Es wurden keine Kategorien angegeben.");

        $sections = explode(",", strtolower(trim($_GET['action'])));
        
        return $sections;
    }
    
    private function get_query_params($totaltime, array $request_sections)
    {
        $queryparams = array();
        $section_map = ResultProcessor::get_section_map();
        
        foreach ($request_sections as $s)
        {
            foreach ($section_map as $place => $item)
            {
                if (in_array($s, $item['section']) && $item["can_visit"]())
                {
                    if ($totaltime && $item["time"] > $totaltime)
                        continue;
                    
                    $queryparams[$item['osm_key']][] = $place;
                }
            }
        }
        
        if (!count($queryparams))
            throw new NoSectionsException("Es konnten keine Ergebnisse geliefert geliefert werden, da Ihre angegebene Zeit zu kurz ist.");
        
        return $queryparams;
    }     
    
    private function create_request_xml(array $bbox, array $queryparams)
    {
        $xml = '<union>%s</union><print mode="body"/>';
        $bbox_string = '<bbox-query s="' . $bbox['sw']['lat'] . '" n="' . $bbox['ne']['lat'] . '" w="' . $bbox['sw']['long'] . '" e="' . $bbox['ne']['long'] . '"/>';
        $query = '<query type="node">' . $bbox_string . '<has-kv k="%s" v="%s"/></query>';

        $querynodes = array();

        foreach ($queryparams as $key => $values)
        {
            foreach ($values as $value)
                $querynodes[] = sprintf($query, $key, $value);
        }

        return sprintf($xml, implode("\n", $querynodes));
    }
    
    public function log_request($request_key, $userid, $action, $lat, $long, $totaltime)
    {
        $db = DB::get();
        $stmnt = $db->prepare('INSERT INTO `history` (userid, action, lat, `long`, totaltime, `time`, requestkey) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $vals = array($userid, implode(",", $action), $lat, $long, $totaltime, date("Y-m-d H:i:s"), $request_key);
        $res = $stmnt->execute($vals);
    }
    
    public function __construct()
    {
        register_shutdown_function(array($this, 'shutdown_function'));
        
        $request_key = uniqid();
        
        try 
        {
            $userid = $this->get_user_id();
            $totaltime = $this->get_total_time();
            $lat_long = $this->get_geo_position();
            $bounding_box = $this->calculate_bounding_box($lat_long['lat'], $lat_long['long']);
            $request_sections = $this->get_request_sections();
            $this->log_request($request_key, $userid, $request_sections, $lat_long['lat'], $lat_long['long'], $totaltime);
            $queryparams = $this->get_query_params($totaltime, $request_sections);
            $request_xml = $this->create_request_xml($bounding_box, $queryparams);
            $result = $this->query_osm($request_xml);
            $processed = ResultProcessor::process_result($userid, $result, $lat_long, $request_sections, $totaltime);
            $this->send_response($processed, true, $request_key);
        }
        catch (Exception $ex)
        {
            $this->send_response($ex->getMessage(), false, $request_key);
        }
    }
}

//------------------------------------------------------------------------------