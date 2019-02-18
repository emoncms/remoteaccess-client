#!/usr/bin/env python2
import json
import logging
import sys
import time
import ssl
import requests as requests
import paho.mqtt.client as paho
import os
import urllib
import hashlib
from os import path, getenv

_dir = path.dirname(path.abspath(__file__))    
# Load file from the path.
config_path = path.join(_dir, 'remoteaccess.json')
#if path.isfile(config_path) is False:
    #config_path = path.join(_dir, 'remoteaccess.json.example')

config = {}

mqtt = {
    "client": None,
    "retry": 5,
    "delay": 2,
    "counter": 0,
    "connected": False
}

emoncms = {
    "protocol" : "http://",
    "host" : "localhost",
    "port" : "80",
    "path" : "/emoncms/feed/list.json"
}

# ------------------------------------------------------------------------------------------
# Load configuration
# ------------------------------------------------------------------------------------------
def load_configuration():
    global config
    global mqtt 
    
    # Load file from the path.
    if path.isfile(config_path) is False:
        logging.error('config file not found')
        sys.exit(0)
    else:
        with open(config_path) as config_file: 
            _config = json.load(config_file)
    
    config = {
        "mode":_config['APP_ENV'],
        "apikey_write":_config['APIKEY_WRITE'],
        "apikey_read":_config['APIKEY_READ'],
        "access_control":_config['ACCESS_CONTROL']
    }
    
    mqtt["host"] = _config['MQTT_HOST']
    mqtt["username"] = _config['MQTT_USERNAME']
    mqtt["password"] = _config['MQTT_PASSWORD']
    mqtt["port"] = int(_config['MQTT_PORT'])
    mqtt["pubTopic"] = "user/%s/response/" % (_config['MQTT_USERNAME'])
    mqtt["subTopic"] = "user/%s/request" % _config['MQTT_USERNAME']
    mqtt["tls"] = _config['MQTT_TLS']
    mqtt["clientId"] = "%s_python" % _config['MQTT_USERNAME']
    mqtt["transport"] = _config['MQTT_TRANSPORT']

    if config['mode'] == 'production':
        logging.basicConfig(format='%(message)s', level=logging.ERROR)
    else:
        logging.basicConfig(format='%(message)s', level=logging.DEBUG)    

    # display all mqtt settings if APP_ENV == 'development'
    logging.debug("Settings: %s, %s, %s, %s. TLS:%s, %s", mqtt["clientId"], mqtt["host"], mqtt["pubTopic"], mqtt["subTopic"], mqtt["tls"], mqtt["transport"])

# ------------------------------------------------------------------------------------------

def initialize():
    """ init function with exception handling.
    client.loop_forever() keeps looping this code until stop_loop() called

    """
    global mqtt
    try:
        logging.info("Starting EmonCMS RemoteAccess")

        mqtt["client"] = paho.Client(mqtt["clientId"], transport=mqtt["transport"])
        
        mqtt["client"].on_connect    = on_connect
        mqtt["client"].on_message    = on_message
        mqtt["client"].on_publish    = on_publish
        mqtt["client"].on_subscribe  = on_subscribe
        mqtt["client"].on_disconnect = on_disconnect

        logging.debug("Connecting to: %s " % mqtt["host"])
        
        mqtt["client"].enable_logger(logger=logging)
        if mqtt["tls"] == True:
            setTLS()
            
        connect()

    except TypeError as err:
        logging.error('Error creating connection. %s' % err)
        return
    except ValueError as err:
        logging.error("%s: %s" % err, err.args)
        return
    except Exception as inst:
        logging.error("Error: %s. %s", inst.args[0], inst.args[1])
        raise
    except: # catch *all* exceptions
        e = sys.exc_info()[0]
        logging.error("Error: %s" % e)
    finally:
        pass 
        
        return

#####


def on_connect(client, obj, flags, rc):
    logging.debug("on_connect() triggered")

    """ function called when connection is made to mqtt server 

    Attributes:
        client -- the instance of the mqtt client that connected
        obj -- the private user data as set in Client() or user_data_set()
        flags -- response flags sent by the broker (dict) 
        rc -- response code

    """
    global mqtt
    logging.debug(paho.connack_string(rc))
    
    if rc == 0:
        mqtt["connected"] = True
        logging.info("Connected to broker: %s" % mqtt["host"])
        logging.debug("Subscribing to \"%s\"", mqtt["subTopic"])
        client.subscribe(mqtt["subTopic"])  # subscribe


def on_message(client, obj, msg):
    """ function called when message is returned from the server 

    Attributes:
        client -- the instance of the mqtt client that received the message
        obj -- the private user data as set in Client() or user_data_set()
        msg - an instance of MQTTMessage. This is a class with members topic, payload, qos, retain

    """
    logging.debug('onMessage: ' + str(msg.topic) + " " + str(msg.qos) + " " + str(msg.payload))
    message = msg.payload.decode("utf-8")
    # NEED TO ENSURE OTHER CLIENTS SET will TO - payload: 'DISCONNECTED CLIENT ' + CLIENT_ID + '--------',
    if(message.startswith('DISCONNECTED')) :
        logging.info(message)
        # @todo: end client connection
    else:
        call_api(message)
    pass


def on_publish(client, obj, mid):
    """ function called when message is returend from the server 

    Attributes:
        client -- the instance of the mqtt client that published
        obj -- the private user data as set in Client() or user_data_set()
        mid -- message identifier

    """
    logging.debug("Published message: " + str(mid))
    pass


def on_subscribe(client, obj, mid, granted_qos):
    """ function called when the broker has acknowledged the subscription 

    Attributes:
        client -- the instance of the mqtt client that subscribed
        obj -- the private user data as set in Client() or user_data_set()
        mid -- message identifier
        granted_qos -- list of integers that give the QoS level the broker has granted

    """
    # logging.info("Listening for responses from: %s" % host)
    logging.info("Subscribed and responding to messages")
    logging.debug("Subscribed: messageid: %s,  QoS: %s" % (str(mid), str(granted_qos)))


def on_disconnect(client, userdata, rc=0):
    """ function called when the client disconnects from the broker

    Attributes
        client -- the instance of the mqtt client that disconnected
        userdata -- the private user data as set in Client() or user_data_set()
        rc -- the disconnection result. if called by disconnect() rc = 0 MQTT_ERR_SUCCESS

    """
    global mqtt
    mqtt["connected"] = False
    logging.debug("Disconnected from broker")
    

def call_api(msg):
    """ Relay the message payload as an API call on the local emonCMS

    Attributes
        msg -- the response body from the ajax request

    """
    global mqtt

    request = json.loads(msg)
    # merge the default settings with ones passed in the mqtt topic
    data = merge_two_dicts(emoncms, request)
    
    # data['q'] = '' # overwrite modrewrite's "q" parameter
    # only allow these endpoints
    action = request['controller'] + "/" + request['action']

    if not action in config["access_control"]:
        logging.error('action %s not found in whitelist' % action)
        return
                 
    # Build path
    path = "/emoncms"
    if 'controller' in request:
        path += "/"+request['controller']
    if 'action' in request:
        path += "/"+request['action']
    if 'subaction' in request and request['subaction']!="":
        path += "/"+request['subaction']
    
    # Query param and apikey are not allowed    
    if 'q' in request["data"]:
        logging.error('param q not allowed')
        return
    if 'apikey' in request["data"]:
        logging.error('apikey not allowed')
        return
    
    params = {}
    if 'data' in request:
        params = merge_two_dicts(params, request["data"])

    params["apikey"] = config["apikey_read"]    
    if config["access_control"][action]=="write":
        params["apikey"] = config["apikey_write"]

    url_params = '?' + urllib.urlencode(params)
    url = "%s%s:%s%s.json%s" % (data["protocol"], data["host"], data["port"], path, url_params)
    logging.debug("Sending API request %s" % url)
    response = requests.get(url)
    send_response(request, response)


def send_response(request, response):
    """ Forward the API call response (JSON) to another 
    MQTT topic the requesting client is subscibed to.

    @todo: Also wraps the api response in a data property and includes the original
    request message in the response.

    Attributes
        response -- json payload for the mqtt message

    """
    global mqtt
    remote_client_id = request["clientId"]
    logging.debug("Sending API response to: \"%s%s\"" % (mqtt["pubTopic"], remote_client_id))

    json_response = {
        "request": request,
        "result": response.json()
    }
    # publish to topic
    pub_response = mqtt["client"].publish(
        mqtt["pubTopic"] + remote_client_id, json.dumps(json_response)
    )

    logging.debug("PUBLISHED: %s", paho.error_string(pub_response.rc))
    # pub_response.wait_for_publish()


def setTLS(tls_version=None):
    """ Set the SSL options

    """
    global mqtt
    logging.debug('-- SETTING TLS settings')
    mqtt["client"].tls_set(ca_certs="/usr/share/ca-certificates/mozilla/DST_Root_CA_X3.crt")

def connect():
    """ calls the mqtt client connection method 

        counts number of connection attempts
    """
    global mqtt
    if mqtt["host"]!="" and mqtt["username"]!="" and mqtt["password"]!="":
        logging.debug("Connecting to broker...")
        try:
            mqtt["client"].username_pw_set(mqtt["username"], mqtt["password"])
            mqtt["client"].connect(mqtt["host"], mqtt["port"], 60) # connect
        except: # catch *all* exceptions
            e = sys.exc_info()[0]
            logging.error("Error: %s" % e)

def merge_two_dicts(x, y):
    """ return new dict based on x and y being merged
    
    """
    z = x.copy()   # start with x's keys and values
    z.update(y)    # modifies z with y's keys and values & returns None
    return z


""" start the script """
load_configuration()
initialize()

lastconfigcheck = time.time()
current_md5 = hashlib.md5(open(config_path,'rb').read()).hexdigest()

last_retry_connect = time.time()

while True:
    # Check for updated config every 5s
    if (time.time()-lastconfigcheck)>5.0:
        lastconfigcheck = time.time()
        # Get md5 of config file
        last_md5 = current_md5
        current_md5 = hashlib.md5(open(config_path,'rb').read()).hexdigest()
        # If the configuration has changed load the new one
        if last_md5!=current_md5:
            logging.debug("config file changed")
            load_configuration()
            mqtt["client"].disconnect()
            
    # Retry connection
    if mqtt["connected"]==False and (time.time()-last_retry_connect)>10.0:
        last_retry_connect = time.time()
        connect()

    mqtt["client"].loop(0)
    time.sleep(0.01);
