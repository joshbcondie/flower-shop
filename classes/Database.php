<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 10:08 AM
 */

class Database extends PDO {

    static protected $db;
    
    protected $dbHost;
    protected $dbName;
    protected $user;
    protected $pass;

    public function __construct() {
        $dbHost = $this->dbHost ? $this->dbHost : 'localhost';
        $dbName = $this->dbName ? $this->dbName : 'shop';
        $user = $this->user ? $this->user : 'shop';
        $pass = $this->pass ? $this->pass : 'shop';
        parent::__construct("mysql:host=$dbHost;dbname=$dbName", $user, $pass);
    }

    public static function &getDB() {
        if (!isset(self::$db))
            self::$db = new Database();
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return self::$db;
    }

}
