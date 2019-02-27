<?php
    
    $menu['sidebar']['setup'][] = array(
        'text' => _("RemoteAccess"),
        'title' => _("Remote Access"),
        'path' => 'remoteaccess/view',
        'icon' => 'earth'
    );

    $menu['sidebar']['remoteaccess'][] = array(
        'text' => '1. '._("Connect"),
        'path' => 'remoteaccess/view/1',
        'order' => 1
    );
    $menu['sidebar']['remoteaccess'][] = array(
        'text' => '2. '._("Access Control"),
        'path' => 'remoteaccess/view/2',
        'order' => 2
    );