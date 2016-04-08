<?php
    $url = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    
    $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
    
    $stmt = $conn->prepare('SELECT * FROM oauth');
    $stmt->execute();
    $oauth = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $conn->close();
    
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        
        // Initialize session and set URL.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://foursquare.com/oauth2/access_token' . 
            '?client_id=' . $oauth['client_id'] .
            '&client_secret=' . $oauth['client_secret'] .
            '&grant_type=authorization_code' .
            '&redirect_uri=' .
            urlencode($url) . 
            '&code=' . $code);
        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // TODO: CHANGE THIS!
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $json = json_decode($response, true);
        $token = $json['access_token'];
        curl_close($ch);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.foursquare.com/v2/users/self?v=20160407&m=swarm&oauth_token=' . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $json = json_decode($response, true);
        curl_close($ch);
        
        $conn = new mysqli('localhost', 'driver', 'driver', 'driver');

        $stmt = $conn->prepare('UPDATE driver SET name = ?, phone = ?, latitude = ?, longitude = ?, token = ?');
        $name = $json['response']['user']['firstName'] . ' ' . $json['response']['user']['lastName'];
        
        if (isset($json['response']['user']['contact']['phone']))
            $phone = $json['response']['user']['contact']['phone'];
        else
            $phone = '';
        
        if (isset($json['response']['user']['checkins']['items'][0]['venue']['location']['lat']))
            $latitude = $json['response']['user']['checkins']['items'][0]['venue']['location']['lat'];
        else
            $latitude = 40.244444;
        
        if (isset($json['response']['user']['checkins']['items'][0]['venue']['location']['lng']))
            $longitude = $json['response']['user']['checkins']['items'][0]['venue']['location']['lng'];
        else
            $longitude = -111.660833;
        
        $stmt->bind_param('ssdds', $name, $phone, $latitude, $longitude, $token);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();
        
        header('Location: ' . $url);
    }
?>

<html>
    <head>
        <title>Driver</title>
    </head>
    <body>
        <script>
            function savePhone() {
                var xhttp;
                if (window.XMLHttpRequest) {
                    xhttp = new XMLHttpRequest();
                } else {
                    // code for IE6, IE5
                    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
                }
                xhttp.open("POST", "api.php?event=change_phone", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                var data = {phone: document.getElementById("phone").value};
                xhttp.send(JSON.stringify(data));
            }
        </script>
        
        <?php
            $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
            
            $stmt = $conn->prepare('SELECT * FROM driver');
            $stmt->execute();
            $driver = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $conn->close();
            
            if ($driver['name'] !== '') {
                echo 'Name: ' . $driver['name'] . '<br>';
                echo 'Phone: <input id="phone" type="text" value="' . $driver['phone'] . '"><button onClick="savePhone()">Save</button><br><br>';
                echo 'Last location:<br>';
                echo 'Latitude: ' . $driver['latitude'] . '<br>';
                echo 'Longitude: ' . $driver['longitude'] . '<br><br>';
            }
        ?>
        
        <a href="https://foursquare.com/oauth2/authenticate?client_id=<?php echo $oauth['client_id']; ?>&response_type=code&redirect_uri=<?php echo urlencode($url); ?>">
            <?php
                if ($driver['name'] !== '')
                    echo 'Register different user or update current user';
                else
                    echo 'Register user';
            ?>
        </a>
    </body>
</html>