<?php
require_once "config.php";
require_once "Rating.php";

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
                "request" => "Gehe in die Kneipe",
                "can_visit" => function() { return true; }
            ),
            "bar" => array
            (
                'section' => array(self::SECTION_PARTY, self::SECTION_FUN, self::SECTION_DATE),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Gehe in die Bar",
                "can_visit" => function() { return true; }
            ),
            "nightclub" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 180,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in den Club",
                "can_visit" => function() { return date("H") > 20 || date("H") < 08; }
            ),
            "stripclub" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 180,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in den Stripclub",
                "can_visit" => function() { return true; }
            ),
            "fuel" => array
            (
                'section' => array(self::SECTION_PARTY),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 2,
                "name_required" => "Tankstelle",
                "request" => "Betrete die Tankstelle",
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
                "request" => "Gehe in das Kino",
                "can_visit" => function() { return date("H") >= 12 || date("H") <= 02; }
            ),
            "cafe" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 45,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "request" => "Gehe in das Café",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "biergarten" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_MAHLZEIT),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "request" => "Gehe in den Biergarten",
                "can_visit" => function() { return (date("H") >= 09 || date("H") <= 01) && (date("n") >= 5 && date("n") <= 9); }
            ),
            "ice_cream" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in die Eisdiele",
                "can_visit" => function() { return date("H") > 06; }
            ),
            "park" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 30,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => "Park",
                "request" => "Gehe in den Park",
                "can_visit" => function() { return true; }
            ),
            "common" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 30,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => "Grünanlage",
                "request" => "Besuche die Grünanlage",
                "can_visit" => function() { return true; }
            ),
            "zoo" => array
            (
                'section' => array(self::SECTION_FUN),
                "time" => 90,
                "osm_key" => "tourism",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in den Zoo",
                "can_visit" => function() { return date("H") >= 08; }
            ),
            //------------------------------------------------------------------
            "9pin" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Kegelanlage",
                "request" => "Spiele eine Runde in der Kegelanlage",
                "can_visit" => function() { return true; }
            ),
            "10pin" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Bowlinganlage",
                "request" => "Spiele eine Runde in der Bowlinganlage",
                "can_visit" => function() { return true; }
            ),
            "boules" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Bouleanlage",
                "request" => "Spiele eine Runde in der Bouleanlage",
                "can_visit" => function() { return true; }
            ),
            "archery" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Besuche den Schießstand",
                "can_visit" => function() { return true; }
            ),
            "badminton" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Badmintonfeld",
                "request" => "Spiele eine Runde auf dem Badmintonfeld",
                "can_visit" => function() { return true; }
            ),
            "basketball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Basketballplatz",
                "request" => "Spiele eine Runde auf dem Basketballplatz",
                "can_visit" => function() { return true; }
            ),
            "beachvolleyball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Beachvolleyballfeld",
                "request" => "Spiele eine Runde auf dem Beachvolleyballfeld",
                "can_visit" => function() { return true; }
            ),
            "climbing" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Kletteranlage",
                "request" => "Besuche die Kletteranlage",
                "can_visit" => function() { return true; }
            ),
            "golf" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Golfplatz",
                "request" => "Spiele eine Runde auf dem Golfplatz",
                "can_visit" => function() { return true; }
            ),
            "shooting" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Schießstand",
                "request" => "Besuche den Schießstand",
                "can_visit" => function() { return true; }
            ),
            "soccer" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Fußballplatz",
                "request" => "Spiele eine Runde auf dem Fußballplatz",
                "can_visit" => function() { return true; }
            ),
            "swimming" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 90,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Schwimmbad",
                "request" => "Besuche das Schwimmbad",
                "can_visit" => function() { return true; }
            ),
            "volleyball" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Volleyballfeld",
                "request" => "Spiele eine Runde auf dem Volleyballfeld",
                "can_visit" => function() { return true; }
            ),
            "tennis" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "sport",
                "max_amount" => 3,
                "name_required" => "Tennisplatz",
                "request" => "Spiele eine Runde auf dem Tennisplatz",
                "can_visit" => function() { return true; }
            ),
            "water_park" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 120,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => "Wasserpark",
                "request" => "Besuche den Wasserpark",
                "can_visit" => function() { return true; }
            ),
            "miniature_golf" => array
            (
                'section' => array(self::SECTION_SPORT),
                "time" => 60,
                "osm_key" => "leisure",
                "max_amount" => 3,
                "name_required" => "Minigolfanlage",
                "request" => "Spiele eine Runde auf der Minigolfanlage",
                "can_visit" => function() { return true; }
            ),
            //------------------------------------------------------------------
            "fountain" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING, self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => "Wasseranlage",
                "request" => "Besichtige die Wasseranlage",
                "can_visit" => function() { return true; }
            ),
            "marketplace" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe auf den Marktplatz",
                "can_visit" => function() { return true; }
            ),
            "place_of_worship" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Besichtige die Sehenswürdigkeit",
                "can_visit" => function() { return true; }
            ),
            "castle" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 90,
                "osm_key" => "historic",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Besichtige die Burg",
                "can_visit" => function() { return true; }
            ),
            "memorial" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "historic",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Besichtige die Sehenswürdigkeit",
                "can_visit" => function() { return true; }
            ),
            "monument" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "historic",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Besichtige das Monument",
                "can_visit" => function() { return true; }
            ),
            "ruins" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 45,
                "osm_key" => "historic",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Besichtige die Ruine",
                "can_visit" => function() { return true; }
            ),
            "attraction" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 30,
                "osm_key" => "tourism",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Besichtige die Attraktion",
                "can_visit" => function() { return true; }
            ),
            "information" => array
            (
                'section' => array(self::SECTION_SIGHTSEEING),
                "time" => 15,
                "osm_key" => "tourism",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Besuche die Touristeninformation",
                "can_visit" => function() { return true; }
            ),
            //------------------------------------------------------------------
            "bench" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 2,
                "name_required" => "Sitzbank",
                "request" => "Setze dich auf die Bank",
                "can_visit" => function() { return true; }
            ),
            "shelter" => array
            (
                'section' => array(self::SECTION_FUN, self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => "Unterstand",
                "request" => "Gehe zum Unterstand",
                "can_visit" => function() { return true; }
            ),
            "hunting_stand" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => "Hochsitz",
                "request" => "Gehe auf den Hochsitz",
                "can_visit" => function() { return true; }
            ),
            "lighthouse" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "man_made",
                "max_amount" => 3,
                "name_required" => "Leuchtturm",
                "request" => "Gehe zu dem Leuchtturm",
                "can_visit" => function() { return true; }
            ),
            "beach" => array
            (
                'section' => array(self::SECTION_DATE),
                "time" => 30,
                "osm_key" => "natural",
                "max_amount" => 3,
                "name_required" => "Strand",
                "request" => "Gehe an den Strand",
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
                "request" => "Kaufe ein im Supermarkt",
                "can_visit" => function() { return true; }
            ),
            "bakery" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kauf ein in der Bäckerei",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "beverages" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 30,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein im Getränkemarkt",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "butcher" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein beim Metzger",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "chemist" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 30,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein in der Drogerie",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "department_store" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 45,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein im Kaufhaus",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "greengrocer" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein im Gemüseladen",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            "organic" => array
            (
                'section' => array(self::SECTION_EINKAUFEN),
                "time" => 15,
                "osm_key" => "shop",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Kaufe ein im Bio-Laden",
                "can_visit" => function() { return date("H") >= 06; }
            ),
            //------------------------------------------------------------------
            "fast_food" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 15,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "request" => "Esse etwas bei",
                "can_visit" => function() { return true; }
            ),
            "food_court" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 30,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => "Räumlichkeit",
                "request" => "Gehe in die Räumlichkeit",
                "can_visit" => function() { return true; }
            ),
            "bbq" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => "BBQ",
                "request" => "Gehe zum BBQ",
                "can_visit" => function() { return true; }
            ),
            "restaurant" => array
            (
                'section' => array(self::SECTION_MAHLZEIT),
                "time" => 60,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in das Restaurant",
                "can_visit" => function() { return date("H") >= 08; }
            ),
            //------------------------------------------------------------------
            "theatre" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 120,
                "osm_key" => "amenity",
                "max_amount" => 3,
                "name_required" => true,
                "request" => "Gehe in das Theater",
                "can_visit" => function() { return date("H") >= 09 || date("H") <= 01; }
            ),
            "artwork" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 45,
                "osm_key" => "amenity",
                "max_amount" => 9,
                "name_required" => true,
                "request" => "Besichtige das Kunstwerk",
                "can_visit" => function() { return true; }
            ),
            "museum" => array
            (
                'section' => array(self::SECTION_KULTUR),
                "time" => 90,
                "osm_key" => "amenity",
                "max_amount" => 6,
                "name_required" => true,
                "request" => "Besuche das Museum",
                "can_visit" => function() { return date("H") >= 08; }
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
            throw new OSMResponseError("Es gibt leider keine Ergebnisse fuer Ihre Suche.");
        
        //dont output the errors
        libxml_use_internal_errors(true);
        $resp = simplexml_load_string($result);
        $converting_error = count(libxml_get_errors()) !== 0;
        
        if ($converting_error)
            throw new OSMResponseError("Die Anfrage konnte leider aufgrund eines Fehlers bei OpenStreetMap nicht verarbeitet werden.");
        
        if (!count($resp->node))
            throw new OSMResponseError("Es gibt leider keine Ergebnisse fuer Ihre Suche. Bitte versuchen Sie eine andere Auswahl.");
        
        $tag_mapping = array
        (
            'addr:street' => 'Straße',
            'addr:housenumber' => 'Hausnr.',
            'note' => 'Infotext',
            'smoking' => 'Rauchen',
            'website' => 'website',
            'contact:website' => 'website',
            'wheelchair' => 'Rollstuhlgeeignet',
            'cuisine' => 'Küche',
            'opening_hours' => 'Öffnungszeiten',
            'phone' => 'Tel.',
            'contact:phone' => 'Tel.'
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
                "city" => null, //will be filled later
                "name" => null, //will be filled later
                "infotext" => array(), //will be filled later
                "id" => intval($n->attributes()->id),
                "lat" => $lat,
                "long" => $long,
                "dist" => $dist,
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
                
                switch ($key)
                {
                    case "addr:city":
                        $location['city'] = $val;
                        break;
                    case "name":
                        $location['name'] = $val;
                        break;
                    default:
                        if (isset($tag_mapping[$key]))
                            $location["infotext"][] = $tag_mapping[$key] . ": " . $val;
                }
            }
            
            $location["infotext"] = implode("; ", $location["infotext"]);
            
            if (empty($location["infotext"]))
                $location["infotext"] = null;
            
            //still no section / place: skip!
            if (empty($location['section']) || empty($location['place']))
                continue;
            
            $place = $location['place']; //e.g. restaurant
            
            //if we have an own name, append it to the request -> 'Gehe in die Grünanlage "Herrengarten"'
            //and only if we have the name! If we take the translation replacement, it would sound dump! -> 'Gehe in die Grünanlage "Grünanlage"'
            if (!empty($location['name']))
                $location['request'] = sprintf($section_map[$place]['request'] . " '%s'", $location['name']);
            else
                $location['request'] = $section_map[$place]['request'];
            
            //if the name is not required and we don't have one, take the translation from name_required ("Bank", ...)
            if (empty($location['name']) && $section_map[$place]['name_required'] !== true)
                $location['name'] = $section_map[$place]['name_required'];

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
                
        //totaltime is optional
        if ($totaltime)
        {
            foreach ($all_items as $prio => $item)
            {
                if ($used_time + $item['time'] >= $max_time)
                    continue;

                $item['preselect'] = true;
                $used_time += $item['time'];

                $preselected[$prio] = $item;
                unset($all_items[$prio]);
            }
        }
       
        //prepend the preselections to the array with the remaining items
        return array_values(array_merge($preselected, $all_items));
    }
                
}
    