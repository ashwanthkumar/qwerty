<?php
	
	require_once("config.php");
	require_once("lib/limonade.php");
	require_once("functions.php");
/bus/travel
	dispatch("/", 'homePageCheck');
	dispatch("/login", 'loginApp');
	dispatch("/app", 'actualApp');
	dispatch("/bus/search", "searchBuses");
	dispatch_post("/bus/travel", 'persionalizeBusLayout');
	dispatch("/bus/travel/book/:travel_id", 'bookTravelBook');
	dispatch("/user/login", 'userLogin');
	dispatch("/user/logout", 'userLogout');
	dispatch("/user/join/linkedIn", 'linkedInAdded');
	dispatch("/user/join/facebook", 'facebookAdded');

	run();


function homePageCheck() {
	if(!isset($_SESSION['already_user_id'])){
		return header("Location: ./login");
	}

	return header("Location: ./app?user_id=" . $_SESSION['already_user_id']);
}

function actualApp() {
	return render('index.html');
}

function loginApp() {
	if(isset($_SESSION['already_user_id'])){
		return header("Location: ./app?user_id=" . $_SESSION['already_user_id']);
	} else {
		return render("login.html");
	}
}
