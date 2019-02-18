# Emoncms Remote Access (Alpha)

Background discussion: [https://community.openenergymonitor.org/t/emoncms-local-vs-remote/7268](https://community.openenergymonitor.org/t/emoncms-local-vs-remote/7268)

## Login on mqtt.emoncms.org

Login on mqtt.emoncms.org with your emoncms.org username and password to register for the remote access service.

https://mqtt.emoncms.org

## Client Installation

Create remoteaccess.env settings file with emoncms.org username and password.

    cd ~/ 
    git clone https://github.com/emoncms/remoteaccess-client
    cd remoteaccess-client
    cp remoteaccess.json.example remoteaccess.json
    sudo chmod 666 remoteaccess.json

Install and start remoteaccess service:

    sudo ln -s /home/pi/remoteaccess-client/remoteaccess.service /lib/systemd/system
    sudo systemctl enable remoteaccess.service
    sudo systemctl start remoteaccess
    
Optional: View service log:

    journalctl -f -u remoteaccess -n 100

## Install Client Emoncms Module

    ln -s /home/pi/remoteaccess-client/remoteaccess /var/www/emoncms/Modules/remoteaccess
