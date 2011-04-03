<?php
   if ($session->check('Message.flash')): $session->flash(); endif; // this line displays our flash messages
   echo $content_for_layout;
?>
<div id="mast">
    <?php echo $session->read('Auth.User.first_name'); ?>
    <?php echo $html->link('Logout', array('controller' => 'users', 'action' => 'logout')); ?>
</div>