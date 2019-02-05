#!/usr/bin/env python
""" Fake EmonCMS input to a single input
Random number continually posted into local install of EmonCMS via API every 10 seconds.
Settings in ../remoteaccess.env  or ../remoteaccess.env.dev loaded with dotenv library
"""
import requests
import json
import time
import sys
import random
from os import path, getenv
from dotenv import load_dotenv

_dir = path.dirname(path.abspath(__file__))
# Load file from the path.
dotenv_path = path.join(_dir, 'remoteaccess.env.dev')
if path.isfile(dotenv_path) is False:
    # Load local dev version if exists
    dotenv_path = path.join(_dir, 'remoteaccess.env')

if path.isfile(dotenv_path) is False:
    print('.env file not found')
    sys.exit(0)
else:
    load_dotenv(dotenv_path)

# SETTINGS

# requires write api key in .env settings
apikey = getenv('EMONCMS_APIKEY')
# change this if you've installed emoncms in a different location
base_url = "http://localhost/emoncms/input/post"

def get_data(url):
    """ return the response code and response text from http request """
    response = requests.get(url)
    print(url)
    return "status: %s %s" % (response.status_code, response.text)

# never ending loop with 10 second interval
while True:
    # random integer between 1 and 100
    value = random.randint(1,101)
    # add url parameters to url
    url = "%s?node=emontx&json={power1:%s}&apikey=%s" % (base_url, value, apikey)
    # request http response
    response = get_data(url)
    # output response from http request
    print("%s - posted: %s to input \"%s\"" % (response, value, 'emontx:power1'))
    # wait 10s before iterating
    time.sleep(10)