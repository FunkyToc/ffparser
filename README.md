# ffparser

A PHP script to pull and recover "Final Fantasy 14 online" Top Weekly Players. Update each monday at 10AM.
Pull weekly top players scoreboards, order by Grand Company points, on the official website of the game.
Datas are stored in SQL database, with Json format.

// Author
* Name : FunkyToc 
* Website : http://funkycoding.fr 
* FfParser : http://funkycoding.fr/ffparser 
* Update : 2018/03/07 

// Project
* Url : http://fr.finalfantasyxiv.com 
* Target : http://fr.finalfantasyxiv.com/lodestone/ranking/gc/weekly/ 
* Frequency : 1 / week 
* Pulldate : every Monday, at 11AM 
* Pulltime : ~1H / 1800 pages 
 *Requires : PHP 5.6+, PDO $db, MySQL 
* Maintain : $worlds 

Better to c*r*onfigure hook / cron to use this script automaticly !
