<?php
    $url = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    $max_miles = 5;
    $mph = 30;
    $post = json_decode(file_get_contents('php://input'), true);
    
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
                
                $distance_to_shop = distance(floatval($shop['latitude']), floatval($shop['longitude']), floatval($driver['latitude']), floatval($driver['longitude']));
                $distance_to_recipient = distance(floatval($shop['latitude']), floatval($shop['longitude']), floatval($post['latitude']), floatval($post['longitude']));
                $distance = $distance_to_shop + $distance_to_recipient;
                $estimated_time = round($distance * 60 / $mph, 2);
                
                $text_content = 'Flower shop: ' . $shop['name'] . "\n";
                $text_content .= 'Address: ' . $shop['address'] . "\n";
                $text_content .= 'Recipient address: ' . $post['address'] . "\n";
                $text_content .= 'Distance: ' . round($distance, 2) . " miles\n";
                $text_content .= 'Estimated time: ' . $estimated_time . " minutes\n";
                
                if ($distance_to_shop <= $max_miles) {
                    $content = json_encode(array(
                        'event' => 'bid_available',
                        'driver_name' => $driver['name'],
                        'estimated_time' => $estimated_time
                    ));
                    $curl = curl_init($shop['url']);
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
                    $text_content .= 'Make bid anyway?';
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
                    'esl' => $url . '?event=delivery_ready&shop_id=' . $id
                ));
                echo $result;
                break;
            case 'bid_awarded':
                echo 'new flower shop';
                break;
        }
    }
?>