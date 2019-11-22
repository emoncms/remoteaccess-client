<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function remoteaccess_controller() {

    global $session, $route, $linked_modules_dir, $user;
    
    $config_file = $linked_modules_dir."/remoteaccess-client/remoteaccess.json";
    
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
            
            $config = new stdClass();
            if (file_exists($config_file)) {
                $config = json_decode(file_get_contents($config_file));
            } else {
                $config = json_decode(file_get_contents("$config_file.example"));
            }
            return view("Modules/remoteaccess/remoteaccess_view.php", array("config"=>$config));
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
                $config = new stdClass();
                if (file_exists($config_file)) {
                    $config = json_decode(file_get_contents($config_file));
                } else {
                    $config = json_decode(file_get_contents("$config_file.example"));
                }
            
                if ($config!=null) {
                    $u = $user->get($session["userid"]);
                    $config->APIKEY_WRITE = $u->apikey_write;
                    $config->APIKEY_READ = $u->apikey_read;
                    $config->MQTT_HOST = $host;
                    $config->MQTT_USERNAME = $username;
                    $config->MQTT_PASSWORD = $password;
                    $filepath = $linked_modules_dir."/remoteaccess-client/remoteaccess.json";
                    
                    // fopen throws warnings. use this error handler to catch warnings:
                    set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
                        // create a new error exception to allow it to be caught in the try/catch statements
                        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                    });
                    try {
                        $fh = fopen($filepath,"w");
                        fwrite($fh, json_encode($config, JSON_PRETTY_PRINT));
                        fclose($fh);

                    } catch (ErrorException $e) {
                        return array('success'=>false, 'message'=> $e->getMessage());
                    }
                    // put standard error handler back in place.
                    restore_error_handler();
                }
            }
            return $result;
        }

        if ($route->action == 'saveaccesscontrol') {
            $route->format = "json";
            $accesscontrol = json_decode(post("accesscontrol"));
            
            if ($accesscontrol==null) {
                 return array("success"=>false,"message"=>"Invalid JSON");
            }
            
            if (file_exists($config_file)) {
                $config = json_decode(file_get_contents($config_file));
            } else {
                $config = json_decode(file_get_contents("$config_file.example"));
            }
            
            $config->ACCESS_CONTROL = new stdClass(); 
            foreach ($accesscontrol as $key=>$val) {
                if (preg_replace('/[^\p{N}\p{L}\/_-]/u','',$key)!=$key) return array('success'=>false, 'message'=>'invalid characters in path');
            
                if ($val=="read" || $val=="write") {
                    $config->ACCESS_CONTROL->$key = $val;
                }
            }
            
            $fh = fopen($linked_modules_dir."/remoteaccess-client/remoteaccess.json","w");
            fwrite($fh,json_encode($config, JSON_PRETTY_PRINT));
            fclose($fh);
            
            return array("success"=>true,"message"=>"Access control saved");
        }     
    }

    // Pass back result
    return array('content' => $result);
}
