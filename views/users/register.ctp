<?php
$session->flash('auth');
echo $form->create('User', array('action' => 'register'));
echo $form->inputs(array(
	//'legend' => __('Login', true),
	'username',
	'password'
));
echo $form->end('Register');
?>
