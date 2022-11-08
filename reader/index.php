<?php 

	/**
		Create the bottom page pager, showing two pages before and after the current one, as well as the two first and last.
	*/
	function create_pager($nbPages, $page, $prefixe){
		$pager = "";
		if($nbPages > 1){
			$pager .= "<div class=\"pager\">";
			if($page > 0){
				$pager .= "<span class=\"\"><a href=\"?page=".intval($page).$prefixe."\" rel=\"previous\" accesskey=\"b\">&lt;</a><link property=\"url\" rel=\"prev\" href=\"?page=".intval($page).$prefixe."\" />&nbsp;</span>";
			}
			$prem = true;
			for($i = 0; $i<$nbPages; $i++){
				if($i == 0 || $i == 1 || $i == $page || $i == $page-1 || $i == $page+1 || $prem || $i == $page-2 || $i == $page+2 || $i == $nbPages-2 || $i == $nbPages-1){
					if(!$prem){
						$pager .= " - ";
					}
					if($i == $page){
						$pager .= "<span class=\"bold\">".intval($i+1)."</span>";
					}else{
						$pager .= "<span class=\"\"><a href=\"?page=".intval($i+1).$prefixe."\">".intval($i+1)."</a></span>";
					}
					$prem = false;
				}
			}
			if($page < $nbPages-1){
				$pager .= "<span class=\"\">&nbsp;<a href=\"?page=".intval($page+2).$prefixe."\" accesskey=\"n\" rel=\"next\">&gt;</a><link property=\"url\" rel=\"next\" href=\"?page=".intval($page+2).$prefixe."\" /></span>";
			}
			$pager .= "</div><br />";
		}
		return $pager;
	}


	/**
		Responsible for showing a single tweet.
	*/
	function print_tweet($row, $dbhandle, $is_discussion, $index){
		$output = "";
		if($index != -1){
			$output .= "<div class=\"tweet\" id=\"".$index."\" >\n";
		}else{
			$output .= "<div class=\"tweet\">\n";
		}
		
		// Has this tweet parent tweets (is the last one of a thread), or are we already in a thread displaying mode
		$has_discussion = false;
		// Discussion
		if($index!=-1){
			$requeteDiscussion = "SELECT * from discussion WHERE id_parent='".$row['id']."' ORDER BY id";
			$resultDiscussion = $dbhandle->query($requeteDiscussion);
			if (!$resultDiscussion) die("Cannot execute query.");
			$output_discussion = "";
			while ($rowD = $resultDiscussion->fetchArray()) {
				$has_discussion = true;
				$output_discussion .= print_tweet($rowD, $dbhandle, true, -1);
				$output_discussion .= "</div>\n";
			}
			if($has_discussion){
				$output .= "<div class=\"conversation\" id=\"conversation".$index."\" >";
				$output .= $output_discussion;
				$output .= "</div>";
			}
		}
		
		if(!$is_discussion){
			$output .= '<div class="main_tweet">';
		}
		$output .= "<span class=\"name\"><span class=\"alignleft\"><b>@".$row['author_screen_name']."</b> • <a href=\"https://twitter.com/intent/user?screen_name=".$row['author_screen_name']."&amp;lang=fr\"  class=\"handle\">" . $row['author_handle'] . "</a></span><span class=\"alignright\">";
		if(!$is_discussion){
			if($row['protected']==0){
				$output .= "<input class=\"supprimer\" name=\"proteger\" type=\"submit\" value=\"Prot&eacute;ger\" />";
				$output .= "<input class=\"supprimer\" name=\"supprimer\" onClick=\"hideTweet('".$index."');return true;\" type=\"submit\" value=\"Supprimer\" />";
			}else{
				$output .= "<input class=\"supprimer\" name=\"deproteger\" type=\"submit\" value=\"D&eacute;prot&eacute;ger\" />";
			}
		}
		$output .= "</span>\n";
		$output .= "</span>\n";
		$output .= "<div style=\"clear: both;\"></div>\n";
		if($is_discussion){
			foreach(explode(',', $row['profile_picture']) as $im){
				$output .= "<img src=\"".$im."\" class=\"pp\" alt=\"PP\"/>\n";
			}
		}else{
			foreach(explode(',', $row['profile_pictures']) as $im){
				$output .= "<img src=\"".$im."\" class=\"pp\" alt=\"PP\"/>\n";
			}
		}
		$output .= "<div class='text'>".nl2br($row['text']);

		// Detect if a Youtube link is included in the tweet, and if so, integrate the YT player directly
		$youtube_match = [];
		preg_match('/(http(s|):|)\/\/(www\.|)yout([^\/]*?)\/(embed\/|watch.*?v(=|%3D)|)([a-z_A-Z0-9\-]{11})/i', $row['text'], $youtube_match);
		if(count($youtube_match)>7){
			$output .= '<iframe class="youtube" src="https://www.youtube.com/embed/'.$youtube_match[7].'?theme=dark&color=black&iv_load_policy=3" allowfullscreen="1" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="origin"></iframe>';
		}
		$output .= "</div>\n";
		
		// Footer of the tweet (date+link, reply, retweet, star)
		$output .= "<div style=\"clear: both;\"></div>\n";
		$output .= "<p class=\"date\"><a href=\"".$row['link_of_tweet']."\">".$row['date']."</a>\n";
		$output .= " - <a href=\"https://twitter.com/intent/tweet?lang=fr&amp;in_reply_to=".$row['id']."\" onClick=\"window.open('https://twitter.com/intent/tweet?lang=fr&amp;in_reply_to=".$row['id']."','Repondre', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no, width=550,height=420'); return false;\">R&eacute;pondre</a>\n - ";
		$output .= "<a href=\"https://twitter.com/intent/retweet?lang=fr&amp;tweet_id=".$row['id']."\" onClick=\"window.open('https://twitter.com/intent/retweet?lang=fr&amp;tweet_id=".$row['id']."','Retweet', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no, width=550,height=420'); return false;\">Retweet</a>\n - ";
		$output .= "<a href=\"https://twitter.com/intent/favorite?lang=fr&amp;tweet_id=".$row['id']."\" onClick=\"window.open('https://twitter.com/intent/favorite?lang=fr&amp;tweet_id=".$row['id']."','Favoris', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no, width=550,height=420'); return false;\">Favoris</a>\n";
		
		// if it has a parent thread, add the switch to show it
		if($has_discussion){
			$output .= " - <span class=\"conversationSwitcher\" onClick=\"displayConversation('conversation".$index."', 'checkbox".$index."');\"><input type=\"checkbox\" id=\"checkbox".$index."\" aria-label=\"conversation\"><label>Voir la conversation</label></span>\n";
		}
		$output .= "</p>";

		// Show the images, with a special case to show directly instagram pictures
		if($row['images'] != ""){
			$output .= "<div class=\"blockImage\">";
			$isDataURI = false;
			foreach(explode(',', $row['images']) as $image){
				if($isDataURI == true){
					$image = "data:image/jpg;base64,".substr($image,2,-1);
					$isDataURI = false;
				}
				if($image == "data:image/jpg;base64"){
					$isDataURI = true;
					continue;
				}
				$image = str_replace("https://instagram", "https://www.instagram", $image);
				$alternate_image = str_replace(".jpg:large", "?format=jpg&name=orig", $image);
				$alternate_image = str_replace(".png:large", "?format=png&name=orig", $alternate_image);
				$output .= "<span class=\"image\"><img src=\"".$image."\" onerror=\"".$alternate_image."\" ".($is_discussion?'loading="lazy"':'')." alt=\"Image du tweet\" class=\"img\"  onClick=\"resize(this);return false;\" tabindex=\"0\" onkeydown=\"if (event.keyCode == 32) {this.click();};\" /></span>\n";
			}
			$output .= "</div>";
		}
		
		// Show embeded videos in the native html5 player
		if($row['videos'] != ""){
			$output .= "<div class=\"blockImage\">";
			$output .= "<span class=\"image\"><video controls=\"controls\" loop>\n";
			$video_array = array();
			$video_iter = 0;
			foreach(explode(',', $row['videos']) as $video){
				$video_array[$video_iter] = explode(';', $video);
				$video_iter++;
			}
			// Order by greater number '1280'>'720'>'480'
			usort($video_array, function($a, $b) {
				$pattern = '/\/(\d{3,4})x\d{3,4}\//';
				$a_value = 0; 
				$b_value = 0;
				if (preg_match($pattern, $a[0], $match)){
					$a_value = $match[1];
				}
				if (preg_match($pattern, $b[0], $match)){
					$b_value = $match[1];
				}
				return -$a_value <=> -$b_value;
			});
			foreach($video_array as $video){
				$output .= '<source src="'.$video[0].'" type="'.$video[1].'">';
			}
			$output .= "</video></span>";
			$output .= "</div>";
		}
		if(!$is_discussion){
			$output .= '</div>';
		}
		return $output;
	}
	
	
	// Responsible for showing a single toot. Similar as the one above, but simpler, as lots of things aren't implemented
	function print_toot($row, $dbhandle, $is_discussion, $index){
		$output = "";
		if($index != -1){
			$output .= "<div class=\"tweet toot\" id=\"".$index."\" >\n";
		}else{
			$output .= "<div class=\"tweet toot\">\n";
		}
		
		$has_discussion = false;
		// Discussion
		if($index != -1){
			$requeteDiscussion = "SELECT * from mastodon_discussion WHERE id_parent='".$row['id']."' ORDER BY id";
			$resultDiscussion = $dbhandle->query($requeteDiscussion);
			if (!$resultDiscussion) die("Cannot execute query.");
			$output_discussion = "";
			while ($rowD = $resultDiscussion->fetchArray()) {
				$has_discussion = true;
				$output_discussion .= print_toot($rowD, $dbhandle, true, -1);
			}
			if($has_discussion){
				$output .= "<div class=\"conversation\" id=\"conversation".$index."\" >";
				$output .= $output_discussion;
				$output .= "</div>";
			}
		}
		
		if(!$is_discussion){
			$output .= '<div class="main_tweet main_toot">';
		}
		$output .= "<span class=\"name\"><span class=\"alignleft\"><b><a href=\"".$row['author_url']."\" class=\"handle\">@".$row['author_screen_name']."</a></b> • <a href=\"".$row['author_url']."\"  class=\"handle\">" . $row['author_handle'] . "</a></span><span class=\"alignright\">";
		$output .= "</span>\n";
		$output .= "</span>\n";
		$output .= "<div style=\"clear: both;\"></div>\n";
		if($is_discussion){
			foreach(explode(',', $row['profile_picture']) as $im){
				$output .= "<img src=\"".$im."\" class=\"pp\" alt=\"PP\"/>\n";
			}
		}else{
			foreach(explode(',', $row['profile_pictures']) as $im){
				$output .= "<img src=\"".$im."\" class=\"pp\" alt=\"PP\"/>\n";
			}
		}
		$output .= "<div class='text'>".nl2br($row['text']);
		$youtube_match = [];
		preg_match('/(http(s|):|)\/\/(www\.|)yout([^\/]*?)\/(embed\/|watch.*?v(=|%3D)|)([a-z_A-Z0-9\-]{11})/i', $row['text'], $youtube_match);
		if(count($youtube_match)>7){
			$output .= '<iframe class="youtube" src="https://www.youtube.com/embed/'.$youtube_match[7].'?theme=dark&color=black&iv_load_policy=3" allowfullscreen="1" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="origin"></iframe>';
		}
		$output .= "</div>\n";
		//$output .= "<br />\n";
		$output .= "<div style=\"clear: both;\"></div>\n";
		$output .= "<p class=\"date\"><a href=\"".$row['link_of_tweet']."\">".$row['date']."</a>\n";
		
		global $mastodon_server;
		$output .= " - <a href=\"".$mastodon_server."@".$row['author_screen_name']."/".$row['id']."\" onClick=\"window.open('".$mastodon_server."@".$row['author_screen_name']."/".$row['id']."','Interactions', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no, width=550,height=820'); return false;\">Int&eacute;ractions</a>\n";

		if($has_discussion){
			$output .= " - <span class=\"conversationSwitcher\" onClick=\"displayConversation('conversation".$index."', 'checkbox".$index."');\"><input type=\"checkbox\" id=\"checkbox".$index."\" aria-label=\"conversation\"><label>Voir la conversation</label></span>\n";
		}
		$output .= "</p>";
		//$output .= $row['images'];
		if($row['images'] != ""){
			$output .= "<div class=\"blockImage\">";
			$isDataURI = false;
			foreach(explode(',', $row['images']) as $image){
				if($isDataURI == true){
					$image = "data:image/jpg;base64,".substr($image,2,-1);
					$isDataURI = false;
				}
				if($image == "data:image/jpg;base64"){
					$isDataURI = true;
					continue;
				}
				$image = str_replace("https://instagram", "https://www.instagram", $image);
				$alternate_image = str_replace(".jpg:large", "?format=jpg&name=orig", $image);
				$alternate_image = str_replace(".png:large", "?format=png&name=orig", $alternate_image);
				$output .= "<span class=\"image\"><img src=\"".$image."\" onerror=\"".$alternate_image."\" ".($is_discussion?'loading="lazy"':'')." alt=\"Image du tweet\" class=\"img\"  onClick=\"resize(this);return false;\" /></span>\n";
			}
			$output .= "</div>";
		}
		if($row['videos'] != ""){
			$output .= "<div class=\"blockImage\">";
			$output .= "<span class=\"image\"><video controls=\"controls\" loop>\n";
			$video_array = array();
			$video_iter = 0;
			foreach(explode(',', $row['videos']) as $video){
				$video_array[$video_iter] = explode(';', $video);
				$video_iter++;
			}
			// Order by greater number '1280'>'720'>'480'
			usort($video_array, function($a, $b) {
				$pattern = '/\/(\d{3,4})x\d{3,4}\//';
				$a_value = 0; 
				$b_value = 0;
				if (preg_match($pattern, $a[0], $match)){
					$a_value = $match[1];
				}
				if (preg_match($pattern, $b[0], $match)){
					$b_value = $match[1];
				}
				return -$a_value <=> -$b_value;
			});
			foreach($video_array as $video){
				$output .= '<source src="'.$video[0].'" type="'.$video[1].'">';
			}
			$output .= "</video></span>";
			$output .= "</div>";
		}
		if(!$is_discussion){
			$output .= '</div>';
		}
		$output .= '</div>';
		return $output;
	}
	
	function get_page_number($nbPages){
		if(isset($_GET['page']) && !empty($_GET['page']) && intval($_GET['page'])){
			$page = intval($_GET['page'])-1;
			if($page<0){
				$page = 0;
			}else if($page>$nbPages-1){
				$page = $nbPages-1;
			}
		}else{
			$page = 0;
		}
		return $page;
	}

	// Settings
	$tweetsperpage = 20;
	$owner = "Name";
	$base = "base.sqlite";
	$mastodon_server = "https://mastodon.social/";

	
	$ids = array();
	
	$pager = "";
	
	$header = "";
	
	$details = false;
	
	$html_header = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8" />
<meta name="referrer" content="no-referrer" />
<title>
	Twireader
</title>
<link rel="stylesheet" type="text/css" href="styles.css">';

	$html_header .= '<meta name="viewport" content="width=device-width,initial-scale=1.0">
<script>
	window.scrollTo(0,1);
	
	var ctrl = false;
	
	// If you press control over a tweet or a toot, it will display without the spaces collapsing to a single one. Useful for ascii art, and not much more.
	function ctrlHandler(event) {
		ctrl = event.ctrlKey;
		document.body.className = ctrl ? "ctrl-pressed" : "";
	};

	window.addEventListener("keydown", ctrlHandler, false);
	window.addEventListener("keypress", ctrlHandler, false);
	window.addEventListener("keyup", ctrlHandler, false);

	// Resize images to their native size on click
	function resize(object){
		if(object.className === "img"){
			object.className = "imgb";
			//var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
			var w = document.documentElement.clientWidth;
			if(object.width < w){
				//object.style.marginLeft = "-" + (Math.max(0,object.width-object.parentNode.parentNode.offsetWidth))/2 + "px";
				//object.style.left = (object.parentNode.parentNode.offsetWidth-object.width)/2 + "px";
			}else{
				//object.style.marginLeft = "-" + object.parentNode.parentNode.offsetLeft + "px";
				//object.style.marginLeft = object.parentNode.parentNode.offsetLeft + "px";
				object.style.marginLeft = (object.width-w) + "px";
			}
		}else{
			object.className = "img";
			object.style.marginLeft = "0";
		}
	}

	
	function hideAndPlay(object){
		object.parentElement.parentElement.style.zIndex="-1";
		object.parentElement.parentElement.nextSibling.getElementsByTagName("video")[0].play();
	}
	
	// Show or hide the parent thread
	async function displayConversation(id, checkbox){
		if (document.getElementById(id).style.display === "block"){
			var height = document.getElementById(id).getBoundingClientRect().height;
			var scrollHeight = document.getElementById(id).scrollHeight;
			var scrolly = window.scrollY;
			document.getElementById(id).style.display = "none";
			document.getElementById(checkbox).checked = false;
			//window.scrollTo(0, scrolly-(scrollHeight+(parseInt(height)-height)));
			window.scrollTo(0, scrolly-height-9);
		}else{
			let scrolly = window.scrollY;
			document.getElementById(id).style.display = "block";
			document.getElementById(checkbox).checked = true;
			let height = document.getElementById(id).getBoundingClientRect().height;
			let scrollHeight = document.getElementById(id).scrollHeight;
			//window.scrollTo(0, scrolly+(scrollHeight+(parseInt(height)-height)));
			window.scrollTo(0, scrolly+height+10);
		}
	}

	// Hide a tweet. It is just useful feedback for the delete button
	function hideTweet(id){
		document.getElementById(id).style.visibility = "hidden";
		document.getElementById("conversation"+id).style.visibility = "hidden";
		document.getElementById("citation"+id).style.visibility = "hidden";
	}
	
</script>
<script src="hammer.min.js" defer></script>
<link rel="shortcut icon" type="image/png" href="favicon.png" />
</head>
<body>';

	$dbhandle = new SQLite3($base);
	if (!$dbhandle) die ($error);
	
	$dbhandle->busyTimeout(60000);
	
	$header .= "<div class=\"header\">";
	
	$requete = "SELECT COUNT(*) from feed";
	$result = $dbhandle->query($requete);
	if (!$result) die("Cannot execute query.");
	$nbtweets = $result->fetchArray()[0];
	$header .=  "<a href=\"./\">".$nbtweets." tweets au total</a> - ";
	

	$header .=  "</a> - ";*/
	$header .=  "<a href=\"./?mentions\">mentions</a> - ";
	
	// Add a list of all tweets authors handles in the base
	$requete = "SELECT author_screen_name, COUNT(DISTINCT id) AS count FROM feed GROUP BY author_screen_name ORDER BY author_screen_name";
	$result = $dbhandle->query($requete);
	if (!$result) die("Cannot execute query.");
	
	$liste = "<span><select aria-label=\"liste following\" onchange=\"this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);\"><option value=\"./\">Selectionner</option>\n";
	while ($row = $result->fetchArray()){
		if(isset($_GET['handle']) && !empty($_GET['handle']) && $_GET['handle'] == $row['author_screen_name']){
			$liste .= "<option value=\"?handle=".$row['author_screen_name']."\" selected>".$row['author_screen_name']." - ".$row['count']."</option>";
		}else{
			$liste .= "<option value=\"?handle=".$row['author_screen_name']."\">".$row['author_screen_name']." - ".$row['count']."</option>";
		}
	}
	$liste .= "</select></span>\n";
	$header .=  $liste;
	
	
	// Different cases of display, based on the arguments in the URL
	if(isset($_GET['handle']) && !empty($_GET['handle'])){
		// You are searching within an handle
		if(isset($_GET['search']) && !empty($_GET['search'])){
			$stmt = $dbhandle->prepare("SELECT COUNT(*) FROM feed WHERE author_screen_name=:author_screen_name AND text LIKE :search");
			$stmt->bindValue(':author_screen_name', $_GET['handle'], SQLITE3_TEXT);
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			$nbtweets = $result->fetchArray()[0];
			if($nbtweets == 0){
				header('location: ./?handle='.$_GET['handle']);
			}
			
			$nbPages = ceil($nbtweets/$tweetsperpage);

			$page = get_page_number($nbPages);
			
			$stmt = $dbhandle->prepare("SELECT * FROM feed WHERE author_screen_name=:author_screen_name AND text LIKE :search LIMIT ".$tweetsperpage*$page.",".$tweetsperpage);
			$stmt->bindValue(':author_screen_name', $_GET['handle'], SQLITE3_TEXT);
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			
			$pager = create_pager($nbPages, $page, "&amp;handle=".$_GET['handle']."&amp;search=".$_GET['search']);
			$details=true;
		// All tweet from a person
		}else{
			$stmt = $dbhandle->prepare("SELECT COUNT(*) FROM feed WHERE author_screen_name=:author_screen_name");
			$stmt->bindValue(':author_screen_name', $_GET['handle'], SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			$nbtweets = $result->fetchArray()[0];
			if($nbtweets == 0){
				header('location: ./');
			}
			
			$nbPages = ceil($nbtweets/$tweetsperpage);

			$page = get_page_number($nbPages);
			
			$stmt = $dbhandle->prepare("SELECT * FROM feed WHERE author_screen_name=:author_screen_name LIMIT ".$tweetsperpage*$page.",".$tweetsperpage);
			$stmt->bindValue(':author_screen_name', $_GET['handle'], SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			
			$pager = create_pager($nbPages, $page, "&amp;handle=".$_GET['handle']);
			$details=true;
		}
	// All cases where you are mentionned. Might be broken
	}else if(isset($_GET['mentions'])){
		// A search in your mentions
		if(isset($_GET['search']) && !empty($_GET['search'])){
			/*if($mentions == 0){
				header('location: ./');
			}*/
			$stmt = $dbhandle->prepare("SELECT COUNT(*) FROM feed WHERE text LIKE :name AND text LIKE :search");
			$stmt->bindValue(':name', "%".$owner."%", SQLITE3_TEXT);
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			$nbtweets = $result->fetchArray()[0];
			if($nbtweets == 0){
				header('location: ./?mentions');
			}
			
			$nbPages = ceil($nbtweets/$tweetsperpage);

			$page = get_page_number($nbPages);
			
			$stmt = $dbhandle->prepare("SELECT * FROM feed WHERE text LIKE :name AND text LIKE :search LIMIT ".$tweetsperpage*$page.",".$tweetsperpage);
			$stmt->bindValue(':name', "%".$owner."%", SQLITE3_TEXT);
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			
			$pager = create_pager($nbPages, $page, "&amp;mentions&amp;search=".$_GET['search']);
			$details=true;
		// All tweets where you are mentionned. Broken
		}else{
			
			$stmt = $dbhandle->prepare("SELECT * FROM feed WHERE text LIKE '%".$owner."%' LIMIT ".$tweetsperpage*$page.",".$tweetsperpage);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			
			$pager = create_pager($nbPages, $page, "&amp;mentions");
		}
	}else{
		// Simple search (doesn't look in cited tweets nor threads)
		if(isset($_GET['search']) && !empty($_GET['search'])){
			$stmt = $dbhandle->prepare("SELECT COUNT(*) FROM feed WHERE text LIKE :search");
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			$nbtweets = $result->fetchArray()[0];
			if($nbtweets == 0){
				header('location: ./');
			}
			
			$nbPages = ceil($nbtweets/$tweetsperpage);

			$page = get_page_number($nbPages);
			
			$stmt = $dbhandle->prepare("SELECT * FROM feed WHERE text LIKE :search LIMIT ".$tweetsperpage*$page.",".$tweetsperpage);
			$stmt->bindValue(':search', "%".$_GET['search']."%", SQLITE3_TEXT);
			$result = $stmt->execute();
			if (!$result) die("Cannot execute query.");
			
			$pager = create_pager($nbPages, $page, "&amp;search=".$_GET['search']);
			$details=true;
		// Default view
		}else{
			$nbPages = ceil($nbtweets/$tweetsperpage);

			$page = get_page_number($nbPages);
			
			$requete = "SELECT * FROM feed ORDER BY id LIMIT ".$tweetsperpage*$page.",".$tweetsperpage;
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
			$pager = create_pager($nbPages, $page, "");
			
			$requete_mastodon = "SELECT * FROM mastodon_feed WHERE timestamp >= (SELECT timestamp FROM feed ORDER BY id LIMIT ".($tweetsperpage*$page-1).",1) AND timestamp < (SELECT timestamp FROM feed WHERE id == (SELECT MAX(id) FROM (SELECT id FROM feed ORDER BY id LIMIT ".$tweetsperpage*$page.",".$tweetsperpage."))) ORDER BY id";
			$result_mastodon = $dbhandle->query($requete_mastodon);
			if (!$result_mastodon) die("Cannot execute query.");
		}
	}

	$output = "";
	$index = 0;
	while ($row = $result->fetchArray()) {
		
		
		// Tweet
		$output .= "<form method=\"post\" action=\"erase.php?index=".($index-1).(($_SERVER['QUERY_STRING']=="")?"":"&amp;".$_SERVER['QUERY_STRING'])."\">";
		$output .= print_tweet($row, $dbhandle, false, $index);
		$id = array();
		$id[] = $row['id'];
		$encoded=json_encode($id); 
		$output .= "<input type=\"hidden\" name=\"ids\" value=\"".htmlentities($encoded)."\">\n";
		if($row['protected']==0){
			$ids[] = $row['id'];
		}
		
		// Citation
		
		$requeteCitation = "SELECT * from citation WHERE id_parent='".$row['id']."' ORDER BY id";
		$resultCitation = $dbhandle->query($requeteCitation);
		if (!$resultCitation) die("Cannot execute query.");
		$citation = false;
		while ($rowC = $resultCitation->fetchArray()) {
			$citation = true;
			$citationHasDiscussion = false;
			$output .= "<div class=\"citation\" id=\"citation".$index."\" >";
			
			$output .= print_tweet($rowC, $dbhandle, true, $rowC['id']);
			$output .= "</div>\n";
			
			
			$output .= "</div>";
		}
		
		
		$output .= "</div>\n";
		$output .= "</form>\n";
		$output .= "<div class=\"separatordiv\"><img src=\"separator2.png\" class=\"separator\" alt=\"separator\" /></div>";
		$index++;
	}

	// A bit of cleaning for CSS, linking handles, and linking hashtags
	$output = preg_replace('!^<div class=\'text\'>(RT \@[A-Za-z0-9_]{1,16}):!m', "<div class='text rt'><span class=\"rt\">\\1</span>:", $output);
	$output = preg_replace('!([^A-Za-z0-9/])\@([A-Za-z0-9_]{1,16})!', "\\1<a href=\"http://twitter.com/\\2\" class=\"handle\">@\\2</a>", $output);
	$output = preg_replace('/(?<![&amp;A-Za-z0-9\/-])(\#([^[:space:][:punct:]]+))/', "<a href=\"http://twitter.com/hashtag/\\2\" class=\"hashtag\">\\1</a>", $output);
	
	$output .= "<div class=\"separatordivmastodon\"><img src=\"mastodon.png\" class=\"separator\" alt=\"separator\" /></div>";

	if(isset($result_mastodon)){
		while ($row_mastodon = $result_mastodon->fetchArray()) {
			$output .= print_toot($row_mastodon, $dbhandle, false, $index);
			$output .= "<div class=\"separatordiv\"><img src=\"separator2.png\" class=\"separator\" alt=\"separator\" /></div>";
			$index++;
		}
	}
	
	$output = preg_replace('!src="http://!', 'src="https://', $output);
	$output = preg_replace('!<abbr class="emoji"([^<>]*)>([^<>]*)</abbr>[\x{200B}-\x{200D}]<abbr class="emoji".*?>(.*?)</abbr>!mu', "<abbr class=\"emoji\"\\1>\\2&zwj;\\3</abbr>", $output);

	$dbhandle->close();
	unset($dbhandle);

	if($details){
		$header .= " - ".$nbtweets." tweets";
	}
	
	$header .= " - \n<form id=\"searchForm\" method=\"get\" action=\"?".$_SERVER['QUERY_STRING']."\">\n";
	if(isset($_GET['search']) && !empty($_GET['search'])){
		$header .= "<input type=\"text\" name=\"search\" id=\"search\" aria-label=\"search\" value=\"".$_GET['search']."\">\n";
	}else{
		$header .= "<input type=\"text\" name=\"search\" id=\"search\" aria-label=\"search\">\n";
	}
	if(isset($_GET['handle']) && !empty($_GET['handle'])){
		$header .= "<input type=\"hidden\" name=\"handle\" value=\"".$_GET['handle']."\">\n";
	}else if(isset($_GET['mentions'])){
		$header .= "<input type=\"hidden\" name=\"mentions\">\n";
	}
	$header .= "</form>";
	$header .= ' - <a href="https://twitter.com/intent/tweet?lang=fr" onClick="window.open(\'https://twitter.com/intent/tweet?lang=fr\',\'Tweet\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no,  copyhistory=no, width=600,height=400\'); return false;">Tweet</a>';
	
	$json = (array) json_decode(file_get_contents('./badges.txt'));
	$header .= ' - <a href="https://twitter.com/notifications">N:'.$json["ntab_unread_count"].'</a> - <a href="https://twitter.com/messages">DM:'.$json["dm_unread_count"].'</a>';
	
	$header .=  "</div><hr />\n";
	
	echo $html_header;
	
	echo $header;
	
	echo $output;
	
	echo $pager;

	echo "<form method=\"post\" action=\"erase.php?".$_SERVER['QUERY_STRING']."\">";
	echo "<input type=\"hidden\" name=\"ids\" value=\"".htmlentities(json_encode($ids))."\">";
	echo "<input id=\"toutSupprimer\" class=\"supprimer\" name=\"supprimer\" type=\"submit\" value=\"Tout supprimer\" />";
	echo "</form>";
	if(isset($_GET['handle']) && !empty($_GET['handle'])){
		$handle_link = '&handle='.$_GET['handle'];
	}else{
		$handle_link = '';
	}
	if(isset($_GET['search']) && !empty($_GET['search'])){
		$search_link = '&search='.$_GET['search'];
	}else{
		$search_link = '';
	}
	echo '
<script>
	// Pan the page to go to the preview or next page (pan == click an go to left or right)
	window.onload = function() {
		var element = document.getElementsByTagName("HTML")[0];
		var hammertime = Hammer(element, {touchAction: "pan-y"});
		//hammertime.get("pan").set({ threshold : 150 });
		//hammertime.get(\'pan\').set({ direction: Hammer.DIRECTION_HORIZONTAL, threshold: 200 });
		hammertime.get(\'swipe\').set({ direction: Hammer.DIRECTION_HORIZONTAL, threshold: 200 });

		hammertime.on("swipeleft", function(event) {
			document.body.style.boxShadow="inset 0 0 0 111px darkgreen";
			document.location.href="?page='.intval($page+2).$handle_link.$search_link.'";
		});
		hammertime.on("swiperight", function(event) {
			document.body.style.boxShadow="inset 0 0 0 111px darkred";
			document.location.href="?page='.intval($page).$handle_link.$search_link.'";
		});

		var videos = document.querySelectorAll("video");
		videos.forEach(function(element){
			if(element.parentElement.parentElement.parentElement.getElementsByClassName("text")[0].textContent.includes("/video/1")){
				element.parentElement.parentElement.previousElementSibling.style.display="none";
				/*var blockImagePre=element.parentElement.parentElement.previousElementSibling;
				blockImagePre.style.position="absolute";
				//blockImagePre.style.left="7px";
				blockImagePre.style.zIndex="1";
				blockImagePre.className="";
				var imgPre=blockImagePre.getElementsByTagName("img")[0];
				imgPre.className="imgb";
				imgPre.width=element.offsetWidth;
				imgPre.setAttribute("onclick","hideAndPlay(this);return false;");*/
				
			}
		});
	}
	
	// You can use the j/k letters to go to the previous or next page
	document.onkeydown = function(e) {
		if (e.target.tagName.toLowerCase() != "input"){
			if (e.which == 75 && !e.ctrlKey && !e.altKey && !e.shiftKey) {
				document.body.style.boxShadow="inset 0 0 0 111px darkgreen";
				document.location.href="?page='.intval($page+2).$handle_link.$search_link.'";
			} else if (e.which == 74 && !e.ctrlKey && !e.altKey && !e.shiftKey) {
				document.body.style.boxShadow="inset 0 0 0 111px darkred";
				document.location.href="?page='.intval($page).$handle_link.$search_link.'";

			} else if (e.which == 85 && !e.ctrlKey && !e.altKey && !e.shiftKey) {
				if(document.activeElement.closest(".tweet[id]") != null){
					var id = parseInt(document.activeElement.closest(".tweet[id]").id);
					if(id > 0) document.getElementById(id-1).querySelector(".main_tweet a").focus();
				}else{
					document.getElementById("19").querySelector(".main_tweet a").focus();
				}
			} else if (e.which == 78 && !e.ctrlKey && !e.altKey && !e.shiftKey) {
				if(document.activeElement.closest(".tweet[id]") != null){
					var id = parseInt(document.activeElement.closest(".tweet[id]").id);
					if(id < 19) document.getElementById(id+1).querySelector(".main_tweet a").focus();
				}else{
					document.getElementById("0").querySelector(".main_tweet a").focus();
				}
			}
		}
	};
</script>
<link rel="prerender" href="?page='.intval($page+2).$handle_link.$search_link.'">';



?>
</body>
</html>
