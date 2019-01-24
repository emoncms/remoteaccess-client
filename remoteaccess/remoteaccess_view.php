<?php 
    global $path; 
    $version = 1;
?>

<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/sidebar.css?v=<?php echo $version; ?>">

<div id="wrapper">
  <div class="sidenav">
    <div class="sidenav-inner">
      
      <ul class="sidenav-menu">
          <li><a href="<?php echo $path; ?>remoteaccess/view/1">1. Connect</a></li>
          <li><a href="<?php echo $path; ?>remoteaccess/view/2">2. Access Control</a></li>
      </ul>
      
    </div>
  </div>

  <div style="height:10px"></div>

  <div style="padding:20px">

    <h2>Emoncms RemoteAccess</h2>
    <p>Access your local emoncms installation remotely</p>
    
    <div style="background-color:#eee; padding:20px; max-width:600px">
        <p style="color:#666">Enter username and password to fetch remote account apikey.</p>
        <label>Host (default: mqtt.emoncms.org):</label>
        <input type="text" id="host" value="emoncms.org">
        
        <label>Username:</label>
        <input type="text" id="username">
        
        <label>Password:</label>
        <input type="text" id="password">
        <br>
        <button id="connect" class="btn">Connect</button>
        <br><br>
        <label>Current emonhub.conf apikey:</label>
        <input type="text" id="apikey" style="width:400px" readonly>
    </div>

    <div id="available-apps"></div>

  </div>
</div>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/sidebar.js?v=<?php echo $version; ?>"></script>

<script>
init_sidebar({menu_element:"#remoteaccess_menu"});
var path = "<?php echo $path; ?>";

$("#connect").click(function() {
    var host = $("#host").val();
    var username = $("#username").val();
    var password = $("#password").val();
    
    $.ajax({ type: "POST", url: path+"remoteaccess/connect", data: "host="+host+"&username="+username+"&password="+password, async: false, success: function(result){ 
        if (result.success!=undefined && result.success) {
            $("#apikey").val(result.apikey_write);
            $("#apikey").css("background-color","rgba(0,255,0,0.3)");
        } else {
            alert("Emoncms account does not exist");
        }
    }});
});
</script>
