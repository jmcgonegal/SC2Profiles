<?php
include("snoopy.class.php");
class Sc2Controller extends AppController {
var $uses = array('Player','Ladder');
var $new_players = 0;
	function beforeFilter()
	{
		// allow people that aren't logged in to view these pages
		$this->Auth->allow('index','view');
		$this->Auth->allow('update','view');
		$this->Auth->allow('ladder_update','view');
	}
	function ladder_update() 
	{
		// jump through the ladders on record and check for new players
		$ladders = $this->Ladder->query("SELECT * FROM `player_ladders`, `ladders` AS Ladder WHERE `player_ladders`.ladder_id = Ladder.id AND Ladder.type = 0 AND Ladder.division = 5  GROUP BY ladder_id"); // and race = 0
		foreach($ladders as $ladder)
		{
			unset($ladder['player_ladders']);
			
			$r = $this->_parseLadder($ladder['Ladder']['url']);
			$this->Ladder->save($r);
		}
		// this is a cronjob, exit out
		exit;
	}
	function update()
	{
		$time_start = microtime(true);
		echo '<h1>Script started</h1>';
		$this->Player->unbindModel(array('hasAndBelongsToMany' => array('Ladder')));
		$result = $this->Player->find('all', array('conditions' => array('Player.achivements' => null, 'Player.disabled' => 0),'limit' => 100));
		
		foreach($result as $player)
		{
			// construct profile url
			$profile_url = "http://us.battle.net/sc2/en/profile/".$player['Player']['id'].'/1/'.$player['Player']['name']."/ladder/leagues";

			$xml = $this->_getUrl($profile_url);
			if($xml != false) {
			
			$profile = $xml->body[0]->xpath("//div[@id='profile-header']");
			$race = $xml->body[0]->xpath("//a[@href='ladder/']");

			$user_code = ereg_replace("[^0-9]","", (string)$profile[0]->h2[0]->a[0]->span[0]);//substr($profile[0]->h2[0]->a[0]->span[0],1); // remove the first character of #882
			$achivement_score = (string) $profile[0]->h3[0];
			$ladders = $xml->body[0]->xpath("//div[@id='profile-left']/ul/li");
			$ladder_url = false;
			unset($xml);
			$player['Player']['bnet_code'] = (int) $user_code;
			$player['Player']['achivements'] = (int) $achivement_score;

			foreach($ladders as $ladder)
			{
				if(strcmp(trim($ladder->a[0]),'Back') != 0)
				{
					$arr = $ladder->a[0]->attributes();
					$ladder_url = current(explode('#',$arr['href'])); // ladder url

					$ladder_id = (int) end(explode('/',$ladder_url));

					$result = $this->Ladder->findById($ladder_id);
					if(!$result && $ladder_id != 0)
					{
						$r = $this->_parseLadder("http://us.battle.net".$ladder_url);
						$this->Ladder->save($r);
					}
				}
			}

			$this->Player->save($player);			
		}
		else {
			//echo '<p>skipped: <strong>'.$player['Player']['name'].'</strong></p>';
			$player['Player']['disabled'] = 1;
			$this->Player->save($player);
		}
}
		$np = $this->new_players;
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		$this->set('np',$np);
		$this->set('time',$time);
	}
	function _getUrl($url) 
	{
		$snoopy = new Snoopy;
		
		// todo: need a function to get the cookie in the future
		$snoopy->cookies["perm"] = '1';
		$snoopy->cookies["int-SC2"] = '1';
		$snoopy->cookies["__utma"]='134253166.407194031.1280999669.1280999669.1280999669.1';
		$snoopy->cookies["__utmb"]='134253166.1.10.1280999669';
		$snoopy->cookies["__utmc"]='134253166';
		$snoopy->cookies["__utmz"]='134253166.1280999669.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)';
		//$snoopy->rawheaders["Set-Cookie"] = 'perm=1; Domain=battle.net; Path=/ int-SC2=1; Domain=.battle.net; Path=/';
		/* Set-Cookie	perm=1; Domain=battle.net; Path=/ int-SC2=1; Domain=.battle.net; Path=/
		*/

		// fake our agent
		$snoopy->agent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8";
		if($snoopy->fetch($url)){ 
			$tidy_config = array(
				'add-xml-decl' => true,
				//'quote-nbsp', false,
				'clean' => false,
				'input-xml' => true,
				'output-xml' => true,
				'show-body-only' => false,
				'wrap' => 0,
				'quote-marks' => false
		); 

		/* we have to remove the namespace or xpath will fuck up
		 * lets also tidy up the code
		 */
		if(!strpos($snoopy->response_code, '200 OK'))
		{
			echo $snoopy->response_code."<br />";
			return false;
		}
		else {
			$tidy = tidy_parse_string(str_replace('xmlns=', 'ns=', $snoopy->results), $tidy_config, 'UTF8');
			
			unset($snoopy);
			
			// load into simplexmlelement
			return simplexml_load_string($tidy);
		}
		}
		else {
			print "<?xml version='1.0' standalone='yes'?>";
			print "<error>";
			print "<response>".$snoopy->response_code."</reponse>";
			print "<headers>";
			while(list($key,$val) = each($snoopy->headers)){
				print "<header>".$key.": ".$val."</header>";
			}
			print "</headers>";
			//		print "<result>".htmlspecialchars($snoopy->results)."</result>\n";
			print "</error>";
	
		}
	}
	function _parseLadder($url)
	{
		$division_map = array('Bronze'=>1,'Silver'=>2,'Gold'=>3,'Platinum'=>4,'Diamond'=>5);
		$ladder_id = (int) end(explode('/',$url));
		$xml = $this->_getUrl($url);
		$table = $xml->body[0]->xpath("//table[@class='data-table']/tr[position()>1]");
		$dn = $xml->body[0]->xpath("//div[@class='data-title']/div");
		$mode = explode("\n",$dn[0]->h3[0]);
		$league_name = trim(substr($mode[1],9)); // Overmind Zeta
		$mode = explode(" ", trim($mode[0]));
		if(sizeof($mode) == 2) { // 2v2 Platium
			$league_type = $mode[0]; // 2v2
			$league_type_division = $mode[1]; // platinum
		}
		else if(sizeof($mode) == 3){ // 2v2 Random Platium
			$league_type = $mode[1].' '.$mode[0]; // random 2v2
			$league_type_division =  $mode[2]; // platinum
		}
		else {
			die('something went terribly wrong');
		}
		echo "<h3>league name: ".$league_name.' type: '.$league_type.' division: '.$league_type_division;
		echo "</h3>";
		$type_map = array('1v1'=>0,'Random 2v2'=>1,'Random 3v3'=>2,'Random 4v4'=>3,'2v2'=>4,'3v3'=>5,'4v4'=>6);
		$ladder = array('Ladder'=>array(
			'id' => $ladder_id, 
			'name' => $league_name,
			'division' => $division_map[$league_type_division],
			'type' => $type_map[$league_type],
			'url' => $url
		));

		foreach($table as $row)
		{
			$t = (string)trim($row->td[2]->div[0]);
			$arr = preg_split("/\s/", $t);
			//$highest_rank = substr($t,0,1);
			//$previous_rank = substr($t,3,1);
			if(sizeof($arr) != 5)
			{
			echo '<pre>';
			print_r($arr);
			echo '</pre>';
			exit;
			}
			$highest_rank = $arr[0];
			$previous_rank = $arr[2];
			
			$race = $arr[4];
			
			$pi = $row->td[2]->div->attributes();
			$player_code = end(explode('-',$pi['id'])); // player-info-99999 returns 99999
			$player_friend_code = '';
			$divison_rank = ereg_replace("[^0-9]","", (string)$row->td[1]);
			$player_name = (string)$row->td[2]->a;
			$points = (string)$row->td[count($row->td)-3];
			$win = (string)$row->td[count($row->td)-2];
			$loss = (string)$row->td[count($row->td)-1];
			$total_games = $win + $loss;
			$player_url = 'http://us.battle.net/sc2/en/profile/'.$player_code.'/1/'.$player_name.'/';
			$out = '<p>Rank: '.$divison_rank.
				' Name: <a href="'.$player_url.'">'.
				$player_name.
				'</a>'.
				' ID: '. $player_code.
				' Race: '. $race.
				' Wins: '. $win.
				' Losses: '. $loss.
				' Win%: '. (round($win/($win+$loss),4)*100).
				'</p>';
			//echo $out;
			
			$race_map = array('Terran'=>1,'Protoss'=>2,'Zerg'=>3,'Random'=>4);
			
			
			$player = array('Player' => array(
			'id' => $player_code,
			'name' => $player_name,
			/* all this player data is bullshit and we should get it from the profile page instead */
			'race' => $race_map[$race],
			'wins' => $win,
			'losses' => $loss,
			'points' => $points,
			'division' => $division_map[$league_type_division],
			'division_rank' => $divison_rank,
			'highest_rank' => $highest_rank,
			'previous_rank' => $previous_rank,
			
			));
			$this->new_players++;
			$this->Player->save($player);
			
			$this->Player->query("REPLACE INTO `player_ladders` (`player_id` ,`ladder_id`, `race`, `win`, `loss`, `points`, `rank`, `highest_rank`, `previous_rank`) VALUES ('".$player_code."', '".$ladder_id."', '".$race_map[$race]."', '".$win."', '".$loss."', '".$points."', '".$divison_rank."', '".$highest_rank."', '".$previous_rank."')");
		}
		
		return $ladder;
	}

	function index() {
		// propogate from a single url to start
		$this->_parseLadder("http://us.battle.net/YOUR_PROFILE_LADDER_URL");
		exit;

	}
}
?>