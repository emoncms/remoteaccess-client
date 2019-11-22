<ul class="nav nav-tabs mb-0 mt-3" id="nav-tabs">
    <li class="active"><a href="#view-remoteauth">1. Remote Auth</a></li>
    <li><a href="#view-accesscontrol">2. Access Control</a></li>
</ul>


<div class="tab-content">

    <h2>Emoncms Remote Access</h2>
    <p>Access your local emoncms installation remotely</p>

    <div id="view-remoteauth" style="background-color:#eee; padding:20px; max-width:600px" class="tab-pane active">
        <h4>Remote Auth</h4>
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

    <div id="view-accesscontrol" style="background-color:#eee; padding:20px; max-width:600px" class="tab-pane">
        <h4>Access Control</h4>
        <p style="color:#666">List of allowed API end points and access level.</p>
        <table class="table" style="margin-top:20px">
            <tr><th>Path</th><th>Access</th><th></th></tr>
            <tbody id="access_control"></tbody>
        </table>
        <p style="color:#666">Add end point:</p>
        <div class="input-prepend input-append">
            <div class="add-on">Path</div>
            <input id="add_endpoint_path" type="text">
            <select id="add_endpoint_accesslevel" style="width:80px; cursor:pointer"><option>read</option><option>write</option></select>
            <div id="add_endpoint" class="btn">Add</div>
        </div>
    </div>

</div>

<script>
init_sidebar({menu_element:"#remoteaccess_menu"});
var config = <?php echo json_encode($config); ?>;

$("#host").val(config.MQTT_HOST);
$("#username").val(config.MQTT_USERNAME);
$("#password").val(config.MQTT_PASSWORD);

draw_access_control();

$("#connect").click(function() {
    var button = $(this)[0];
    var $error = $("#error");
    var $success = $("#success");
    $success.hide();
    $error.hide();

    button.disabled = true;
    var data = {
        host: $("#host").val(),
        username: $("#username").val(),
        password: $("#password").val(),
    }
    $.post(path+"remoteaccess/connect", data, function(result){
        if (result && result.success!=undefined && result.success) {
            $success.show();
        } else {
            $error.html("<strong>Error:</strong> " + (result ? result.message : 'Host returned "' + result + '"'));
            $error.show();
        }
    }, 'json')
    .fail(function(xhr, error, message) {
        $error.html("<strong>Host Error:</strong> "+error);
        $error.show();
    })
    .always(function() {
        button.disabled = false;
    })
});

$("#access_control").on("click",".delete",function(){
    var path = $(this).attr("path");
    delete config.ACCESS_CONTROL[path];
    draw_access_control();
    save_access_control();
});

$("#access_control").on("click",".accesslevel",function(){
    var path = $(this).attr("path");
    if (config.ACCESS_CONTROL[path]=="read") {
        config.ACCESS_CONTROL[path] = "write";
    } else {
        config.ACCESS_CONTROL[path] = "read";
    }
    draw_access_control();
    save_access_control();
});

$("#add_endpoint").click(function() {
    var path = $("#add_endpoint_path").val();
    var accesslevel = $("#add_endpoint_accesslevel").val();
    
    if (config.ACCESS_CONTROL[path]==undefined) {
        config.ACCESS_CONTROL[path] = accesslevel;
    } else {
        alert("end point path already exists");
    }
    draw_access_control();
    save_access_control();
});

function draw_access_control() {
    var out = "";
    for (var path in config.ACCESS_CONTROL) {
        var colour = "warning";
        if (config.ACCESS_CONTROL[path]=="write") colour = "important";

        out += "<tr>";
        out += "<td>"+path+"</td>";
        out += "<td><span class='accesslevel label label-"+colour+"' style='cursor:pointer' path='"+path+"'>"+config.ACCESS_CONTROL[path]+"</span></td>";
        out += "<td><i class='delete icon-trash' style='cursor:pointer' path='"+path+"'></i></td>";
        out += "</tr>";
    }
    $("#access_control").html(out);
}

function save_access_control() {
    $.ajax({ type: "POST", url: path+"remoteaccess/saveaccesscontrol", data: "accesscontrol="+JSON.stringify(config.ACCESS_CONTROL), async: false, success: function(result){ 
        if (result.success!=undefined && result.success) {

        } else {
            alert(result.message);
        }
    }});
}


$(function () {
    // trigger tab open on click (adding hash to location)
    $('#nav-tabs a').click(function (e) {
        e.preventDefault();
        var href = $(e.target).attr('href');
        selectTab(href.replace('view-',''));
        // show tab
        $(this).tab('show');
        // change hash
        location.hash = href.replace('view-','');
    })
    // pre-select tab on load
    // @todo: fix slight delay from ajax calls
    selectTab();

    // on hash change
    $(window).on('hashchange', function(event) {
        selectTab(location.hash);
    })
})
/**
 * loop through all tabs and highlight one if given [hash] is a match
 */
function selectTab(hash) {
    hash = hash || location.hash;

    $.each($('#backup-tabs a'), function(i,elem) {
        var $tab = $(elem);
        if($tab.attr('href') == hash.replace('#','#view-')) {
            $tab.tab('show');
        }
    });
}
</script>
