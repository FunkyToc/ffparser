<?php 

/* 
 * Final Fantasy 14 Online Parser
 * Mannualy launch this script to recover server's scoreboards.
 *
 * Frequency : 1 / week
 * Pulldate : every Monday, at 11AM
 * Pulltime : ~1H / 1800 pages
 * Requires : PHP 5.6+, \PDO $db, MySQL
 * Maintain : $worlds (the servers list)
 *
 * Better use hook / cron to use this script automaticly !
 * 
 */

try 
{
	// ENV
	ignore_user_abort(true);
	set_time_limit(4800);

	require_once('../config.php');

	// Here we go ! 
	$startedTimestamp = time();
	
	// PARSER 
	foreach ($worlds as $world) 
	{
		$serverFetched = ($world == 'Mondial') ? '' : $world;
		$pulledResult = [];
		$results = [];

		// DB last update check 
		$sql = $db->prepare('SELECT id, pulldate FROM ffparser_ranking WHERE world = :world LIMIT 1');
		$sql->bindValue(':world', $world, PDO::PARAM_STR);
		$sql->execute();
		$checkWorld = $sql->fetch();

		// Prevent Spam 
		if (empty($checkWorld['id']) || (strtotime($checkWorld['pulldate']) + $rankingHoursDelay) < time() ) {

			// Foreach Company Type ($gcid) 
			for ($gcid = 1; $gcid < 4; $gcid++) 
			{ 
				$inc = 0;

				// Foreach pages of the scoreboards (10)
				for ($i = 0; $i < 10; $i++) 
				{ 
					// Main fetch
					libxml_use_internal_errors(true); // Hide the notice of HTML fetch DOM
					$html 		= new DOMDocument();
					$html->loadHtmlFile('http://fr.finalfantasyxiv.com/lodestone/ranking/gc/weekly/?filter=1&worldname='. $serverFetched .'&page='. ($i+1) .'&gcid='. $gcid);
					$xpath 		= new DOMXPath($html);
					$players 	= $xpath->query("//ol[@class='list_ranking']/li/div/div[@class='list_body clearfix']");
					
					
					foreach ($players as $player) 
					{
						// Foreach $player html content, set a new DOMXPath
					    $player_file = new DOMDocument();
					    $cloned = $player->cloneNode(TRUE);
					    $player_file->appendChild($player_file->importNode($cloned, True));
					    $player_doc = new DOMXPath($player_file);

					    // get pseudo
					    $name_tag = $player_doc->query("//div[@class='userdata']/div[@class='player_name_gold']/a");
					    $pulledResult[$gcid][$inc]['pseudo'] = trim($name_tag->item(0)->nodeValue);

					    // get score
					    $score_tag = $player_doc->query("//div[@class='point txt_yellow']/div");
					    $pulledResult[$gcid][$inc]['score'] = trim($score_tag->item(0)->nodeValue);

					    // get rank
					    $rank_tag = $player_doc->query("//div");
					    $pulledResult[$gcid][$inc]['rank'] = intval(trim($rank_tag->item(0)->nodeValue));
					    $pulledResult[$gcid][$inc]['rank'] = empty($pulledResult[$gcid][$inc]['rank']) ? ($inc+1) : $pulledResult[$gcid][$inc]['rank'];

					    // get img
					    $img_tag = $player_doc->query("//div[@class='userdata']/div[@class='thumb_wrap']/div[@class='thumb_cont_black']/a/img/@src");
					    $pulledResult[$gcid][$inc]['img'] = trim($img_tag->item(0)->nodeValue);

					    // get url
					    $url_tag = $player_doc->query("//div[@class='userdata']/div[@class='thumb_wrap']/div[@class='thumb_cont_black']/a/@href");
					    $pulledResult[$gcid][$inc]['url'] = trim($url_tag->item(0)->nodeValue);
					    
					    $inc++;
					}
				} // END 10 PAGES 

				// Keep turning ON the PDO connection (PDO connection close itself after 30s of inactivity) 
				$sql = $db->prepare('SELECT id FROM ffparser_ranking WHERE 1 LIMIT 1');
				$sql->execute();

			} // END COMPANY 

			// Compact results 
			$inc = 1;
			foreach ($pulledResult as $company => $playerList) 
			{	
				foreach ($playerList as $key => $player) 
				{
					$results[$inc] = $player;
					$inc++;
				}
			}

			// Order by score 
			usort($results, function($a, $b) 
			{
			    return $b['score'] - $a['score'];
			});

			// Re-update rank 
			foreach ($results as $key => $player) 
			{
				$results[$key]['rank'] = $key + 1;
			}


			// Push results (update or insert)
			if (!empty($results)) {

				$json_results = json_encode($results);

				if (!empty($checkWorld['id'])) {

					// Update world 
					$sql = $db->prepare('UPDATE ffparser_ranking SET pulldate = NOW(), list = :list WHERE world = :world LIMIT 1');
					$sql->bindValue(':world', $world, PDO::PARAM_STR);
					$sql->bindValue(':list', $json_results, PDO::PARAM_STR);
					$sql->execute();

					echo 'World <b>' . $world . '</b> updated<br>';
				
				} else {

					// New world 
					$sql = $db->prepare('INSERT INTO ffparser_ranking (world, pulldate, list) VALUES (:world, NOW(), :list)');
					$sql->bindValue(':world', $world, PDO::PARAM_STR);
					$sql->bindValue(':list', $json_results, PDO::PARAM_STR);
					$sql->execute();

					echo 'World <b>' . $world . '</b> created<br>';
				}
			
			} else {

				echo 'World <b>' . $world . '</b> error<br>';
			}
			
		}
	}

	// It's over !
	$endedTimestamp = time();
	$processTime = round(($endedTimestamp - $startedTimestamp) /60, 2);
	echo '<br>Process complete ! (in '. $processTime .' minutes)'; 

}
catch (Exception $e) 
{
    echo $e->getMessage();
    exit();
}
