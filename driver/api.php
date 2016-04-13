<?php
    $url = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    $max_miles = 5;
    $mph = 30;
    $post = json_decode(file_get_contents('php://input'), true);
    
    function append_event($url, $param) {
        return strpos($url, '?') === false ? $url . '?event=' . $param : $url . '&event=' . $param;
    }
    
    function distance($latitude1, $longitude1, $latitude2, $longitude2) {
        $earth_radius = 6371;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return 0.621371 * $d;
    }
    
    function getRealPOST() {
        $pairs = explode("&", file_get_contents("php://input"));
        $vars = array();
        foreach ($pairs as $pair) {
            $nv = explode("=", $pair);
            $name = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            $vars[$name] = $value;
        }
        return $vars;
    }
    
    function generateRandomString($length = 10) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
    
    if (isset($_GET['event'])) {
        switch ($_GET['event']) {
            case 'new_text':
                if (!isset($_POST['Body']))
                    break;
                
                if (strcasecmp(trim($_POST['Body']), 'bid anyway') === 0) {
                    
                    $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                
                    $stmt = $conn->prepare('SELECT * FROM driver');
                    $stmt->execute();
                    $driver = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $stmt = $conn->prepare('
                        SELECT delivery.id AS id, 
                                shop.url AS shop_url, 
                                delivery.estimated_time AS estimated_time, 
                                delivery.timestamp AS timestamp
                        FROM delivery 
                        INNER JOIN shop ON delivery.shop_id = shop.id
                        ORDER BY timestamp DESC LIMIT 1 
                    ');
                    $stmt->execute();
                    $delivery = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $conn->close();
                    
                    $content = json_encode(array(
                        'delivery_id' => $delivery['id'],
                        'driver_name' => $driver['name'],
                        'estimated_time' => floatval($delivery['estimated_time'])
                    ));
                    $curl = curl_init(append_event($delivery['shop_url'], 'bid_available'));
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
                    curl_exec($curl);
                    curl_close($curl);
                }
                else if (strcasecmp(trim($_POST['Body']), 'delivery complete') === 0) {
                    
                    $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                                    
                    $stmt = $conn->prepare('SELECT * FROM driver');
                    $stmt->execute();
                    $driver = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $stmt = $conn->prepare('
                        SELECT driver.name AS driver_name, 
                                delivery.id AS id, 
                                shop.url AS shop_url 
                        FROM delivery 
                        INNER JOIN shop ON delivery.shop_id = shop.id 
                        INNER JOIN driver ON delivery.id = driver.delivery_id
                    ');
                    $stmt->execute();
                    $delivery = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $conn->query('UPDATE driver SET status = \'AVAILABLE\', delivery_id=\'\'');
                    
                    $conn->close();
                    
                    $content = json_encode(array(
                        'id' => $delivery['id'],
                        'driver_name' => $delivery['driver_name']
                    ));
                    $curl = curl_init(append_event($delivery['shop_url'], 'delivery_complete'));
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
                    curl_exec($curl);
                    curl_close($curl);
                }
                
                break;
            case 'registration':
                if (!isset($post['client_id']) || !isset($post['client_secret']) || !isset($post['account_sid']) || !isset($post['auth_token']) || !isset($post['phone']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('UPDATE oauth SET client_id = ?, client_secret = ?, account_sid = ?, auth_token = ?, phone = ?');
                $stmt->bind_param('sssss', $post['client_id'], $post['client_secret'], $post['account_sid'], $post['auth_token'], $post['phone']);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                echo 'foursquare registration';
                break;
            case 'change_phone':
                if (!isset($post['phone']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('UPDATE driver SET phone = ?');
                $stmt->bind_param('s', $post['phone']);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                echo 'changed phone';
                break;
            case 'new_location':
                $real_post = getRealPOST();
                
                if (!isset($real_post['checkin']['venue']['location']['lat']) || !isset($real_post['checkin']['venue']['location']['lng']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('UPDATE driver SET latitude = ?, longitude = ?');
                $stmt->bind_param('dd', $post['checkin']['venue']['location']['lat'], $real_post['checkin']['venue']['location']['lng']);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                echo 'new location';
                break;
            case 'delivery_ready':
                if (!isset($post['id']) || !isset($post['address']) || !isset($post['latitude']) || !isset($post['longitude']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('SELECT * FROM shop WHERE id = ?');
                $stmt->bind_param('s', $_GET['shop_id']);
                $stmt->execute();
                $shop = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare('SELECT * FROM driver');
                $stmt->execute();
                $driver = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare('SELECT * FROM oauth');
                $stmt->execute();
                $oauth = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $conn->close();
                
                if ($driver['status'] !== 'AVAILABLE')
                    break;
                
                $distance_to_shop = distance(floatval($shop['latitude']), floatval($shop['longitude']), floatval($driver['latitude']), floatval($driver['longitude']));
                $distance_to_recipient = distance(floatval($shop['latitude']), floatval($shop['longitude']), floatval($post['latitude']), floatval($post['longitude']));
                $distance = round($distance_to_shop + $distance_to_recipient, 2);
                $estimated_time = round($distance * 60 / $mph, 2);
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                    
                $stmt = $conn->prepare('INSERT INTO delivery (id, shop_id, address, distance, estimated_time) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssdd', $post['id'], $_GET['shop_id'], $post['address'], $distance, $estimated_time);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                $text_content = 'Flower shop: ' . $shop['name'] . "\n";
                $text_content .= 'Address: ' . $shop['address'] . "\n";
                $text_content .= 'Recipient address: ' . $post['address'] . "\n";
                $text_content .= 'Distance: ' . $distance . " miles\n";
                $text_content .= 'Estimated time: ' . $estimated_time . " minutes\n";
                
                if ($distance_to_shop <= $max_miles) {
                    $content = json_encode(array(
                        'delivery_id' => $post['id'],
                        'driver_name' => $driver['name'],
                        'estimated_time' => $estimated_time
                    ));
                    $curl = curl_init(append_event($shop['url'], 'bid_available'));
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
                    curl_exec($curl);
                    curl_close($curl);
                    
                    $text_content .= 'Bid made automatically';
                }
                else {
                    $text_content .= 'Text "bid anyway" to bid.';
                }
                
                $curl = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . $oauth['account_sid'] . '/Messages.json');
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, 'From=' . $oauth['phone'] . '&To=' . $driver['phone'] . '=&Body=' . urlencode($text_content));
                curl_setopt($curl, CURLOPT_USERPWD, $oauth['account_sid'] . ':' . $oauth['auth_token']);
                curl_exec($curl);
                curl_close($curl);
                
                echo 'delivery is ready';
                break;
            case 'new_flower_shop':
                if (!isset($post['name']) || !isset($post['address']) || !isset($post['latitude']) || !isset($post['longitude']) || !isset($post['url']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                
                $stmt = $conn->prepare('INSERT INTO shop (id, name, address, latitude, longitude, url) VALUES (?, ?, ?, ?, ?, ?)');
                $id = generateRandomString();
                $stmt->bind_param('sssdds', $id, $post['name'], $post['address'], $post['latitude'], $post['longitude'], $post['url']);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                $result = json_encode(array(
                    'esl' => $url . '?shop_id=' . $id
                ));
                echo $result;
                break;
            case 'bid_awarded':
                if (!isset($post['id']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                
                $stmt = $conn->prepare('UPDATE driver SET status = \'BUSY\', delivery_id = ?');
                $stmt->bind_param('s', $post['id']);
                $stmt->execute();
                $stmt->close();
                
                $stmt = $conn->prepare('
                    SELECT shop.name AS shop_name, 
                            shop.address AS shop_address,
                            delivery.address AS recipient_address, 
                            delivery.distance AS distance,
                            delivery.estimated_time AS estimated_time 
                    FROM delivery 
                    INNER JOIN shop ON delivery.shop_id = shop.id 
                    WHERE delivery.id = ?
                ');
                $stmt->bind_param('s', $post['id']);
                $stmt->execute();
                $delivery = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare('SELECT * FROM driver');
                $stmt->execute();
                $driver = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare('SELECT * FROM oauth');
                $stmt->execute();
                $oauth = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $conn->close();
                
                $text_content = "BID AWARDED\n";
                $text_content .= 'Flower shop: ' . $delivery['shop_name'] . "\n";
                $text_content .= 'Address: ' . $delivery['shop_address'] . "\n";
                $text_content .= 'Recipient address: ' . $delivery['recipient_address'] . "\n";
                $text_content .= 'Distance: ' . $delivery['distance'] . " miles\n";
                $text_content .= 'Estimated time: ' . $delivery['estimated_time'] . " minutes\n";
                $text_content .= 'Text "delivery complete" when finished.';
                
                $curl = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . $oauth['account_sid'] . '/Messages.json');
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, 'From=' . $oauth['phone'] . '&To=' . $driver['phone'] . '=&Body=' . urlencode($text_content));
                curl_setopt($curl, CURLOPT_USERPWD, $oauth['account_sid'] . ':' . $oauth['auth_token']);
                curl_exec($curl);
                curl_close($curl);
                
                echo 'bid awarded';
                break;
        }
    }
?>