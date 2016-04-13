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
    const STATUS_BID_RECEIVED = 'STATUS_BID_RECEIVED';
    const STATUS_BID_ACCEPTED = 'STATUS_BID_ACCEPTED';
    const STATUS_BID_REJECTED = 'STATUS_BID_REJECTED';
    const STATUS_COMPLETE = 'STATUS_COMPLETE';

    public static function createDeliveryRequest($order, $address, $latitude, $longitude) {
        $createDeliveryRequestSQL = 'INSERT INTO delivery (order_details, address, latitude, longitude, status)
                                     VALUES (:order_details, address, :latitude, :longitude, :status)';
        $stmt = Database::getDB()->prepare($createDeliveryRequestSQL);
        $stmt->bindParam('order_details', $order);
        $stmt->bindParam('address', $address);
        $stmt->bindParam('latitude', $latitude);
        $stmt->bindParam('longitude', $longitude);
        $stmt->bindValue('status', self::STATUS_PENDING);
        $stmt->execute();

        $deliveryId = Database::getDB()->lastInsertId();
        
        self::broadcastRequestForDelivery($deliveryId);

    }
    
    public static function getDelivery($deliveryId) {
        $deliveryDetailsSQL = 'SELECT id, order_details, address, latitude, longitude, status, timestamp
                               FROM delivery
                               WHERE id = :deliveryId';
        $stmt = Database::getDB()->prepare($deliveryDetailsSQL);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();
        
        $deliveryDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        $bidSQL = 'SELECT id, driver_name, estimated_time, status
                   FROM bid
                   WHERE delivery_id = :deliveryId';
        $stmt = Database::getDB()->prepare($bidSQL);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();

        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $deliveryDetails['bids'] = $bids;
        
        return $deliveryDetails;
        
    }

    public static function getDeliveries() {
        $deliveryDetailsSQL = 'SELECT id, order_details, address, latitude, longitude, status, timestamp
                               FROM delivery
                               WHERE 1';
        $stmt = Database::getDB()->prepare($deliveryDetailsSQL);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();

        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $deliveries;
    }

    public static function makeBid($deliveryId, $driverName, $estimatedTime) {
        $createBidSQL = 'INSERT INTO bid (delivery_id, driver_name, estimated_time, status)
                         VALUES (:delivery_id, :driver_name, :estimated_time, :status)';
        $stmt = Database::getDB()->prepare($createBidSQL);
        $stmt->bindParam('delivery_id', $deliveryId);
        $stmt->bindParam('driver_name', $driverName);
        $stmt->bindParam('estimated_time', $estimatedTime);
        $stmt->bindValue('status', self::STATUS_PENDING);
        $stmt->execute();

        self::updateDeliveryStatus($deliveryId, self::STATUS_BID_RECEIVED);
    }

    public static function acceptBid($bidId) {
        $deliveryIdSQL = 'SELECT delivery_id FROM bid WHERE id = :bidId';
        $stmt = Database::getDB()->prepare($deliveryIdSQL);
        $stmt->bindParam('bidId', $bidId);
        $stmt->execute();

        $deliveryId = $stmt->fetch(PDO::FETCH_ASSOC);
//        error_log('[Delivery.php]::$deliveryId: ' . print_r($deliveryId, true));
        $deliveryId = $deliveryId['delivery_id'];

        $updateBidStatusSQL = 'UPDATE bid
                               SET status = :status
                               WHERE delivery_id = :deliveryId';
        $stmt = Database::getDB()->prepare($updateBidStatusSQL);
        $stmt->bindValue('status', self::STATUS_BID_REJECTED);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();
        
        $updateBidStatusSQL = 'UPDATE bid
                               SET status = :status
                               WHERE id = :bidId';
        $stmt = Database::getDB()->prepare($updateBidStatusSQL);
        $stmt->bindParam('bidId', $bidId);
        $stmt->bindValue('status', self::STATUS_BID_ACCEPTED);
        $stmt->execute();

        self::updateDeliveryStatus($deliveryId, self::STATUS_BID_ACCEPTED);
    }

    public static function updateDeliveryStatus($deliveryId, $status) {
        $updateDeliveryStatusSQL = 'UPDATE delivery
                                    SET status = :status
                                    WHERE id = :deliveryId';
        $stmt = Database::getDB()->prepare($updateDeliveryStatusSQL);
        $stmt->bindParam('status', $status);
        $stmt->bindParam('deliveryId', $deliveryId);
        $stmt->execute();
    }
    
    public static function broadcastRequestForDelivery($deliveryId) {
        $deliveryDetails = self::getDelivery($deliveryId);
        $ESLs = Driver::getESLs();
        
        foreach ($ESLs as $ESL) {
            $event = array(
                '_domain' => 'rfq',
                '_name' => 'delivery_ready',
                'order_id' => $deliveryDetails['id'],
                'order_details' => $deliveryDetails['order_details'],
                'address' => $deliveryDetails['address'],
                'latitude' => $deliveryDetails['latitude'],
                'longitude' => $deliveryDetails['longitude'],
                'order_time' => $deliveryDetails['timestamp'],
            );

            self::sendEvent($ESL['ESL'], $event);
        }
    }

    private static function sendEvent($ESL, $eventData) {
//        error_log('[' . __CLASS__ . '][' . __FUNCTION__ . ']::$ESL ' . print_r($ESL, true));
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