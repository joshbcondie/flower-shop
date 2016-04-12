<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 5:18 PM
 */
class Driver
{
    public static function getESLs() {
        $eslQuery = 'SELECT ESL FROM driver';
        $stmt = Database::getDB()->prepare($eslQuery);
        $stmt->execute();
        $ESLs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $ESLs;
    }

}