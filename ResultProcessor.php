<?php
require_once "Rating.php";
require_once "Exceptions.php";

//------------------------------------------------------------------------------
        
class ResultProcessor
{
    const max_locations = 50;
    
    const CATEGORY_FOOD = "food";
    const CATEGORY_PARTY = "party";
    
    public static function get_category_map()
    {
        $category_map = array
        (
            "restaurant" => array
            (
                "category" => self::CATEGORY_FOOD,
                "time" => 80,
                "max_amount" => 15,
                "can_visit" => function() { return date("G") > 8 || date("G") < 2; }
            ),
            "fast_food" => array
            (
                "category" => self::CATEGORY_FOOD,
                "time" => 30,
                "amount" => 25,
                "can_visit" => function() { return true; }
            ),
            "pub" => array
            (
                "category" => self::CATEGORY_PARTY,
                "time" => 80,
                "max_amount" => 30,
                "can_visit" => function() { return date("G") > 15 || date("G") < 4; }
            ), 
            "nightclub"  => array
            (
                "category" => self::CATEGORY_PARTY,
                "time" => 200,
                "max_amount" => 15,
                "can_visit" => function() { return date("G") > 22 || date("G") < 6; }
            )
        );
                
        return $category_map;
    }
        
    private static function calc_dist($lat1, $lat2, $long1, $long2)
    {
        return acos(sin(deg2rad($lat1))*sin(deg2rad($lat2))+cos(deg2rad($lat1))*cos(deg2rad($lat2))*cos(deg2rad($long2-$long1)))*6371;
    }
    
    public static function get_amenities_from_categories(array $categories, $totaltime)
    {
        $amenities = array();
        $category_map = self::get_category_map();
        
        foreach ($categories as $c)
        {
            foreach ($category_map as $amenity => $item)
            {
                if ($item['category'] == $c && $item["can_visit"]())
                {
                    if ($totaltime && $item["time"] > $totaltime)
                        continue;
                    
                    $amenities[] = $amenity;
                }
            }
        }
        
        return array_unique($amenities);
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
            'amenity' => 'amenity',
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
        
        $category_map = self::get_category_map();
        
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
                'additional' => array()
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
                $key = strtolower(trim($t->attributes()->k));
                $val = (string)$t->attributes()->v;

                if (isset($tag_mapping[$key]))
                    $location[$tag_mapping[$key]] = $val;
                else
                    $location['additional'][$key] = $val;
            }

            //locations without name AND (valid) amenity are worthless for us
            if (empty($location['name']) || empty($location['amenity']) || !isset($category_map[$location['amenity']]))
                continue;
            
            //prevent overwriting when we sort after key
            $order_key = "$order_key" . $location['id'];
            $amenity = $location['amenity'];
            $locations[$amenity][$order_key] = $location;
        }
        
        $all_items = array();
        
        foreach ($locations as $amenity => &$items)
        {
            ksort($items);
            
            if (count($items) > $category_map[$amenity]['max_amount'])
                array_splice($items, $category_map[$amenity]['max_amount']);
            
            foreach ($items as $k => $item)
                $all_items[$k] = $item;
        }
        
        ksort($all_items);
        
        if (count($all_items) > self::max_locations)
            array_splice($all_items, self::max_locations);
        
        return array_values($all_items);
    }
                
}
    