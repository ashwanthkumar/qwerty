<?php

error_reporting(E_ALL);

function addLinkedInSkills($skills, $userId) {
	$db = $GLOBALS['db'];
	foreach ($skills['values'] as $skill) {
		$insert_data = array(
			"idskills" => $skill['id'],
			"name" => $skill['skill']['name']
		);
		$db->insert("skills", $insert_data);

		// User Connections
		$userConnectionData = array(
			"users_idusers" => $userId,
			"skills_idskills" => $skill['id']
		);
		$db->insert("users_has_skills", $userConnectionData);
	}
}

function linkedInAdded() {
	$li = $GLOBALS['li'];
	$db = $GLOBALS['db'];

	$token = $li->getAccessToken($_REQUEST['code']); 
	$token_expires = time() + $li->getAccessTokenExpiration();

	$info = $li->get('/people/~:(id,first-name,last-name,three-current-positions,three-past-positions,skills,educations,email-address)');
	// Check for the user using the email
	$email = $info['emailAddress'];
	$usersFromDb = $db->select("users", "email = :email OR linked_in = :lid", array(":email" => $email, ":lid" => $info['id']));
	$userId = NULL;
	if(count($usersFromDb) > 0) {
		$userId = $usersFromDb[0]['idusers'];
		$update_payload = array(
			"linked_in" => $info['id'],
			"linked_in_expiry" => $token_expires
		);
		$db->update("users", $update_payload, "email = :email", array(":email" => $email));
	} else {
		$insert_payload = array(
			"email" => $email,
			"name" => $info['firstName'] . " " . $info['lastName'],
			"linked_in" => $info['id'],
			"linked_in_expiry" => $token_expires
		);
		$db->insert("users", $insert_payload);
		$userId = $db->lastInsertId();
	}

	addLinkedInSkills($info['skills'], $userId);
	$_SESSION['already_user_id'] = $userId;
	return header("Location: /qwerty/app?user_id=$userId&type=linkedin");
}

function addFacebookLikes($likes, $component, $userId) {
	$db = $GLOBALS['db'];

	// Insert the facebook likes data
	foreach ($likes as $like) {
		$like_data = array(
			"id$component" => $like['id'],
			"name" => $like['name']
		);
		$db->insert($component, $like_data);

		$connection_data = array(
			"users_idusers" => $userId,
			"$component"."_id$component" => $like['id']
		);
		$db->insert("users_has_$component", $connection_data);

	}
}

function facebookAdded() {
	$db = $GLOBALS['db'];
	$facebook = $GLOBALS['fb'];
	$user = $facebook->api("/me");

	// Check for the user using the email
	$email = $user['email'];
	// print_r($user);
	// halt("Testing email");
	$usersFromDb = $db->select("users", "email = :email OR og_id = :og_id", array(":email" => $email, ":og_id" => $user['id']));
	$userId = NULL;
	if(count($usersFromDb) > 0) {
		$userId = $usersFromDb[0]['idusers'];

		$update_payload = array(
			"og_id" => $facebook->getAccessToken()
		);
		$db->update("users", $update_payload, "email = :email", array(":email" => $email));
	} else {
		$insert_payload = array(
			"email" => $email,
			"name" => $user['name'],
			"og_id" => $user['id']
		);
		$db->insert("users", $insert_payload);
		$userId = $db->lastInsertId();
	}

	$music = $facebook->api("/me/music");
	addFacebookLikes($music['data'], "music", $userId);

	$movies = $facebook->api("/me/movies");
	addFacebookLikes($movies['data'], "movies", $userId);

	$books = $facebook->api("/me/books");
	addFacebookLikes($books['data'], "books", $userId);
	$_SESSION['already_user_id'] = $userId;
	return header("Location: /qwerty/app?user_id=$userId&type=facebook");
}

function userLogin() {
	$type = $_GET['login_type'];

	$linkedIn = $GLOBALS['li'];
	$facebook = $GLOBALS['fb'];

	if($type === "facebook") {
		$loginUrl = $facebook->getLoginUrl(array(
			"redirect_uri" => APP_BASE_DOMAIN . "/user/join/facebook",
			"scope" => "user_likes,email"));
		header("Location: {$loginUrl}");
	} else if($type === "linkedin") {
		$loginUrl = $linkedIn->getLoginUrl(array(LinkedIn::SCOPE_FULL_PROFILE, LinkedIn::SCOPE_EMAIL_ADDRESS));
		header("Location: {$loginUrl}");
	} else {
		// Something fundamentally wrong
		halt("Something fundamentally went wrong here!");
	}
}

function searchBuses() {
	$from = $_GET['from'];
	$to = $_GET['to'];
	$date_of_travel = $_GET['date'];

	$bus = $GLOBALS['bus'];
	$busses = $bus->searchBuses($from, $to, $date_of_travel);
	
	$response = array();
	foreach ($busses->data->onwardflights as $bus) {
		$rsp = array();
		$rsp['origin'] = $bus->origin;
		$rsp['destination'] = $bus->destination;
		$rsp['travels'] = $bus->TravelsName;
		$rsp['bus_type'] = $bus->BusType;
		$rsp['fare'] = $bus->fare->totalfare;
		$rsp['departure_time'] = $bus->DepartureTime;
		$rsp['bus_id'] = $bus->skey;

		$response[] = $rsp;
	}

	return json($response);
}

function getBusLayoutForTravelId() {}

function persionalizeBusLayout() {
	/* Major Working Algorithm */

	$db = $GLOBALS['db'];

	$userId = $_POST['user_id'];
	$bus_id = $_POST["bus_id"];
	$source = $_POST['from'];
	$destination = $_POST['to'];
	$travel_date = date('Y-m-d H:i:s', strtotime($_POST['date']));

	// Create a new Itinery (if not already present) or 
	// get the existing itinery's ID
	$itinerary_id = createNewItineryIfNotFound($bus_id, $travel_date, $source, $destination);

	$response = array("travel_id" => $itinerary_id);
	$responseData = array();
	foreach (getUsersTravellingIn($itinerary_id) as $user_travelling) {
		$travellingUserId = $user_travelling['users_idusers'];
		$seatNumber = $user_travelling['seat_number'];

		$matches = calculateSimilarity($userId, $travellingUserId);

		$rsp['seat_number'] = $seatNumber;
		$rsp['matches'] = $matches;

		$responseData[] = $rsp;
	}
	$response["data"] = $responseData;

	return json($response);
}

function getUsersTravellingIn($itinerary_id) {
	$db = $GLOBALS['db'];
	return $db->select("itinerary_has_users", "itinerary_iditinerary = :itinerary_id", array(":itinerary_id" => $itinerary_id));
}

function createNewItineryIfNotFound($bus_id, $departure_time, $source, $destination) {
	$db = $GLOBALS['db'];

	$existingItinary = $db->select("itinerary", "source_name = :source AND destination_name = :destination 
		AND travel_date = :travel_date", 
		array(":source" => $source, ":destination" => $destination, ":travel_date" => $departure_time));

	if(count($existingItinary) > 0) {
		// Already present, get the id
		return $existingItinary[0]['iditinerary'];
	} else {
		// Create a new one
		$insert_data = array("source_name" => $source, 
			"destination_name" => $destination, 
			"travel_date" => $departure_time,
			"bus_id" => $bus_id);

		$db->insert("itinerary", $insert_data);
		return $db->lastInsertId();
	}
}

function calculateSimilarity($userId, $matchingUser) {
	$db = $GLOBALS['db'];
	$matchedSkills = $db->run("SELECT * FROM `users_has_skills` m1, `users_has_skills` m2 WHERE m1.skills_idskills = m2.skills_idskills AND m1.users_idusers != m2.users_idusers and m1.users_idusers != :user_id AND m2.users_idusers = :matching_user", array(":user_id" => $userId, ":matching_user" => $matchingUser));
	$matchedBooks = $db->run("SELECT * FROM `users_has_books` m1, `users_has_books` m2 WHERE m1.books_idbooks = m2.books_idbooks AND m1.users_idusers != m2.users_idusers and m1.users_idusers != :user-id AND m2.users_idusers = :matching_user", array(":user_id" => $userId, ":matching_user" => $matchingUser));
	$matchedMusic = $db->run("SELECT * FROM `users_has_music` m1, `users_has_music` m2 WHERE m1.music_idmusic = m2.music_idmusic AND m1.users_idusers != m2.users_idusers and m1.users_idusers != :user_id AND m2.users_idusers = :matching_user", array(":user_id" => $userId, ":matching_user" => $matchingUser));
	$matchedMovies = $db->run("SELECT * FROM `users_has_movies` m1, `users_has_movies` m2 WHERE m1.movies_idmovies = m2.movies_idmovies AND m1.users_idusers != m2.users_idusers and m1.users_idusers != :user_id AND m2.users_idusers = :matching_user", array(":user_id" => $userId, ":matching_user" => $matchingUser));

	$totalMatches = count($matchedSkills) + count($matchedBooks) + count($matchedMusic) + count($matchedMovies);
	$matchingMatchers = array();
	$matchingMatchers['skills'] = round(count($matchedSkills) / $totalMatches) * 100;
	$matchingMatchers['books'] = round(count($matchedBooks) / $totalMatches) * 100;
	$matchingMatchers['music'] = round(count($matchedMusic) / $totalMatches) * 100;
	$matchingMatchers['movies'] = round(count($matchedMovies) / $totalMatches) * 100;

	return $matchingMatchers;
}

function bookTravelBook() {
	$travelId = params("travel_id");
	$user_id = $_GET['user_id'];
	$seat_number = $_GET['seat_number'];

	$db = $GLOBALS['db'];

	$insert_data = array(
		"users_idusers" => $user_id,
		"itinerary_iditinerary" => $travelId,
		"seat_number" => $seat_number
	);

	$db->insert("itinerary_has_users", $insert_data);
	return json(array("success" => true));
}
