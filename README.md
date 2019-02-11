# ffparser

A PHP script to pull and recover "Final Fantasy 14 online" Top Weekly Players. Update each monday at 10AM.
Pull weekly top players scoreboards, order by Grand Company points, on the official website of the game.
Datas are stored in SQL database, with Json format.

// Author
* Name : FunkyToc 
* Website : http://funkycoding.fr 
* FfParser : http://funkycoding.fr/ffparser 
* Update : 2019/02/11 

// Project
* Url : http://fr.finalfantasyxiv.com 
* Target : http://fr.finalfantasyxiv.com/lodestone/ranking/gc/weekly/ 
* Frequency : 1 / week 
* Pulldate : every Monday, at 11AM 
* Pulltime : ~1H / 1800 pages 
* Requires : PHP 5.6+, PDO $db, MySQL 
* Maintain : $worlds (the servers list)

Better to c*r*onfigure hook / cron to use this script automaticly !


// Versions

* V1.2.0 : update with 2019's new html.
* V1.1.0 : add front view, input search pseudo.
* V1.0.0 : init, php web crawler, SQL stock.
