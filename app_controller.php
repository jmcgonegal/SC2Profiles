<?php
class AppController extends Controller {
    var $components = array('Auth','Session');
	//var $helpers = array('Session');
    function beforeFilter() {
        //Configure AuthComponent
        //$this->Auth->authorize = 'actions';
        $this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
        $this->Auth->logoutRedirect = array('controller' => 'users', 'action' => 'login');
        //$this->Auth->loginRedirect = array('controller' => 'posts', 'action' => 'add');
    }
	function flash($message, $path)
	{
		$this->Session->setFlash($message);
		$this->redirect($path);
	}
}
?>
