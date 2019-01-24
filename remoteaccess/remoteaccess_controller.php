<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function remoteaccess_controller() {

    global $session, $route, $homedir, $user;
    
    // Default route format
    $route->format = 'json';
    
    // Result can be passed back at the end or part way in the controller
    $result = false;
    
    require "Modules/remoteaccess/remoteaccess_model.php";
    $remoteaccess = new RemoteAccess();
    
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
                $env = $remoteaccess->load_env($homedir."/remoteaccess-client/remoteaccess.env");
                if ($env) {
                    $u = $user->get($session["userid"]);
                    $env["EMONCMS_APIKEY"] = $u->apikey_read;
                    $env["MQTT_HOST"] = $host;
                    $env["MQTT_USERNAME"] = $username;
                    $env["MQTT_PASSWORD"] = $password;
                    $remoteaccess->save_env($homedir."/remoteaccess-client/remoteaccess.env",$env);
                }
            }
            return $result;
        }   
    }

    // Pass back result
    return array('content' => $result);
}
