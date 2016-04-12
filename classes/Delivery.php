<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 10:36 AM
 */
class Delivery
{
    const STATUS_PENDING = 'STATUS_PENDING';
    const STATUS_BID_ACCEPTED = 'STATUS_BID_ACCEPTED';
    const STATUS_COMPLETE = 'STATUS_COMPLETE';

    public static function createDeliveryRequest($order, $latitude, $longitude) {
        $createDeliveryRequestSQL = 'INSERT INTO delivery (order, latitude, longitude, status)
                                     VALUES (:order, :latitude, :longitude, :status)';
        $stmt = Database::getDB()->prepare($createDeliveryRequestSQL);
        $stmt->bindParam('order', $order);
        $stmt->bindParam('latitude', $latitude);
        $stmt->bindParam('longitude', $longitude);
        $stmt->bindValue('status', self::STATUS_PENDING);
        $stmt->execute();

        $deliveryId = Database::getDB()->lastInsertId();
        
        self::broadcastRequestForDelivery($deliveryId);

    }
    
    public static function getDelivery($deliveryId) {
        $deliveryDetailsSQL = 'SELECT id, order, latitude, longitude, status, timestamp
                               FROM delivery
                               WHERE id = :deliveryId';
        $stmt = Database::getDB()->prepare($deliveryDetailsSQL);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();
        
        $deliveryDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $deliveryDetails;
        
    }
    
    public static function broadcastRequestForDelivery($deliveryId) {
        $deliveryDetails = self::getDelivery($deliveryId);
        $ESLs = Driver::getESLs();
        
        foreach ($ESLs as $ESL) {
            $event = array(
                '_domain' => 'rfq',
                '_name' => 'delivery_ready',
                'order_id' => $deliveryDetails['id'],
                'order' => $deliveryDetails['order'],
                'latitude' => $deliveryDetails['latitude'],
                'longitude' => $deliveryDetails['longitude'],
                'order_time' => $deliveryDetails['timestamp'],
            );

            self::sendEvent($ESL, $event);
        }
    }

    private static function sendEvent($ESL, $eventData) {
//        error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$url ' . print_r($url, true));
        $parsedURL = parse_url($ESL);
//        error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$parsedURL ' . print_r($parsedURL, true));
        $url = '';
        if (!isset($parsedURL['scheme']) || !$parsedURL['scheme']) {
            $url = 'http://';
        }
        $url .= self::unparseUrl($parsedURL);

        $data = json_encode($eventData);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );

        $result = curl_exec($ch);

//        error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$result ' . print_r($result, true));

        //curl errors
        if(curl_errno($ch) != 0)
        {

            error_log('[' . __CLASS__ . '][' . __FUNCTION__ . '] - invalid curl error', E_USER_WARNING);
            error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$url ' . print_r($url, true));
            error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::data ' . print_r($data, true));
            error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$result ' . print_r($result, true));
        }

        return $result;
    }
    private static function unparseUrl($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}