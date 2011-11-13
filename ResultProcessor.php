<?php
require_once "config.php";
require_once "Rating.php";
require_once "Exceptions.php";

//------------------------------------------------------------------------------
        
class ResultProcessor
{
    const max_locations = 100;
    const time_overdraw_percent = 30; //how many percent is the max time allowed to be overdrawn
    
    const SECTION_PARTY = "party";
    const SECTION_FUN = "fun";
    const SECTION_SPORT = "sport";
    const SECTION_SIGHTSEEING = "sightseeing";
	const SECTION_DATE = "date";
	const SECTION_EINKAUFEN = "einkaufen";
	const SECTION_MAHLZEIT = "mahlzeit";
	const SECTION_KULTUR = "kultur";
	//const SECTION_RANDOM = "random";
	
    public static function get_section_map()
    {
        $section_map = array
        (
		    //------------------------------------------------------------------
            "pub" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
			"bar" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "nightclub" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 180,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "stripclub" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 180,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "fuel" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),			
            //------------------------------------------------------------------
            "cinema" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 120,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "pub" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
			"bar" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "cafe" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 45,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "biergarten" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_MAHLZEIT),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "ice_cream" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "shelter" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "park" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 30,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "common" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 30,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "zoo" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 90,
                "osm_key" => "tourism",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),			
            //------------------------------------------------------------------
            "9pin" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "10pin" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "boules" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "archery" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "badminton" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "basketball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "beachvolleyball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "climbing" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "golf" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "shooting" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "soccer" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "swimming" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "vollayball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "tennis" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "water_park" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 120,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "miniature_golf" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),			
            //------------------------------------------------------------------
            "fountain" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "marketplace" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "place_of_worship" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "castle" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 90,
                "osm_key" => "historic",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "memorial" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "historic",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "monument" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "historic",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "ruins" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 45,
                "osm_key" => "historic",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "attraction" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "tourism",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "information" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "tourism",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),			
			//------------------------------------------------------------------
            "pub" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
			"bar" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "cafe" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "ice_cream" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "fountain" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "bench" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 4,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "shelter" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "hunting_stand" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "cinema" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 120,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "lighthouse" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "man_made",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "beach" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "natural",
                "max_amount" => 3,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
			//------------------------------------------------------------------
            "supermarket" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 45,
                "osm_key" => "building",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "bakery" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "beverages" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "butcher" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "chemist" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "department_store" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "greengrocer" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "organic" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
			//------------------------------------------------------------------
            "biergarten" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "fast_food" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "food_court" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => false,
                "can_visit" => function() { return true; }
            ),
            "bbq" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "restaurant" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
			//------------------------------------------------------------------
            "theatre" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 120,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "artwork" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 45,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),
            "museum" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "can_visit" => function() { return true; }
            ),			
        );
                
        return $section_map;
    }
        
    private static function calc_dist($lat1, $lat2, $long1, $long2)
    {
        return acos(sin(deg2rad($lat1))*sin(deg2rad($lat2))+cos(deg2rad($lat1))*cos(deg2rad($lat2))*cos(deg2rad($long2-$long1)))*6371;
    }
    
    public static function process_result($userid, $result, array $lat_long, array $request_sections, $totaltime)
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
                'section' => null, //e.g. food
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

            foreach ($n->tag as $t)
            {
                $key = strtolower(trim($t->attributes()->k)); //e.g. amenity or cuisine
                $val = (string)$t->attributes()->v; //e.g. restaurant or greek

                $continue = false;
                
                //check if element is contained within the section map
                foreach ($section_map as $place => $details)
                {
                    if ($key == $details['osm_key'] && $val == $place)
                    {
                        //determine output section based on intersection of the queryparams
                        $output_section = array_intersect($request_sections, $section_map[$val]['section']);

                        //no match was found. no problem, we will come back.
                        if (!count($output_section))
                            break;

                        $location['section'] = array_pop($output_section); //e.g. food
                        $location['place'] = $val; //e.g. restaurant
                        $continue = true;
                        break;
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
        
        $section_grouped = array();
        
        foreach ($locations as $place => &$items)
        {
            ksort($items);
            
            if (count($items) > $section_map[$place]['max_amount'])
                array_splice($items, $section_map[$place]['max_amount']);
            
            foreach ($items as $k => $item)
                $section_grouped[$item['section']][$k] = $item;
        }
        
        $all_items = array();
        
        //move first element of each section to the top
        foreach ($section_grouped as $section => &$items)
        {
            ksort($items);
             //get first key
            reset($items);
            $prio = key($items);
            $new_prio = "0.00" . $items[$prio]['id'];
            $items[$new_prio] = $items[$prio];
            unset($items[$prio]);
            ksort($items);
            
            foreach ($items as $prio => $item)
                $all_items[$prio] = $item;
        }
        
        ksort($all_items);
        
        //throw out locations if there are too much
        if (count($all_items) > self::max_locations)
            array_splice($all_items, self::max_locations);
        
        //perform preselect
        $used_time = 0;
        $max_time = $totaltime + $totaltime * (self::time_overdraw_percent / 100);
        
        //special array for all preselected items
        $preselected = array();
        
        foreach ($all_items as $prio => $item)
        {
            if ($used_time + $item['time'] >= $max_time)
                continue;

            $item['preselect'] = true;
            $used_time += $item['time'];
            
            $preselected[$prio] = $item;
            unset($all_items[$prio]);
        }
        
        //prepend the preselections to the array with the remaining items
        return array_values(array_merge($preselected, $all_items));
    }
                
}
    