#!/usr/bin/python3
# -*- coding: utf-8 -*-

import sqlite3 as lite
import tweepy
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
from functools import partial
from json import loads
from collections import OrderedDict

# Twitter parameters.
me = 'Name'
consumerKey = ''
consumerKeySecret = ''
accessToken = ''
accessTokenSecret = ''


# Local file parameters.
url = 'https://twitter.com/'
urlprefix = 'https://twitter.com/%s/status/' % me
tweetdir = os.environ['HOME'] + '/twitter/'
# Store the last ID fetched
idfile = tweetdir + 'lastID.txt'
badgeFile = '/var/www/twitter/badges.txt'

database = '/var/www/twireader/base.sqlite'

locale.setlocale(locale.LC_ALL, 'fr_FR.utf8')

# Date/time parameters.
datefmt = '%-d %B %Y &agrave; %H:%M:%S'
datefmtiso = '%Y-%m-%d %H:%M:%S'
homeTZ = pytz.timezone('Europe/Paris')
utc = pytz.utc

urllib3.disable_warnings()

# This function pretty much taken directly from a tweepy example.
def setup_api():
    auth = tweepy.auth.OAuthHandler(consumerKey, consumerKeySecret)
    auth.set_access_token(accessToken, accessTokenSecret)
    return tweepy.API(auth)

# Add HTML around a link
def render_link(url):
    return "<a href=\""+html.escape(url)+"\">"+html.escape(url)+"</a>"

# Transform a t.co link to a full fledged URL
def elongate(matches):
    match = matches.group()
    response = requests.Response()
    try:
        response = requests.get(match, allow_redirects=True, stream=True, headers = {'User-Agent': '12.02 (X11; Linux i686; Vivaldi Cqcb Style; U; fr-FR) Presto/2.9.201 Version/12.02/AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu'}, verify=False, timeout=60)
    except Exception as ex:
        print("Except requests", ex)
        pass
    if (response.status_code == 200) or (response.status_code == 400):
        return render_link(response.url)
    else:
        #print(response.status_code , response.url , match , response.history)
        return render_link(match)

# *Try* to add some info to the emojis. Doesn't work well on multiples chars emojis
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

# Try to get the full fledged picture from an Instagram link, but Instagram makes it very difficult.
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

# Call the Twitter API to fetch the parent thread
def getConversation(id, idParent):
    try:
        t = api.get_status(id, tweet_mode='extended')
    except tweepy.errors.TweepyException as er:
        print(er)
        print(er.args)
        print("IDs : " + id + " " + idParent)
        return
    #print t
    if hasattr(t, 'retweeted_status'):
        textElongated = regex.sub(elongate, ("RT @"+t.retweeted_status.user.screen_name+": "+ t.retweeted_status.full_text ))
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https + "," + t.retweeted_status.user.profile_image_url_https
    else:
        textElongated = regex.sub(elongate, t.full_text )
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https
    images = ""
    first = True
    try:
        #media = t.entities.get("media").pop(0).get("media_url")
        for image in t.extended_entities.setdefault("media", []):
            if first:
                images += image.get("media_url") + ":large"
                first = False
            else:
                images += ","+image.get("media_url") + ":large"
    except (IndexError,AttributeError):
        images = ""
        first = True

    insta = ""
    insta = getInstagram(regex4.search(textElongated2, re.MULTILINE))
    if insta and insta != "" and insta is not textElongated2:
        if first:
            images += insta
        else:
            images += "," + insta

    medias = ""
    first = True
    try:
        for media in t.extended_entities.setdefault("media", []):
            for variant in media.get("video_info").setdefault("variants", []):
                if first:
                    medias += variant.get("url") + ";" + variant.get("content_type")
                    first = False
                else:
                    medias += "," + variant.get("url") + ";" + variant.get("content_type")
    except (IndexError,AttributeError):
        medias = ""

    ts = t.created_at.replace(tzinfo=pytz.utc).astimezone(homeTZ)
    sql = "INSERT INTO discussion VALUES(?,?,?,?,?,?,?,?,?,?,?)"
    params = (images, t.id_str, idParent, pp, textElongated2, t.user.screen_name, t.user.name, ts.strftime(datefmt), url + t.user.screen_name + '/status/' + t.id_str, medias, ts.strftime(datefmtiso))
    with con:
        cur = con.cursor()
        try:
            cur.execute(sql, params)
        except lite.IntegrityError as er:
            pass
    if hasattr(t, 'retweeted_status'):
        if hasattr(t.retweeted_status, 'in_reply_to_status_id_str') and t.retweeted_status.in_reply_to_status_id_str is not None:
            getConversation(t.retweeted_status.in_reply_to_status_id_str, idParent)
    else:
        if hasattr(t, 'in_reply_to_status_id_str') and t.in_reply_to_status_id_str is not None:
            getConversation(t.in_reply_to_status_id_str, idParent)

# Fetch a cited tweet inside a tweet.
def getCitation(idParent, matches):
    id = matches.group(1)
    try:
        t = api.get_status(id, tweet_mode='extended')
    except tweepy.errors.TweepyException as er:
        print(er)
        print(er.args)
        print("IDs : " + id + " " + idParent)
        return
    #print t
    if hasattr(t, 'retweeted_status'):
        textElongated = regex.sub(elongate, ("RT @"+t.retweeted_status.user.screen_name+": "+ t.retweeted_status.full_text ))
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https + "," + t.retweeted_status.user.profile_image_url_https
        if hasattr(t.retweeted_status, 'in_reply_to_status_id_str') and t.retweeted_status.in_reply_to_status_id_str is not None:
            getConversation(t.retweeted_status.in_reply_to_status_id_str, t.id_str)
    else:
        textElongated = regex.sub(elongate, t.full_text )
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https
        if hasattr(t, 'in_reply_to_status_id_str') and t.in_reply_to_status_id_str is not None:
            getConversation(t.in_reply_to_status_id_str, t.id_str)
    #print(textElongated)
    #t.entities.get("media")
    images = ""
    first = True
    try:
        #media = t.entities.get("media").pop(0).get("media_url")
        for image in t.extended_entities.setdefault("media", []):
            if first:
                images += image.get("media_url") + ":large"
                first = False
            else:
                images += ","+image.get("media_url") + ":large"
    except (IndexError,AttributeError) as e:
        images = ""
        first = True
        pass

    insta = ""
    insta = getInstagram(regex4.search(textElongated2, re.MULTILINE))
    if insta and insta != "" and insta is not textElongated2:
        if first:
            images += insta
        else:
            images += "," + insta

    medias = ""
    first = True
    try:
        for media in t.extended_entities.setdefault("media", []):
            for variant in media.get("video_info").setdefault("variants", []):
                if first:
                    medias += variant.get("url") + ";" + variant.get("content_type")
                    first = False
                else:
                    medias += "," + variant.get("url") + ";" + variant.get("content_type")
    except (IndexError,AttributeError) as e:
        medias = ""
        pass

    #ts = utc.localize(t.created_at).astimezone(homeTZ)
    ts = t.created_at.replace(tzinfo=pytz.utc).astimezone(homeTZ)
    sql = "INSERT INTO citation VALUES(?,?,?,?,?,?,?,?,?,?,?)"
    params = (images, t.id_str, idParent, pp, textElongated2, t.user.screen_name, t.user.name, ts.strftime(datefmt), url + t.user.screen_name + '/status/' + t.id_str, medias, ts.strftime(datefmtiso))
    with con:
        cur = con.cursor()
        try:
            cur.execute(sql, params);
        except lite.IntegrityError as er:
            pass


# Authorize.
api = setup_api()

# Get the ID of the last downloaded tweet.
with open(idfile, 'r') as f:
  lastID = f.read().rstrip()

# Collect all the tweets since the last one.
tweets = api.home_timeline(since_id=lastID, tweet_mode='extended')
con = lite.connect(database)
con.text_factory = str

# Get the link to be elongated
regex = re.compile(r"(http|ftp|scp)(s)?://[-=a-zA-Z0-9.?&_/]+(?<!\.)")
# Get the emojis, to be tagged
regex2 = re.compile(u"([0-9|#][\u20E3])|[\u00AE|\u00A9|\u203C|\u2047|\u2048|\u2049|\u3030|\u303D|\u2139|\u2122|\u3297|\u3299][\uFE00-\uFEFF]?|[\u2190-\u21FF][\uFE00-\uFEFF]?|[\u2300-\u23FF][\uFE00-\uFEFF]?|[\u2460-\u24FF][\uFE00-\uFEFF]?|[\u25A0-\u25FF][\uFE00-\uFEFF]?|[\u2600-\u27BF][\uFE00-\uFEFF]?|[\u2900-\u297F][\uFE00-\uFEFF]?|[\u2B00-\u2BF0][\uFE00-\uFEFF]?|[\U0001F000-\U0001FFFF][\uFE00-\uFEFF]?", re.UNICODE)
# Get the cited tweets
regex3 = re.compile(r"https://twitter.com/\w+/status/(\d+)(\?.*)?\<\/a\>")
# Get the instagram images
regex4 = re.compile(r"(https://www.instagram.com/p/[-a-zA-Z0-9_]+/).*\<\/a\>$")


# For every tweet, get all the info necessary, and put them in the base
for t in reversed(tweets):
    # If it's a retweet, the display is a bit different
    if hasattr(t, 'retweeted_status'):
        textElongated = regex.sub(elongate, ("RT @"+t.retweeted_status.user.screen_name+": "+ t.retweeted_status.full_text ))
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https + "," + t.retweeted_status.user.profile_image_url_https
        if hasattr(t.retweeted_status, 'in_reply_to_status_id_str') and t.retweeted_status.in_reply_to_status_id_str is not None:
            getConversation(t.retweeted_status.in_reply_to_status_id_str, t.id_str)
        #tsrt = utc.localize(t.retweeted_status.created_at).astimezone(homeTZ)
        tsrt = t.retweeted_status.created_at.replace(tzinfo=pytz.utc).astimezone(homeTZ)
        try:
            textElongated2 = textElongated2 + "<br /><p class='date'><a href='" + url + t.retweeted_status.user.screen_name + '/status/' + t.retweeted_status.id_str + "'>" + tsrt.strftime(datefmt) + "</a></p>"
        except UnicodeDecodeError as e:
            print(e)
            print(type(textElongated2))
            print(type(url))
            print(type(t.retweeted_status.user.screen_name))
            print(type(t.retweeted_status.id_str))
            print(type(tsrt.strftime(datefmt)))
    else:
        textElongated = regex.sub(elongate, t.full_text )
        textElongated2 = regex2.sub(emoji, textElongated)
        pp = t.user.profile_image_url_https
        if hasattr(t, 'in_reply_to_status_id_str') and t.in_reply_to_status_id_str is not None:
            getConversation(t.in_reply_to_status_id_str, t.id_str)

    regex3.sub(partial(getCitation, t.id_str), textElongated2)
    
    # Get all medias
    images = ""
    first = True
    try:
        if hasattr(t, 'extended_entities') and t.extended_entities is not None:
            for image in t.extended_entities.setdefault("media", []):
                if first:
                    images += image.get("media_url_https") + ":large"
                    first = False
                else:
                    images += ","+image.get("media_url_https") + ":large"
                
        if hasattr(t, 'retweeted_status') and hasattr(t.retweeted_status, 'extended_entities') and t.retweeted_status.extended_entities is not None:
            for image in t.retweeted_status.extended_entities.setdefault("media", []):
                if first:
                    images += image.get("media_url_https") + ":large"
                    first = False
                else:
                    images += ","+image.get("media_url_https") + ":large"
    except (IndexError,AttributeError) as e:
        #first = True
        print("Error image")
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

    medias = ""
    first = True
    try:
        if hasattr(t, 'extended_entities') and t.extended_entities is not None:
            for media in t.extended_entities.setdefault("media", []):
                if media.get("video_info") is not None:
                    for variant in media.get("video_info").setdefault("variants", []):
                        if first:
                            medias += variant.get("url") + ";" + variant.get("content_type")
                            first = False
                        else:
                            medias += "," + variant.get("url") + ";" + variant.get("content_type")

        if hasattr(t, 'retweeted_status') and hasattr(t.retweeted_status, 'extended_entities') and t.retweeted_status.extended_entities is not None:
            for media in t.retweeted_status.extended_entities.setdefault("media", []):
                if media.get("video_info") is not None:
                    for variant in media.get("video_info").setdefault("variants", []):
                        if first:
                            medias += variant.get("url") + ";" + variant.get("content_type")
                            first = False
                        else:
                            medias += "," + variant.get("url") + ";" + variant.get("content_type")
    except (IndexError,AttributeError) as e:
        print("Error medias")
        print(e)
        pass
    
    medias = ",".join(OrderedDict.fromkeys(medias.split(',')))

    ts = t.created_at.replace(tzinfo=pytz.utc).astimezone(homeTZ)
    sql = "INSERT INTO feed VALUES(?,?,?,?,?,?,?,?,?,?,?,?)"
    params = (0, pp, images,"", t.id_str , textElongated2 , t.user.screen_name, t.user.name, ts.strftime(datefmt), url + t.user.screen_name + '/status/' + t.id_str, medias, ts.strftime(datefmtiso))
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
    lastID = t.id_str


# Update the ID of the last downloaded tweet.
with open(idfile, 'w') as f:
    lastID = f.write(lastID)


with open(badgeFile, 'w') as f:
    # All those parameters are used to fish the badges out of the Twitter website. You'll have to use the devtools to find the one working for you. Or you can remove this part
    cookies = {
        'auth_token': '',
        'secure_session': 'true',
        'twll': '',
        'remember_checked': '1',
        'remember_checked_on': '1',
        '_ga': '',
        'dnt': '1',
        'personalization_id': '',
        'guest_id': '',
        '_twitter_sess': '',
        'rweb_optin': 'side_no_out',
        'ct0': '',
        'eu_cn': '1',
        'ads_prefs': '=',
        'kdt': '',
        'twid': '',
    }

    headers = {
        'User-Agent': 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0',
        'Accept': '*/*',
        'Accept-Language': 'en-US,en;q=0.5',
        'Referer': 'https://twitter.com/home',
        'x-twitter-polling': 'true',
        'authorization': 'Bearer AAA',
        'x-twitter-auth-type': 'OAuth2Session',
        'x-twitter-client-language': 'en',
        'x-twitter-active-user': 'yes',
        'x-csrf-token': '',
        'Origin': 'https://twitter.com',
        'DNT': '1',
        'Connection': 'keep-alive',
        'TE': 'Trailers',
    }

    params = (
        ('supports_ntab_urt', '1'),
    )

    response = requests.get('https://api.twitter.com/2/badge_count/badge_count.json', headers=headers, params=params, cookies=cookies)
    f.write(response.text)
