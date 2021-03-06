<?php 

/* 
 * Final Fantasy 14 Online Parser
 * Recover guild's members with guild ID.
 *
 * Frequency : 2 hours
 * Pulldate : on demand
 * Pulltime : ~30 seconds
 * Requires : PHP 5.6+, \PDO $db, MySQL
 * Maintain : $worlds (the servers list), xpath queries (html targets)
 *
 * Better use hook / cron to use this script automaticly !
 * 
 */

try 
{
    // ENV
    ignore_user_abort(true);
    set_time_limit(120);
    include_once('config.php');

    function getGuildMember(string $guildID)
    {
        // VARS
        global $db;
        $guildID = isset($guildID) && !empty(trim($guildID)) ? trim($guildID) : die('missing guild ID'); // exemple : '9235053248388270324';

        // PARSER
        $pulledResult = [];
        $results = [];

        // DB last update check
        $sql = $db->prepare('SELECT id, pulldate FROM ffparser_guild WHERE guildID = :guildID LIMIT 1');
        $sql->bindValue(':guildID', $guildID, PDO::PARAM_STR);
        $sql->execute();
        $checkGuild = $sql->fetch();

        // Prevent Spam
        if (empty($checkGuild['id']) || (strtotime($checkGuild['pulldate']) + $guildHoursDelay) < time() ) {

            // Guild info
            libxml_use_internal_errors(true); // Hide the notice of HTML fetch DOM
            $html       = new DOMDocument();
            $html->loadHtmlFile('https://fr.finalfantasyxiv.com/lodestone/freecompany/'. $guildID .'/member/');
            $xpath      = new DOMXPath($html);

            // Init members
            $results['members'] = '';

            // get city
            $results['city']    = trim(strip_tags($xpath->query("//div[@class='ldst__window']/div[@class='entry']/a/div[@class='entry__freecompany__box']/p[@class='entry__freecompany__gc']")->item(0)->nodeValue));

            // get world
            $results['guild']   = trim($xpath->query("//div[@class='ldst__window']/div[@class='entry']/a/div[@class='entry__freecompany__box']/p[@class='entry__freecompany__name']")->item(0)->nodeValue);

            // get guild name
            $results['world']   = trim($xpath->query("//div[@class='ldst__window']/div[@class='entry']/a/div[@class='entry__freecompany__box']/p[@class='entry__freecompany__gc']")->item(1)->nodeValue);

            // Foreach pages of members (11-)
            for ($i = 0; $i < 10; $i++) {

                // Main fetch
                libxml_use_internal_errors(true); // Hide the notice of HTML fetch DOM
                $html       = new DOMDocument();
                $html->loadHtmlFile('https://fr.finalfantasyxiv.com/lodestone/freecompany/'. $guildID .'/member/?page='. ($i+1));
                $xpath      = new DOMXPath($html);
                $players    = $xpath->query("//div[@class='ldst__window']/ul/li[@class='entry']");

                // Break 
                if (empty($players->length)) {
                    break;
                }

                foreach ($players as $player) {

                    // Foreach $player html content, set a new DOMXPath
                    $player_file = new DOMDocument();
                    $cloned = $player->cloneNode(TRUE);
                    $player_file->appendChild($player_file->importNode($cloned, True));
                    $player_doc = new DOMXPath($player_file);

                    // get pseudo
                    $name_tag = $player_doc->query("//p[@class='entry__name']");
                    $results['members'] .= trim($name_tag->item(0)->nodeValue) .',';
                }

                // Keep turning ON the PDO connection (PDO connection close itself after 30s of inactivity)
                $sql = $db->prepare('SELECT id FROM ffparser_guild WHERE 1 LIMIT 1');
                $sql->execute();
            }

            // Push results (update or insert)
            if (!empty($results)) {
                $results['members'] = rtrim($results['members'], ',');

                if (!empty($checkGuild['id'])) {
                    // Update world
                    $sql = $db->prepare('UPDATE ffparser_guild SET pulldate = NOW(), world = :world, city = :city, guildName = :guildName, guildID = :guildID, members = :members WHERE world = :world LIMIT 1');
                    $sql->bindValue(':world', $results['world'], PDO::PARAM_STR);
                    $sql->bindValue(':city', $results['city'], PDO::PARAM_STR);
                    $sql->bindValue(':guildID', $guildID, PDO::PARAM_STR);
                    $sql->bindValue(':guildName', $results['guild'], PDO::PARAM_STR);
                    $sql->bindValue(':members', $results['members'], PDO::PARAM_STR);
                    $sql->execute();

                } else {
                    // New world
                    $sql = $db->prepare('INSERT INTO ffparser_guild (pulldate, world, city, guildName, guildID, members) VALUES (NOW(), :world, :city, :guildName, :guildID, :members)');
                    $sql->bindValue(':world', $results['world'], PDO::PARAM_STR);
                    $sql->bindValue(':city', $results['city'], PDO::PARAM_STR);
                    $sql->bindValue(':guildID', $guildID, PDO::PARAM_STR);
                    $sql->bindValue(':guildName', $results['guild'], PDO::PARAM_STR);
                    $sql->bindValue(':members', $results['members'], PDO::PARAM_STR);
                    $sql->execute();
                }

                return true;
            }
        } 

        return false;
    }

}
catch (Exception $e) 
{
    echo $e->getMessage();
    exit();
}
