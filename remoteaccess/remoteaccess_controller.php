<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function remoteaccess_controller() {

    global $session, $route, $mysqli;
    
    // Default route format
    $route->format = 'json';
    
    // Result can be passed back at the end or part way in the controller
    $result = false;
    
    require "Modules/remoteaccess/remoteaccess_model.php";
    $remoteaccess = new RemoteAccess($mysqli);
    
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
            
            $result = json_decode(http_request("POST","https://".$host."/user/auth.json",array(
                "username"=>post("username"),
                "password"=>post("password")
            )));
            
            if (isset($result->success) && $result->success) {
                
            }
            
            return $result;
        }
    }

    // Pass back result
    return array('content' => $result);
}
