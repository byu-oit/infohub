<?php
	$this->Html->css('secondary', null, array('inline' => false));
?>

<div id="loginBody" class="deskBg">
	<div id="loginBox">
		<h1 class="headerTab">Login</h1>
		<div id="lbLower" class="whiteBox">
			<p>Login credential will be your BYU Net ID</p>
			<input type="text" class="inputShade" placeholder="Net ID">
			<input type="text" class="inputShade" placeholder="Password">
			<input type="submit" class="inputButton" value="Login">
			<a href="https://y.byu.edu/ae/prod/authentication/cgi/findNetId.cgi">Forgot your Net ID or password?</a><br>
			<a href="https://y.byu.edu/ae/prod/person/cgi/createNetId.cgi">Create a Net ID?</a>
		</div>
	</div>
</div>
