<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class RemoteAccess {

    public function __construct() {
    }

    public function load_env($filename) 
    {
        if (!file_exists($filename)) return false;
    
        $env = file_get_contents($filename);
        $lines = explode("\n",$env);
        $out = "";
        foreach ($lines as $line) {
            if (isset($line[0]) && $line[0]!="#") {
                $keyval = explode("=",$line);
                if (count($keyval)==2) {
                    $key = $keyval[0];
                    $value = str_replace("'","",$keyval[1]);
                    $settings[$key] = $value;
                }
            }
        }
        return $settings;
    }
    
    public function save_env($filename,$env) 
    {
        if (!file_exists($filename)) return false;
        
        $out = "";
        foreach ($env as $key=>$val) {
            $out .= $key."=";
            if (is_numeric($val)) {
                $out .= $val;
            } else if ($val=='true') {
                $out .= $val;
            } else if ($val=='false') {
                $out .= $val;
            } else {
                $out .= "'".$val."'";
            }
            $out .= "\n";
        }
        if (!$fh = fopen($filename,"w")) return false;
        fwrite($fh,$out);
        fclose($fh);
    }

}
