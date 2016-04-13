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

    public static function addESL($ESL) {
        $insertEslQuery = 'INSERT INTO driver(ESL) VALUES(:ESL)';
        $stmt = Database::getDB()->prepare($insertEslQuery);
        $stmt->bindValue('ESL', $ESL);
        $stmt->execute();
        $driverId = Database::getDB()->lastInsertId();

        return $driverId;
    }

}