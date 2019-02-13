<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function remoteaccess_controller() {

    global $session, $route, $homedir, $user;
    
    // Default route format
    $route->format = 'json';
    
    // Result can be passed back at the end or part way in the controller
    $result = false;
    
    // Read access API's and pages
    if ($session['read']) {
    
    }
    
    // Write access API's and pages
    if ($session['write']) {
        if ($route->action == "view") {
            $route->format = 'html';
            return view("Modules/remoteaccess/remoteaccess_view.php", array());
        }
        
    
        if ($route->action == 'connect') {
            $route->format = "json";
            $host = post("host");
            $username = post("username");
            $password = post("password");
            
            $result = json_decode(http_request("POST","https://".$host."/user/auth.json",array(
                "username"=>$username,
                "password"=>$password
            )));
            
            if (isset($result->success) && $result->success) {
                $config = json_decode(file_get_contents($homedir."/remoteaccess-client/remoteaccess.json"));
            
                if ($config!=null) {
                    $u = $user->get($session["userid"]);
                    $config["EMONCMS_APIKEY"] = $u->apikey_read;
                    $config["MQTT_HOST"] = $host;
                    $config["MQTT_USERNAME"] = $username;
                    $config["MQTT_PASSWORD"] = $password;
                    $fh = fopen($homedir."/remoteaccess-client/remoteaccess.json","w");
                    fwrite($fh,json_encode($config, JSON_PRETTY_PRINT));
                    fclose($fh);
                }
            }
            return $result;
        }   
    }

    // Pass back result
    return array('content' => $result);
}
