<?php
    $max_miles = 5;
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
    
    if (isset($_GET['event'])) {
        switch ($_GET['event']) {
            case 'foursquare_registration':
                if (!isset($post['client_id']) || !isset($post['client_secret']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('UPDATE oauth SET client_id = ?, client_secret = ?');
                $stmt->bind_param('ss', $post['client_id'], $post['client_secret']);
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
                if (!isset($post['url']))
                    break;
                
                $url = $post['url'];
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

                $stmt = $conn->prepare('SELECT * FROM shop WHERE url = ?');
                $stmt->bind_param('s', $url);
                $stmt->execute();
                $shop = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare('SELECT * FROM driver');
                $stmt->execute();
                $driver = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $conn->close();
                
                $content = json_encode(array(
                    'event' => 'bid_available',
                    'driver_name' => 'Josh',
                    'estimated_time' => distance(floatval($shop['latitude']), floatval($shop['longitude']), floatval($driver['latitude']), floatval($driver['longitude']))
                ));
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
                curl_exec($curl);
                curl_close($curl);
                
                echo 'delivery is ready';
                break;
            case 'new_flower_shop':
                if (!isset($post['name']) || !isset($post['latitude']) || !isset($post['longitude']) || !isset($post['url']))
                    break;
                
                $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
                
                $stmt = $conn->prepare('INSERT INTO shop (name, latitude, longitude, url) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('sdds', $post['name'], $post['latitude'], $post['longitude'], $post['url']);
                $stmt->execute();
                $stmt->close();
                
                $conn->close();
                
                echo 'new flower shop';
                break;
            case 'bid_awarded':
                echo 'new flower shop';
                break;
        }
    }
?>