<?php

/* 
 * Final Fantasy 14 Online Parser
 * Pull weekly top players scoreboards, order by Grand Company points, on the official website of the game
 * PHP crawler to Json array, store in SQL database 
 *
 * Author : FunkyToc
 * Website : http://funkycoding.fr
 * Exemple : http://funkycoding.fr/ffparser
 * Update : 2018/03/19
 *
 * Url : http://fr.finalfantasyxiv.com
 * Target : http://fr.finalfantasyxiv.com/lodestone/ranking/gc/weekly/
 * Frequency : 1 / week
 * Pulldate : every Monday, at 11AM
 * Pulltime : ~1H / 1800 pages
 * Requires : PHP 5.6+, \PDO $db, MySQL
 * Maintain : $worlds (the servers list)
 *
 * Better use hook / cron to use this script automaticly !
 * 
 */

try {

	require_once('config.php');

	// VARS
	$results = [];
	$search_guild = isset($_POST['search_guild']) && !empty(trim($_POST['search_guild'])) ? trim($_POST['search_guild']) : '';
	$search_players = isset($_POST['search_players']) && !empty(trim($_POST['search_players'])) ? trim($_POST['search_players']) : '';
	
	// SELECTED WORLD 
	$serverSelected = isset($_POST['server']) ? trim($_POST['server']) : false;
	$server = array_search($serverSelected, $worlds) !== false ? $worlds[array_search($serverSelected, $worlds)] : 'Mondial';

	// GUILD check 
	if (!empty($search_guild)) {

		// GUILD check 
		$sql = $db->prepare('SELECT id, pulldate, world, city, guildName, guildID, members FROM ffparser_guild WHERE guildID = :guildID LIMIT 1');
		$sql->bindValue(':guildID', $search_guild, PDO::PARAM_STR);
		$sql->execute();
		$checkGuild = $sql->fetch();

		if (!empty($checkGuild['id']) && (strtotime($checkGuild['pulldate']) + $guildHoursDelay) < time()) {

			// PULL guild members 
			// ajax
			
		} elseif (!empty($checkGuild['members'])) {

			// UPDATE search players 
			$search_players = $checkGuild['members'];
			$server = $checkGuild['world'];
		}
	}

	// WORLD check 
	$sql = $db->prepare('SELECT id, pulldate, world, list FROM ffparser_ranking WHERE world = :world LIMIT 1');
	$sql->bindValue(':world', $server, PDO::PARAM_STR);
	$sql->execute();
	$checkWorld = $sql->fetch();
	$results = json_decode($checkWorld['list']);

	// SEARCH PSEUDOS 
	if (!empty($search_players) && !empty($results)) {

		$searches = explode(',', $search_players);
		$unique_ranks = []; // uniq player (no duplicate)  
		$results_search = [];
		$i = 0;

		foreach ($results as $player) 
		{
			foreach ($searches as $search_pseudo) 
			{
				if ( stristr($player->pseudo, trim(strip_tags($search_pseudo))) && array_search($player->rank, $unique_ranks) === false ) {

					$results_search[$i] = $player;
					$unique_ranks[] = $player->rank;
					$i++;
				}
			}
		}

		$results = $results_search;	
	}

	// VIEW 
	?>

	<!-- SEARCH FORM START -->
	<div id="search">
		<form method="post">
			<p>
				<span>Server : </span>
				<select name="server">
					<?php foreach ($worlds as $world) { ?>
						<option value="<?= $world ?>" <?= ($server == $world) ? 'selected' : ''?> ><?= $world ?></option>
					<?php } ?>
				</select>
			</p>
			<div>
				<p>
					<span>Guild ID : </span>
					<input type="text" name="search_guild" placeholder="9235053248388270324" value="<?= $search_guild ?>">
				</p>
				<p>
					<span>Search pseudos : </span>
					<input type="text" name="search_players" placeholder="Your Pseudo, Cloud, Strife" value="<?= $search_players ?>">
				</p>
			</div>
			<div>
				<button type="submit">Chercher</button>
			</div>
		</form>
	</div>
	<!-- SEARCH FORM END -->

	<?php if (!empty($results)) { ?>
		<!-- PLAYER LIST START -->
		<div id="playerList">
			
			<?php if (!empty($checkGuild['id'])) { ?>
				<div>
					<h3><?= $checkGuild['guildName'] ?> (<?= $checkGuild['world'] ?>)</h3>
				</div>
			<?php } ?>

			<table>

				<tr>
					<th>Icon</th>
					<th>Rang</th>
					<th>Pseudo</th>
					<th>Points GC</th>
				</tr>

				<?php foreach ($results as $player) { ?>
					<!-- PLAYER START -->
					<tr>
						<td><img src="<?= $player->img ?>" style="width: 20px;"></td>
						<td><?= $player->rank ?></td>
						<td><a href="http://fr.finalfantasyxiv.com/<?= $player->url ?>" target="_blank"><?= $player->pseudo ?></a></td>
						<td><?= $player->score ?></td>
					</tr>
					<!-- PLAYER END -->
				<?php } ?>
				
			</table>
		</div>
		<!-- PLAYER LIST END -->

	<?php } else { ?>

		<div id="playerList">
			<p>No Datas</p>
		</div>

	<?php } ?>	

<?php 
} 
catch (Exception $e) 
{
    echo $e->getMessage();
    exit();
}
