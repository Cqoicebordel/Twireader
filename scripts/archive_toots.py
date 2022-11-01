#!/usr/bin/python3
# -*- coding: utf-8 -*-

import sqlite3 as lite
import pytz
import os
import sys
import pycurl
import re
import locale
import time
import html
import base64
import io
#import urllib2
import urllib.request, urllib.error, urllib.parse
import urllib3
import requests
import unicodedata
import traceback
from functools import partial
from json import loads
from collections import OrderedDict
from datetime import datetime
from pprint import pprint

# Twitter parameters.
mastodon_me = 'name'
mastodon_bearer = ''

# Local file parameters.
mastodon_url = 'https://mastodon.social/'
mastodon_apiTimeline_url = 'api/v1/timelines/home?since_id='
mastodon_apiNotifications_url = 'api/v1/notifications?since_id='
mastodon_apiMarkers_url = 'api/v1/markers?timeline[]=notifications'
mastodon_apiToot_url = 'api/v1/statuses/'
# Reuse the same folder
tweetdir = os.environ['HOME'] + '/twitter/'
# Store the number of unread notifications
mastodonfile = tweetdir + 'mastodon.txt'
# Store the last toot fetched ID
mastodon_idfile = tweetdir + 'mastodon_lastID.txt'

database = '/var/www/twireader/base.sqlite'

locale.setlocale(locale.LC_ALL, 'fr_FR.utf8')

# Date/time parameters.
datefmt = '%-d %B %Y &agrave; %H:%M:%S'
datefmtiso = '%Y-%m-%d %H:%M:%S'
homeTZ = pytz.timezone('Europe/Paris')
utc = pytz.utc

urllib3.disable_warnings()


'''
    Here the code is copy pasted from the Twitter script, so some things might be weird, but if you have read the other script first, you'll understand why.
    The working is mostly the same as on the other script : get the last ID fetched, fetch all starting from that ID, store the last ID.

'''


def emoji(matches):
    match = matches.group()
    try:
        return "<abbr class=\"emoji\" title=\""+unicodedata.name(match)+"\">"+match+"</abbr>"
    except:
        """
        try:
            print("Problème Emoji ", ord(match))
        except:
            pass
            """
        return "<abbr class=\"emoji\" >"+match+"</abbr>"

def getInstagram(matches):
    if matches:
        insta_url = matches.group(1)
        response = requests.Response()
        try:
            response = requests.get(insta_url + 'media/?size=l', allow_redirects=True, stream=True, headers = {
                'User-Agent': '12.02 (X11; Linux i686; Opera Cqcb Style; U; fr-FR) Presto/2.9.201 Version/12.02/AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu',
                'authority': 'scontent-cdg2-1.cdninstagram.com',
                'pragma': 'no-cache',
                'cache-control': 'no-cache',
                'sec-ch-ua': '"Chromium";v="97", " Not;A Brand";v="99"',
                'sec-ch-ua-mobile': '?0',
                'sec-ch-ua-platform': '"Linux"',
                'dnt': '1',
                'upgrade-insecure-requests': '1',
                'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.20 Safari/537.36',
                'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site': 'none',
                'sec-fetch-mode': 'navigate',
                'sec-fetch-user': '?1',
                'sec-fetch-dest': 'document',
                'accept-language': 'fr,en-US;q=0.9,en;q=0.8'
                    }, verify=False, timeout=60)
        except Exception as ex:
            print("Except requests", ex)
            pass
        if (response.status_code == 200) or (response.status_code == 400):
            #elongatedInsta = response.url
            return 'data:image/jpg;base64,'+str(base64.b64encode(response.content))
        
        return insta_url + 'media/?size=l'



def getConversation(id, idParent):
    headers = {"Authorization": "Bearer YGbPAciYDGIutPf6DaZZLo73s2wl766QUAGE-9tEsx0"}
    response = requests.get(mastodon_url+mastodon_apiToot_url+id, allow_redirects=True, headers=headers, verify=False, timeout=60)
    t = loads(response.text)
    #print t
    if t['reblog'] is not None:
        textElongated = 'RT <a href="'+t['reblog']['account']['url']+'">@'+t['reblog']['account']['acct']+'</a>: '+ t['reblog']['content']
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t['account']['avatar'] + "," + t['reblog']['account']['avatar']
    else:
        textElongated = t['content']
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t['account']['avatar']

    #print(textElongated)
    images = ""
    medias = ""
    image_first = True
    media_first = True
    try:
        for media in t.setdefault("media_attachments", []):
            if media['type'] == "image":
                if image_first:
                    images += (media['remote_url'] or media['url'])
                    image_first = False
                else:
                    images += "," + (media['remote_url'] or media['url'])
            elif media['type'] == "video":
                if media_first:
                    medias += (media['remote_url'] or media['url'])
                    media_first = False
                else:
                    medias += "," + (media['remote_url'] or media['url'])
                
        if t['reblog'] is not None:
            for media in t['reblog'].setdefault("media_attachments", []):
                if media['type'] == "image":
                    if image_first:
                        images += (media['remote_url'] or media['url'])
                        image_first = False
                    else:
                        images += "," + (media['remote_url'] or media['url'])
                elif media['type'] == "video":
                    if media_first:
                        medias += (media['remote_url'] or media['url'])
                        media_first = False
                    else:
                        medias += "," + (media['remote_url'] or media['url'])
    except (IndexError,AttributeError) as e:
        #images = ""
        #first = True
        print("Error media l169")
        traceback.print_exc()
        print(e)
        pass


    insta = ""
    insta = getInstagram(regex4.search(textElongated2, re.MULTILINE))

    if insta and insta != "" and insta is not textElongated2:
        if first:
            images += insta
        else:
            images += "," + insta


    images = ",".join(OrderedDict.fromkeys(images.split(',')))
    
    medias = ",".join(OrderedDict.fromkeys(medias.split(',')))
    datetimeToot = datetime.strptime(t['created_at'][:-1]+"000Z", '%Y-%m-%dT%H:%M:%S.%fZ')
    ts = utc.localize(datetimeToot).astimezone(homeTZ)

    sql = "INSERT INTO mastodon_discussion VALUES(?,?,?,?,?,?,?,?,?,?,?,?)"
    params = (images, t['id'], idParent, pp, textElongated2, t['account']['acct'], t['account']['display_name'], ts.strftime(datefmt), t['uri'], medias, ts.strftime(datefmtiso), t['account']['url'])
    with con:
        cur = con.cursor()
        try:
            cur.execute(sql, params);
        except lite.IntegrityError as er:
            pass
    if t['reblog'] is not None:
        if t['reblog']['in_reply_to_id'] is not None:
            getConversation(t['reblog']['in_reply_to_id'], idParent)
    else:
        if t['in_reply_to_id'] is not None:
            getConversation(t['in_reply_to_id'], idParent)



# Get the ID of the last downloaded tweet.
with open(mastodon_idfile, 'r') as f:
    mastodon_lastID = f.read().rstrip()

# Collect all the tweets since the last one.
headers = {"Authorization": "Bearer YGbPAciYDGIutPf6DaZZLo73s2wl766QUAGE-9tEsx0"}
response = requests.get(mastodon_url+mastodon_apiTimeline_url+mastodon_lastID+"&limit=40", allow_redirects=True, headers=headers, verify=False, timeout=60)
toots = loads(response.text)
con = lite.connect(database)
con.text_factory = str

regex = re.compile(r"(http|ftp|scp)(s)?://[-=a-zA-Z0-9.?&_/]+(?<!\.)")
regex2 = re.compile(u"([0-9|#][\u20E3])|[\u00AE|\u00A9|\u203C|\u2047|\u2048|\u2049|\u3030|\u303D|\u2139|\u2122|\u3297|\u3299][\uFE00-\uFEFF]?|[\u2190-\u21FF][\uFE00-\uFEFF]?|[\u2300-\u23FF][\uFE00-\uFEFF]?|[\u2460-\u24FF][\uFE00-\uFEFF]?|[\u25A0-\u25FF][\uFE00-\uFEFF]?|[\u2600-\u27BF][\uFE00-\uFEFF]?|[\u2900-\u297F][\uFE00-\uFEFF]?|[\u2B00-\u2BF0][\uFE00-\uFEFF]?|[\U0001F000-\U0001FFFF][\uFE00-\uFEFF]?", re.UNICODE)
regex3 = re.compile(r"https://twitter.com/\w+/status/(\d+)(\?.*)?\<\/a\>")
regex4 = re.compile(r"(https://www.instagram.com/p/[-a-zA-Z0-9_]+/).*\<\/a\>$")


for t in reversed(toots):
    #print(t['id'])
    if t['reblog'] is not None:
        textElongated = '<span class="rt">RT <a href="'+t['reblog']['account']['url']+'" class="handle">@'+t['reblog']['account']['acct']+'</a>:</span> '+ t['reblog']['content']
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t['account']['avatar'] + "," + t['reblog']['account']['avatar']
        if t['reblog']['in_reply_to_id'] is not None:
            getConversation(t['reblog']['in_reply_to_id'], t['id'])
        datetimeRBToot = datetime.strptime(t['reblog']['created_at'][:-1]+"000Z", '%Y-%m-%dT%H:%M:%S.%fZ')
        tsrt = utc.localize(datetimeRBToot).astimezone(homeTZ)
        try:
            textElongated2 = textElongated2 + "<p class='date'><a href='" + t['reblog']['uri'] + "'>" + tsrt.strftime(datefmt) + "</a></p>"
        except UnicodeDecodeError as e:
            print(e)
            print(type(textElongated2))
            print(type(t['reblog']['uri']))
            print(type(t['reblog']['in_reply_to_id']))
            print(type(t['reblog']))
            print(type(tsrt.strftime(datefmt)))
    else:
        textElongated = t['content']
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t['account']['avatar']
        if t['in_reply_to_id'] is not None:
            getConversation(t['in_reply_to_id'], t['id'])

    #print(textElongated)
    images = ""
    medias = ""
    image_first = True
    media_first = True
    #pprint(t.setdefault("media_attachments", []))
    try:
        for media in t.setdefault("media_attachments", []):
            if media['type'] == "image":
                if image_first:
                    images += (media['remote_url'] or media['url'])
                    image_first = False
                else:
                    images += "," + (media['remote_url'] or media['url'])
            elif media['type'] == "video" or media['type'] == "gifv":
                if media_first:
                    medias += (media['remote_url'] or media['url'])
                    media_first = False
                else:
                    medias += "," + (media['remote_url'] or media['url'])
                
        if t['reblog'] is not None:
            for media in t['reblog'].setdefault("media_attachments", []):
                if media['type'] == "image":
                    if image_first:
                        images += (media['remote_url'] or media['url'])
                        image_first = False
                    else:
                        images += "," + (media['remote_url'] or media['url'])
                elif media['type'] == "video" or media['type'] == "gifv":
                    if media_first:
                        medias += (media['remote_url'] or media['url'])
                        media_first = False
                    else:
                        medias += "," + (media['remote_url'] or media['url'])
    except (IndexError, AttributeError, TypeError) as e:
        #images = ""
        #first = True
        print("Error media l288")
        traceback.print_exc()
        print(e)
        pprint(t)
        pass

    insta = ""
    insta = getInstagram(regex4.search(textElongated2, re.MULTILINE))

    if insta and insta != "" and insta is not textElongated2:
        if first:
            images += insta
        else:
            images += "," + insta


    images = ",".join(OrderedDict.fromkeys(images.split(',')))
    
    medias = ",".join(OrderedDict.fromkeys(medias.split(',')))
    datetimeToot = datetime.strptime(t['created_at'][:-1]+"000Z", '%Y-%m-%dT%H:%M:%S.%fZ')
    ts = utc.localize(datetimeToot).astimezone(homeTZ)
    
    if t['visibility'] == "private":
        url = mastodon_url + "web/@" + t['account']['acct'] + "/" + t['id']
    else:
        url = t['uri']

    sql = "INSERT INTO mastodon_feed VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)"
    params = (0, pp, images, "", t['id'] , textElongated2 , t['account']['acct'], t['account']['display_name'], ts.strftime(datefmt), url, medias, ts.strftime(datefmtiso), t['account']['url'])
    with con:
        cur = con.cursor()
        try:
            cur.execute(sql, params);
        except lite.IntegrityError as er:
            #print("SQLite3")
            #print(er)
            #print(er.args)
            pass
            #Rien
    mastodon_lastID = t['id']


# Update the ID of the last downloaded tweet.
with open(mastodon_idfile, 'w') as f:
    mastodon_lastID = f.write(mastodon_lastID)

# Get the number of unread notification. Not used in the scope of this project (yet), but useful nonetheless
with open(mastodonfile, 'w') as f:
    response = requests.get(mastodon_url+mastodon_apiMarkers_url, allow_redirects=True, headers=headers, verify=False, timeout=60)
    marker = loads(response.text)
    response = requests.get(mastodon_url+mastodon_apiNotifications_url+marker["notifications"]["last_read_id"], allow_redirects=True, headers=headers, verify=False, timeout=60)
    f.write(str(len(loads(response.text))))
