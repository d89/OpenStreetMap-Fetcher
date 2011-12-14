<?php
class DB
{
    private static $db = null;
        
    public static function get()
    {
        if (!is_null(self::$db))
            return self::$db;
        
        try 
        {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB, DB_USER, DB_PW);
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
}