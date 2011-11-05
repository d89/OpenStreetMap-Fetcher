<?php
require_once "Rating.php";
require_once "Exceptions.php";

//------------------------------------------------------------------------------
        
class ResultProcessor
{
    const max_locations = 50;
    
    const SECTION_FOOD = "food";
    const SECTION_PARTY = "party";
    const SECTION_SPORT = "sport";
    
    public static function get_section_map()
    {
        $section_map = array
        (
            //------------------------------------------------------------------
            "restaurant" => array
            (
                "section" => self::SECTION_FOOD,
                "time" => 80,
                "osm_key" => "amenity",
                "max_amount" => 15,
                "name_required" => true,
                "can_visit" => function() { return date("G") > 8 || date("G") < 2; }
            ),
            "fast_food" => array
            (
                "section" => self::SECTION_FOOD,
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 25,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            //------------------------------------------------------------------
            "pub" => array
            (
                "section" => self::SECTION_PARTY,
                "time" => 80,
                "osm_key" => "amenity",
                "max_amount" => 30,
                "name_required" => true,
                "can_visit" => function() { return date("G") >= 15 || date("G") < 4; }
            ), 
            "nightclub"  => array
            (
                "section" => self::SECTION_PARTY,
                "time" => 200,
                "osm_key" => "amenity",
                "max_amount" => 15,
                "name_required" => true,
                "can_visit" => function() { return date("G") > 22 || date("G") < 6; }
            ),
            //------------------------------------------------------------------
            "soccer"  => array
            (
                "section" => self::SECTION_SPORT,
                "time" => 50,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return date("G") < 22 && date("G") > 7; }
            ),
            "table_tennis"  => array
            (
                "section" => self::SECTION_SPORT,
                "time" => 30,
                "osm_key" => "sport",
                "max_amount" => 7,
                "name_required" => false,
                "can_visit" => function() { return date("G") < 22 && date("G") > 7; }
            ),
            "basketball"  => array
            (
                "section" => self::SECTION_SPORT,
                "time" => 40,
                "osm_key" => "sport",
                "max_amount" => 5,
                "name_required" => false,
                "can_visit" => function() { return date("G") < 22 && date("G") > 7; }
            ),
            "cycling"  => array
            (
                "section" => self::SECTION_SPORT,
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 5,
                "name_required" => false,
                "can_visit" => function() { return date("G") < 22 && date("G") > 7; }
            )
        );
                
        return $section_map;
    }
        
    private static function calc_dist($lat1, $lat2, $long1, $long2)
    {
        return acos(sin(deg2rad($lat1))*sin(deg2rad($lat2))+cos(deg2rad($lat1))*cos(deg2rad($lat2))*cos(deg2rad($long2-$long1)))*6371;
    }
    
    public static function process_result($userid, $result, array $lat_long)
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
            'name' => 'name',
            'note' => 'info',
            'smoking' => 'smoking',
            'website' => 'website',
            'contact:website' => 'website',
            'wheelchair' => 'wheelchair',
            'addr:country' => 'country',
            'cuisine' => 'cuisine',
            'opening_hours' => 'opening_hours',
            'phone' => 'phone',
            'contact:phone' => 'phone'
        );
        
        //get the ratings
        $ratings = array();
        
        if ($userid)
            $ratings = Rating::get_ratings_for_user($userid);
        
        $section_map = self::get_section_map();
        
        $locations = array();

        foreach ($resp->node as $n)
        {
            $lat = floatval($n->attributes()->lat);
            $long = floatval($n->attributes()->lon);
            $dist = self::calc_dist($lat, $lat_long['lat'], $long, $lat_long['long']);
            
            $location = array
            (
                "id" => intval($n->attributes()->id),
                "lat" => $lat,
                "long" => $long,
                "dist" => $dist,
                'additional' => array(),
                "preselect" => false,
                "section" => null, //e.g. food
                "place" => null //e.g. restaurant
            );
            
            $order_key = $dist;
            
            //check if there was a rating for this location id
            if (isset($ratings[$location['id']]))
            {
                $rating = $ratings[$location['id']];
                
                //manipulate the order key accordingly
                if ($rating == 1)
                    $order_key /= 3;
                else
                    $order_key *= 3;
            }

            foreach (array_unique(array_values($tag_mapping)) as $tag)
                $location[$tag] = null;

            $location_is_assigned = false;
            
            foreach ($n->tag as $t)
            {
                $key = strtolower(trim($t->attributes()->k)); //e.g. amenity or cuisine
                $val = (string)$t->attributes()->v; //e.g. restaurant or greek

                $continue = false;
                
                if (!$location_is_assigned)
                {
                    //check if element is contained within the section map
                    foreach ($section_map as $place => $details)
                    {
                        if ($key == $details['osm_key'] && $val == $place)
                        {
                            $location['section'] = $section_map[$val]['section']; //e.g. food
                            $location['place'] = $val; //e.g. restaurant
                            $location_is_assigned = true;
                            $continue = true;
                            break;
                        }
                    }
                }
                
                //skip adding to original, if it was the section (amenity, ...)
                if ($continue)
                    continue;
                
                if (isset($tag_mapping[$key]))
                    $location[$tag_mapping[$key]] = $val;
                else
                    $location['additional'][$key] = $val;
            }
            
            //still no section / place: skip!
            if (empty($location['section']) || empty($location['place']))
                continue;
            
            $place = $location['place']; //e.g. restaurant
            
            //if the name is not required and we don't have one, take the place ("table_tennis")
            if (empty($location['name']) && !$section_map[$place]['name_required'])
                $location['name'] = $place;

            //no name? worthless for us
            if (empty($location['name']))
                continue;
            
            $location['time'] = $section_map[$place]['time'];
            
            //prevent overwriting when we sort after key
            $order_key = "$order_key" . $location['id'];
            $locations[$place][$order_key] = $location;
        }
        
        $all_items = array();
        
        foreach ($locations as $place => &$items)
        {
            ksort($items);
            
            if (count($items) > $section_map[$place]['max_amount'])
                array_splice($items, $section_map[$place]['max_amount']);
            
            foreach ($items as $k => $item)
                $all_items[$k] = $item;
        }
        
        ksort($all_items);
        
        if (count($all_items) > self::max_locations)
            array_splice($all_items, self::max_locations);
        
        //perform preselect: dummy implementation
        $max_preselect_items = 5;
        
        foreach ($all_items as &$location)
        {
            if ($max_preselect_items-- == 0)
                break;
            
            $location['preselect'] = true;
        }
        
        return array_values($all_items);
    }
                
}
    