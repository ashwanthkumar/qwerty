<?php
	
	require_once("config.php");
	require_once("lib/limonade.php");
	require_once("functions.php");

	dispatch("/", 'actualApp');
	dispatch("/app", 'actualApp');
	dispatch("/bus/search", "searchBuses");
	dispatch("/bus/travel/:travel_id", 'getBusLayoutForTravelId');
	dispatch_post("/bus/travel/:bus_id/personalize", 'persionalizeBusLayout');
	dispatch_post("/bus/travel/book/:travel_id", 'bookTravelBook');
	dispatch("/user/login", 'userLogin');
	dispatch("/user/join/linkedIn", 'linkedInAdded');
	dispatch("/user/join/facebook", 'facebookAdded');

	run();


function actualApp() {
	$additionContent = "";
	if(array_key_exists("type", $_GET)) $additionContent .= "You cam here from " . $_GET['type'];
	if(array_key_exists("userid", $_GET)) $additionContent .= ". UserID => " . $_GET['userid'];
	return "Hello World. $additionContent";
}