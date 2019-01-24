# Emoncms Remote Access (Alpha)

This concept is still work in progress. Expect buggy results for now.

Background discussion: [https://community.openenergymonitor.org/t/emoncms-local-vs-remote/7268](https://community.openenergymonitor.org/t/emoncms-local-vs-remote/7268)

## Login on mqtt.emoncms.org

Login on mqtt.emoncms.org with your emoncms.org username and password to register for the remote access service.

https://mqtt.emoncms.org

## Client Installation

Create remoteaccess.env settings file with emoncms.org username and password.

    git clone https://github.com/emoncms/remoteaccess-client
    cd remoteaccess-client
    cp remoteaccess.env.example .env
    nano remoteaccess.env

Enter your local emoncms apikey and remote emoncms.org account username and password to connect:

    # application mode
    APP_ENV='production'
    # emoncms api write key
    EMONCMS_APIKEY='apikey'
    # mqtt broker connection
    MQTT_HOST='mqtt.emoncms.org'
    MQTT_USERNAME='username'
    MQTT_PASSWORD='password'
    MQTT_PORT=8883
    MQTT_TLS=true

    # Save As remoteaccess.env for production
    # Save As remoteaccess.env.dev for development

Install python dependencies:

    pip install python-dotenv

Install and start remoteaccess service:

    sudo ln -s /home/pi/remoteaccess-client/remoteaccess.service /lib/systemd/system
    sudo systemctl enable remoteaccess.service
    sudo systemctl start remoteaccess
    
View service log:

    journalctl -f -u remoteaccess -n 100
