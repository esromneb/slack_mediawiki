<?php
/**#@+
 * This extension integrates Slack with MediaWiki. Sends Slack notifications
 * for selected actions that have occurred in your MediaWiki sites.
 *
 * This file contains functionality for the extension.
 *
 * @ingroup Extensions
 * @link https://github.com/kulttuuri/slack_mediawiki
 * @author Aleksi Postari / kulttuuri <aleksi@postari.net>
 * @copyright Copyright © 2015, Aleksi Postari
 * @license http://en.wikipedia.org/wiki/MIT_License MIT
 */

if (!defined('MEDIAWIKI')) die();

$hpc_attached = true;
require_once("slack_default_config.php");

if ($wgSlackNotificationEditedArticle)
	$wgHooks['ArticleSaveComplete'][] = array('slack_article_saved');			// When article has been saved
if ($wgSlackNotificationAddedArticle)
	$wgHooks['ArticleInsertComplete'][] = array('slack_article_inserted');		// When new article has been inserted
if ($wgSlackNotificationRemovedArticle)
	$wgHooks['ArticleDeleteComplete'][] = array('slack_article_deleted');		// When article has been removed
if ($wgSlackNotificationMovedArticle)
	$wgHooks['TitleMoveComplete'][] = array('slack_article_moved');				// When article has been moved
if ($wgSlackNotificationNewUser)
	$wgHooks['AddNewAccount'][] = array('slack_new_user_account');				// When new user account is created
if ($wgSlackNotificationBlockedUser)
	$wgHooks['BlockIpComplete'][] = array('slack_user_blocked');				// When user or IP has been blocked
if ($wgSlackNotificationFileUpload)
	$wgHooks['UploadComplete'][] = array('slack_file_uploaded');				// When file has been uploaded

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Slack Notifications',
	'author' => 'Aleksi Postari',
	'description' => 'Sends Slack notifications for selected actions that have occurred in your MediaWiki sites.',
	'url' => 'https://github.com/kulttuuri/slack_mediawiki',
	"version" => "1.0"
);

/**
 * Gets nice HTML text for user containing the link to user page
 * and also links to user site, groups editing, talk and contribs pages.
 */
function getSlackUserText($user)
{
	global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingUserPage,
               $wgWikiUrlEndingBlockUser, $wgWikiUrlEndingUserRights, 
	       $wgWikiUrlEndingUserTalkPage, $wgWikiUrlEndingUserContributions;
	
	return sprintf(
		"%s",
		"<".$wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserPage.$user."|$user>"
		);
}

/**
 * Gets nice HTML text for article containing the link to article page
 * and also into edit, delete and article history pages.
 */
function getSlackArticleText(WikiPage $article)
{
        global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
               $wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory;

        return sprintf(
                "%s",
                "<".$wgWikiUrl.$wgWikiUrlEnding.$article->getTitle()->getFullText()."|".$article->getTitle()->getFullText().">"
/*                "move",
                "protect",
                "watch"*/
                );
}

/**
 * Gets nice HTML text for title object containing the link to article page
 * and also into edit, delete and article history pages.
 */
function getSlackTitleText(Title $title)
{
        global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
               $wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory;

        $titleName = $title->getFullText();
        return sprintf(
                "%s (%s | %s | %s)",
                "<".$wgWikiUrl.$wgWikiUrlEnding.$titleName."|".$titleName.">",
                "<".$wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingEditArticle."|edit>",
                "<".$wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingDeleteArticle."|delete>",
                "<".$wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingHistory."|history>"/*,
                "move",
                "protect",
                "watch"*/
                );
}

/**
 * Occurs after the save page request has been processed.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
 */
function slack_article_saved(WikiPage $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
{
    // Skip new articles that have view count below 1. Adding new articles is already handled in article_added function and
	// calling it also here would trigger two notifications!
	$isNew = $status->value['new']; // This is 1 if article is new
	if ($isNew == 1) {
		return true;
	}
	
	$message = sprintf(
		"%s has %s article %s %s",
		getSlackUserText($user),
                $isMinor == true ? "made minor edit to" : "edited",
                getSlackArticleText($article),
		$summary == "" ? "" : "Summary: $summary");
	push_slack_notify($message, "yellow");
	return true;
}

/**
 * Occurs after a new article has been created.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleInsertComplete
 */
function slack_article_inserted(WikiPage $article, $user, $text, $summary, $isminor, $iswatch, $section, $flags, $revision)
{
        // Do not announce newly added file uploads as articles...
        if ($article->getTitle()->getNsText() == "File") return true;
        
	$message = sprintf(
		"%s has created article %s %s",
		getSlackUserText($user),
		getSlackArticleText($article),
		$summary == "" ? "" : "Summary: $summary");
	push_slack_notify($message, "green");
	return true;
}

/**
 * Occurs after the delete article request has been processed.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
 */
function slack_article_deleted(WikiPage $article, $user, $reason, $id)
{
	$message = sprintf(
		"%s has deleted article %s Reason: %s",
		getSlackUserText($user),
		getSlackArticleText($article),
		$reason);
	push_slack_notify($message, "red");
	return true;
}

/**
 * Occurs after a page has been moved.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
 */
function slack_article_moved($title, $newtitle, $user, $oldid, $newid, $reason = null)
{
	$message = sprintf(
		"%s has moved article %s to %s. Reason: %s",
		getSlackUserText($user),
		getSlackTitleText($title),
		getSlackTitleText($newtitle),
		$reason);
	push_slack_notify($message, "green");
	return true;
}

/**
 * Called after a user account is created.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
 */
function slack_new_user_account($user, $byEmail)
{
	$message = sprintf(
		"New user account %s was just created (email: %s, real name: %s)",
		getSlackUserText($user),
		$user->getEmail(),
		$user->getRealName());
	push_slack_notify($message, "green");
	return true;
}

/**
 * Called when a file upload has completed.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
 */
function slack_file_uploaded($image)
{
    global $wgWikiUrl, $wgWikiUrlEnding, $wgUser;
	$message = sprintf(
		"%s has uploaded file <%s|%s> (format: %s, size: %s MB, summary: %s)",
		getSlackUserText($wgUser->mName),
		$wgWikiUrl . $wgWikiUrlEnding . $image->getLocalFile()->getTitle(),
		$image->getLocalFile()->getTitle(),
		$image->getLocalFile()->getMimeType(),
		round($image->getLocalFile()->size / 1024 / 1024, 3),
            $image->getLocalFile()->getDescription());

	push_slack_notify($message, "green");
	return true;
}

/**
 * Occurs after the request to block an IP or user has been processed
 * @see http://www.mediawiki.org/wiki/Manual:MediaWiki_hooks/BlockIpComplete
 */
function slack_user_blocked(Block $block, $user)
{
	global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingBlockList;
	$message = sprintf(
		"%s has blocked %s %s Block expiration: %s. %s",
		getSlackUserText($user),
                getSlackUserText($block->getTarget()),
		$block->mReason == "" ? "" : "with reason '".$block->mReason."'.",
		$block->mExpiry,
		"<".$wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingBlockList."|List of all blocks>.");
	push_slack_notify($message, "red");
	return true;
}

/**
 * Sends the message into Slack room.
 * @param message Message to be sent.
 * @param color Background color for the message. One of "green", "yellow" or "red". (default: yellow)
 * @see https://api.slack.com/incoming-webhooks
*/
function push_slack_notify($message, $bgColor)
{
	global $wgSlackIncomingWebhookUrl, $wgSlackFromName, $wgSlackRoomName, $wgSlackSendMethod;

	  $slackColor = "warning";
	  if ($bgColor == "green") $slackColor = "good";
	  else if ($bgColor == "red") $slackColor = "danger";
	
	  $optionalChannel = "";
      if (!empty($wgSlackRoomName)) {
      	$optionalChannel = ' "channel": "'.$wgSlackRoomName.'", ';
      }

      // Convert " to ' in the message to be sent as otherwise JSON formatting would break.
      $message = str_replace('"', "'", $message);

	  $post = sprintf('payload={"text": "%s", "username": "%s",'.$optionalChannel.' "attachments": [ { "color": "%s" } ]}',
	  		  urlencode($message),
	  		  urlencode($wgSlackFromName),
	  		  urlencode($slackColor));

	  // Use file_get_contents to send the data. Note that you will need to have allow_url_fopen enabled in php.ini for this to work.
	  if ($wgSlackSendMethod == "file_get_contents") {
		$extradata = array(
			'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => $post,
			),
		);
		$context = stream_context_create($extradata);
		$result = file_get_contents($wgSlackIncomingWebhookUrl, false, $context);
	  }
	  // Call the Slack API through cURL (default way). Note that you will need to have cURL enabled for this to work.
	  else {
	      $h = curl_init();
	      curl_setopt($h, CURLOPT_URL, $wgSlackIncomingWebhookUrl);
	      curl_setopt($h, CURLOPT_POST, 1);
	      curl_setopt($h, CURLOPT_POSTFIELDS, $post);
		  // I know this shouldn't be done, but because it wouldn't otherwise work because of SSL...
		  curl_setopt ($h, CURLOPT_SSL_VERIFYHOST, 0);
		  curl_setopt ($h, CURLOPT_SSL_VERIFYPEER, 0);
		  // ... Aaand execute the curl script!
	      curl_exec($h);
	      curl_close($h);
  	  }
}
?>
