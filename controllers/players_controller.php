<?php 
class PlayersController extends AppController
{
	var $name = 'Players';
	var $scaffold;
	function beforeFilter()
	{
		// allow people that aren't logged in to view these pages
		$this->Auth->allow('index','view');
	}
}
?>