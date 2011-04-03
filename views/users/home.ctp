<p>Hello <?php e($User['first_name'] . ' ' . $User['last_name']) ?></p>
<p>Your last login was on <?php e($User['last_login']) ?></p>
<?php echo $html->link('Logout', '/users/logout'); ?>