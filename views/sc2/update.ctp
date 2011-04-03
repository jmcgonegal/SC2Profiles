<p>Found <?php echo $np;?> new players</p>
<p><strong>Executed in <?php echo $time;?> seconds</strong></p>
<?php if($result) { ?>
<script type="text/JavaScript">
if (document.addEventListener) {
	document.addEventListener("DOMContentLoaded", init, false);
}
window.onload = init;

function init() {
	if (arguments.callee.done) return;
	arguments.callee.done = true;

	setTimeout("location.reload(true);",5000);
}

</script>
<?php } ?>