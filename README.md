# Slack MediaWiki

This is a extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your Wiki like editing, adding or removing a page into [Slack](https://slack.com/) channel.

> Looking for extension that can send notifications to HipChat? [Click here](https://github.com/kulttuuri/hipchat_mediawiki).

![Screenshot](http://i.imgur.com/4SG64a3.jpg)

## Supported MediaWiki operations to send notifications

* When article is added.
* When article is removed.
* When article is moved.
* When article is edited.
* When new user is added.
* When user is blocked.
* When file is uploaded.

## Requirements

* [cURL](http://curl.haxx.se/). This extension also supports using file_get_contents for sending the data. See the configuration parameter $wgSlackSendMethod below to change this.
* MediaWiki 1.8+ (tested with version 1.8, also tested and works with 1.25+)
* Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1) Create a new Slack Incoming Webhook. When setting up the webhook, define channel where you want the notifications to go into. You can setup a new webhook on [this page](https://teachergaming.slack.com/services/new/incoming-webhook).

2) After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3) Send folder SlackNotifications into your `mediawiki_installation/extensions` folder.

4) Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
require_once("$IP/extensions/SlackNotifications/slack_notifications.php");
// Required. Your Slack incoming webhook URL. Read more from here: https://api.slack.com/incoming-webhooks
$wgSlackIncomingWebhookUrl = "";
// Required. Name the message will appear be sent from.
$wgSlackFromName = "Wiki";
// URL into your MediaWiki installation with the trailing /.
$wgWikiUrl		= "http://your_wiki_url/";
// Wiki script name. Leave this to default one if you do not have URL rewriting enabled.
$wgWikiUrlEnding = "index.php?title=";
// What method will be used to send the data to Slack server. By default this is "curl" which only works if you have the curl extension enabled. This can be: "curl" or "file_get_contents". Default: "curl".
$wgSlackSendMethod = "curl";
```

5) Enjoy the notifications in your Slack room!
	
## Additional options

These options can be set after including your plugin in your localSettings.php file.

### Customize room where notifications gets sent to

By default when you create incoming webhook at Slack site you'll define which room notifications go into. You can also override this in MediaWiki by setting the parameter below. Remember to also include # before your room name.

```php
$wgSlackRoomName = "";
```

### Actions to notify of

MediaWiki actions that will be sent notifications of into Slack. Set desired options to false to disable notifications of those actions.

```php
// New user added into MediaWiki
$wgSlackNotificationNewUser = true;
// User or IP blocked in MediaWiki
$wgSlackNotificationBlockedUser = true;
// Article added to MediaWiki
$wgSlackNotificationAddedArticle = true;
// Article removed from MediaWiki
$wgSlackNotificationRemovedArticle = true;
// Article moved under new title in MediaWiki
$wgSlackNotificationMovedArticle = true;
// Article edited in MediaWiki
$wgSlackNotificationEditedArticle = true;
// File uploaded
$wgSlackNotificationFileUpload = true;
```
	
## Additional MediaWiki URL Settings

Should any of these default MediaWiki system page URLs differ in your installation, change them here.

```php
$wgWikiUrlEndingUserRights          = "Special%3AUserRights&user=";
$wgWikiUrlEndingBlockUser           = "Special:Block/";
$wgWikiUrlEndingUserPage            = "User:";
$wgWikiUrlEndingUserTalkPage        = "User_talk:";
$wgWikiUrlEndingUserContributions   = "Special:Contributions/";
$wgWikiUrlEndingBlockList           = "Special:BlockList";
$wgWikiUrlEndingEditArticle         = "action=edit";
$wgWikiUrlEndingDeleteArticle       = "action=delete";
$wgWikiUrlEndingHistory             = "action=history";
```

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)
