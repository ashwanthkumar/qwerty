<?php
	
	require_once("config.php");
	require_once("lib/limonade.php");
	require_once("functions.php");

	dispatch("/", 'actualApp');
	dispatch("/login", 'loginApp');
	dispatch("/app", 'actualApp');
	dispatch("/bus/search", "searchBuses");
	dispatch_post("/bus/travel", 'persionalizeBusLayout');
	dispatch_post("/bus/travel/book/:travel_id", 'bookTravelBook');
	dispatch("/user/login", 'userLogin');
	dispatch("/user/join/linkedIn", 'linkedInAdded');
	dispatch("/user/join/facebook", 'facebookAdded');

	run();


function actualApp() {
	return render('index.html');
}

function loginApp() {
	if(isset($_SESSION['already_user_id'])){
		return header("Location: ./?user_id=" . $_SESSION['already_user_id']);
	} else {
		return render("login.html");
	}
}