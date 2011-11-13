<?php
header('content-type: text/html; charset=utf-8');
require_once "config.php";
require_once "Exceptions.php";

class Rating
{
    const db_host = "localhost";
    const db = "d0124838";
    const db_user = "root";
    const db_pw = "";
    
    private static $db = null;
    
    private $response = array
    (
        "message" => "Request could not be processed", 
        "status_code" => false
    );    
    
    private $response_was_sent = false;
    
    public function shutdown_function() 
    {
        $error = error_get_last();
        if ($error)
        {
            $this->send_response("Internal Error: " . $error['message'], false);
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
    
    private static function connect()
    {
        if (!is_null(self::$db))
            return self::$db;
        
        try 
        {
            $db = new PDO("mysql:host=".self::db_host.";dbname=".self::db, self::db_user, self::db_pw);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->query( "set character set utf8" );
            $db->query( "set names utf8" );
            
            self::$db = $db;
            return self::$db;
        }
        catch (PDOException $ex)
        {
            throw new Exception("Could not connect to database: " . $ex->getMessage());
        }
    }
    
    private function enter_rating($db, $userid = null, $placeid = "", $rating = null)
    {
        $userid = trim($userid);
        if (strlen($userid) !== 32)
            throw new Exception("Invalid User-ID");
        
        $placeid = intval($placeid);
        if ($placeid <= 0)
            throw new Exception("Invalid Place-ID");
        
        $rating = intval($rating) == 1;
  
        //insert
        $stmnt = $db->prepare('INSERT INTO `rating` (placeid, userid, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating=?');
        $vals = array($placeid, $userid, $rating, $rating);
        $res = $stmnt->execute($vals);
        
        if (!$res)
            throw new Exception("Could not insert rating into database");
    }
    
    public static function get_ratings_for_user($userid)
    {
        $db = self::connect();
        
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
            $db = self::connect();
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