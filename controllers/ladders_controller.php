<?php 
class LaddersController extends AppController
{
	var $name = 'Ladders';
	var $scaffold;
	function beforeFilter()
	{
		// allow people that aren't logged in to view these pages
		$this->Auth->allow('index','view');
	}
}
?>