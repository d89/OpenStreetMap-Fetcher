<?php
header('content-type: text/html; charset=utf-8');
require_once "config.php";

class Rating
{
    private $response = array
    (
        "message" => "Die Bearbeitung der Anfrage ist fehlgeschlagen.", 
        "status_code" => false
    );    
    
    private $response_was_sent = false;
    
    public function shutdown_function() 
    {
        $error = error_get_last();
        if ($error)
        {
            $this->send_response("Interner Fehler: " . $error['message'], false);
        }
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
    
    private function enter_rating($db, $userid = null, $placeid = "", $rating = null)
    {
        $userid = trim($userid);
        if (strlen($userid) !== 32)
            throw new Exception("Ungueltige Benutzer-ID.");
        
        $placeid = intval($placeid);
        if ($placeid <= 0)
            throw new Exception("Ungueltige OSM-ID.");
        
        $rating = intval($rating) == 1;
  
        //insert
        $stmnt = $db->prepare('INSERT INTO `rating` (placeid, userid, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating=?');
        $vals = array($placeid, $userid, $rating, $rating);
        $res = $stmnt->execute($vals);
        
        if (!$res)
            throw new Exception("Interner Fehler: Datenbankzugriff ist gescheitert.");
    }
    
    public static function get_ratings_for_user($userid)
    {
        $db = DB::get();
        
        $stmnt = $db->prepare('SELECT placeid, rating FROM `rating` WHERE userid = ?');
		$stmnt->execute(array($userid));
		$res = $stmnt->fetchAll(PDO::FETCH_ASSOC);
        
        $return = array();
        
        foreach ($res as $r)
            $return[$r['placeid']] = $r['rating'];
        
        return $return;
    }
    
    public function __construct()
    {
        register_shutdown_function(array($this, 'shutdown_function'));
        
        try 
        {
            $db = DB::get();
            $this->enter_rating($db, $_GET['userid'], $_GET['placeid'], $_GET['rating']);
            $this->send_response("Successfull", true);
        }
        catch (Exception $ex)
        {
            $this->send_response($ex->getMessage(), false);
        }
    }
}

//------------------------------------------------------------------------------