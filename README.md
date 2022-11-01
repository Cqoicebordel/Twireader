# Twireader

## Presentation

Here is a kludged together Twitter (and Mastodon) reader. It displays them chronologically, in a paged reader.

Its workflow is somewhat simple :  
1. A cron-ed script fetch the tweets from your timeline  
2. They are stored in a SQLite database  
3. You use a local hosted webpage to view them

### Features

- Paged Twitter and Mastodon feed, displayed chronologically  
- Self hosted database and webpage  
- Direct link to Tweets, retweeted tweets, cited tweets and thread tweets  
- Switchable view of a parent thread  
- You can show only tweets of a particular handle  
- Simple search included  
- Direct links to tweet, see notifications or DMs  
- Display of number of unread notifications and DMs  
- Show full size image  
- Use native HTML5 video player to display Twitter videos  
- Integrate Youtube players  
- Use J/K letters or panning to switch pages  
- Protect or delete tweets you don't want to keep  
- Gif are shown as images and videos  


## Installation

### The Database

First, lets create the database.

Go to the base folder and execute  
```
sqlite3 base.sqlite < base.sqlite.sql
```

You can also use the GUI SQLite Browser to do it. Whatever float your boat.

### The scripts

There are a few prerequisites for the scripts. You must have Python 3, and install a few dependencies, like Tweepy and SQLite.

Then, you must add the correct tokens and settings for your installations.

Finally, if you succed running the scripts, you should put them in cron jobs or whatever regularly scheduled task manager you have.

I used a 3 minutes refresh for Twitter. I had to ignore some errors so the user mailbox wouldn't be out of control. And even like this, it will be filled with some errors  
```
*/3 * * * * PYTHONWARNINGS="ignore:Unverified HTTPS request" python3 /home/cqoicebordel/scripts/archive-tweets.py
```

For Mastodon, I used a much gentler option, as there are a lot less toots in my timeline, and because I wouldn't spam the Mastodon's servers. So it fetches it 5 times per hours. At least, for now.  
```
1,13,25,37,49 * * * * python3 /home/cqoicebordel/scripts/archive_toots.py
```

As you may have noticed, too, I tried to avoid running both of them on the same tick.

### The webpage

Here you have to already have a webserver running, with PHP 7.0+ installed. I haven't tested with 8.0+ yet though. Fill the parameters, and you can just point your browser to the index.php webpage.

## Problems

### Situation of the code

As I said above, it's a kludged together code, untested on other machine than mine. It was strictly a tool to be used by me only. I provide it as is, without support, without anything else.  
If you have question, or want to contribute, I may help, of course, but don't expect it as a given.  
For the rest, the license is akin to a CC-by-nc. 

### Situation of the features

Some features might not work. Sometimes, it's because I didn't have any usage for them, and abandonned them. Some other times, it's because Twitter broke them.

#### Twitter Intents

The reply, retweet and star buttons used to use Twitter intents, simple html pages doing only what was asked. They removed it, but I found keeping the small windows to have the mobile site was not bad.

#### Mastodon features

Mastodon integration is in an alpha stage here. Not to say there will be a beta, but just to say that the code wasn't here for long, and I haven't had time to had feature I could use.  
I also found some bugs I didn't solve yet (for example, the URI of a toot provided by the API might point to a 404 page).

#### SQLite database

I found that on my machine (which is a Core 2 Quad with 8 GB of RAM, so… yeah), it felt a bit slow when it reached 100 000 tweets stored in the feed table (in the timeline), so usually, at that point, I archive the database, and put a new empty one in. YMMV.

#### Twitter and Mastodon timeline sync

I had to make a choice to display both Twitter and Mastodon feeds on a single page. Because my feed is usually slower on Mastodon, I choose to display a fixed number of tweets, 20, and try to match the times of the tweets to display the toots, to keep it mostly in sync.  
It means that the number of toots per page is random. That might annoy some users.

#### Misc

- There are no alt text on the images  
- Polls aren't rendered, at all  
- Mixed medias tweets don't show all medias yet  
- …
