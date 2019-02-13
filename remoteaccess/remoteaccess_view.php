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

    <h2>Emoncms Remote Access</h2>
    <p>Access your local emoncms installation remotely</p>
    
    <div style="background-color:#eee; padding:20px; max-width:600px">
        <p style="color:#666">Enter host, username and password of remote account</p>
        <label>Host (default: mqtt.emoncms.org):</label>
        <input type="text" id="host" value="emoncms.org">
        
        <label>Username:</label>
        <input type="text" id="username">
        
        <label>Password:</label>
        <input type="password" id="password">
        <br>
        <button id="connect" class="btn">Verify & Save</button>
        
        <div id="success" class="alert alert-success hide" style="margin-top:20px"><b>Success:</b> Authentication verified & details saved</div>
        <div id="error" class="alert alert-error hide" style="margin-top:20px"></div>
    </div>

    <div id="available-apps"></div>

  </div>
</div>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/sidebar.js?v=<?php echo $version; ?>"></script>

<script>
init_sidebar({menu_element:"#remoteaccess_menu"});
var path = "<?php echo $path; ?>";
var config = <?php echo json_encode($config); ?>;

$("#host").val(config.MQTT_HOST);
$("#username").val(config.MQTT_USERNAME);
$("#password").val(config.MQTT_PASSWORD);

$("#connect").click(function() {
    var host = $("#host").val();
    var username = $("#username").val();
    var password = $("#password").val();
    
    $.ajax({ type: "POST", url: path+"remoteaccess/connect", data: "host="+host+"&username="+username+"&password="+password, async: false, success: function(result){ 
        if (result.success!=undefined && result.success) {
            $("#success").show();
            $("#error").hide();
        } else {
            $("#error").html("<b>Error:</b> "+result.message);
            $("#error").show();
            $("#success").hide();
        }
    }});
});
</script>
