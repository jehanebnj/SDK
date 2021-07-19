<?php

const CLIENT_ID = 'client_60f324e98d1cb3.40889045';
const CLIENT_SECRET = '626fe6480483a000f2073f1a612944aacd6ae230';
const FACEBOOK_CLIENT_ID = '313096147158775';
const FACEBOOK_CLIENT_SECRET = 'c4ac86c990ffd48b3322d3734ec4ed1a';
const GOOGLE_CLIENT_ID = '324837892217-5daq38h2b02mgds10nk09lftvecbof1n.apps.googleusercontent.com';
const GOOGLE_CLIENT_SECRET = '_BD6x1EyHn-H-MzZmPmyiopN';
const DISCORD_CLIENT_ID = '866750937899859968';
const DISCORD_CLIENT_SECRET = '1CSi64JYRxFNXqcDdYYRyUFLNXiqhrK9';
 
$authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';

$tokenURL = 'https://www.googleapis.com/oauth2/v4/token';
$baseURL = 'https://' . $_SERVER['SERVER_NAME'] . '/gg-success';

session_start();

function getUser($params)
{
	$result = file_get_contents("https://oauth-server:8081/token?"
		. "client_id=" . CLIENT_ID
		. "&client_secret=" . CLIENT_SECRET
		. "&" . http_build_query($params));
	$token = json_decode($result, true)["access_token"];
	// GET USER by TOKEN
	$context = stream_context_create([
		'http' => [
			'method' => "GET",
			'header' => "Authorization: Bearer " . $token
		]
	]);
	$result = file_get_contents("https://oauth-server:8081/api", false, $context);
	var_dump(json_decode($result, true));
}

function home()
{
    $authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';
    $baseURL = 'https://' . $_SERVER['SERVER_NAME']
    . '/gg-success';
	$client_id = CLIENT_ID;
	$fACEBOOK_client_id = FACEBOOK_CLIENT_ID;
	$dIScORD_client_id = DISCORD_CLIENT_ID;
    $_SESSION['state'] = bin2hex(random_bytes(16));
    $params = array(
        'response_type' => 'code',
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => $baseURL,
        'scope' => 'openid email profile',
        'state' => $_SESSION['state']
    );
    $google_href = $authorizeURL.'?'.http_build_query($params);

	
	echo '<a href="https://localhost:8081/auth?response_type=code&client_id=${client_id}&scope=basic&state=azerty">oauth-server</a> <br>';
	echo '<a href="https://facebook.com/v11.0/dialog/oauth?response_type=code&client_id=${FACEBOOK_CLIENT_ID}&redirect_uri=https://localhost/fb-success">Provider Facebook</a> <br>';
	echo '<a href="${google_href}">Provider Google</a> <br>';
	echo '<a href="https://discord.com/api/oauth2/authorize?client_id=${dIScORD_client_id}&redirect_uri=https://localhost/dc-success&response_type=code&scope=email%20identify">Provider Discord</a>';
}

function success()
{
	["code" => $code, "state" => $state] = $_GET;

	getUser([
		"grant_type" => "authorization_code",
		"code" => $code
	]);
}

function getFacebookUser()
{
	["code" => $code, "state" => $state] = $_GET;
	$result = file_get_contents("https://graph.facebook.com/oauth/access_token?"
		. "client_id=" . FACEBOOK_CLIENT_ID
		. "&client_secret=" . FACEBOOK_CLIENT_SECRET
		. "&redirect_uri=https://localhost/fb-success"
		. "&grant_type=authorization_code&code=" . $code
	);
	$token = json_decode($result, true)["access_token"];
	$context = stream_context_create([
		"http" => [
			"method" => "GET",
			"header" => "Authorization: Bearer " . $token
		]
	]);
	$result = file_get_contents("https://graph.facebook.com/me?fields=id,name,email", false, $context);
	var_dump(json_decode($result, true));
}

function getGoogleUser() {
    $tokenURL = 'https://www.googleapis.com/oauth2/v4/token';
    $baseURL = 'https://localhost/gg-success';

	$ch = curl_init($tokenURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
		'grant_type' => 'authorization_code',
		'client_id' => GOOGLE_CLIENT_ID,
		'client_secret' => GOOGLE_CLIENT_SECRET,
		'redirect_uri' => $baseURL,
		'code' => $_GET['code']
	]));
	$data = json_decode(curl_exec($ch), true);
	$jwt = explode('.', $data['id_token']);
	$res = json_decode(base64_decode($jwt[1]), true);
	print($res);
}
function getDiscordUser() {
    $tokenURL = 'https://discord.com/api/oauth2/token';
    $baseURL = 'https://' . $_SERVER['SERVER_NAME'] . '/dc-success';
    $userURL = 'https://discord.com/api/users/@me';

    if (isset($_GET['code'])) {
        $ch = curl_init($tokenURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'redirect_uri' => $baseURL,
            'code' => $_GET['code']
        ]));

        $token = json_decode(curl_exec($ch), true)['access_token'];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token"
            ]
        ]);

        $result = file_get_contents($userURL, false, $context);
        $user = json_decode($result, true);
        var_dump($user);
    }
}
/**
 * AUTH_CODE WORKFLOW
 *  => Get CODE
 *  => EXCHANGE CODE => TOKEN
 *  => GET USER by TOKEN
 */
/**
 * PASSWORD WORKFLOW
 * => GET USERNAME/PASSWORD (form)
 * => EXHANGE U/P => TOKEN
 * => GET USER by TOKEN
 */

$route = strtok($_SERVER['REQUEST_URI'], '?');

switch ($route) {
	case '/':
		home();
		break;

	case '/success':
		success();
		break;
		
	case '/fb-success':
		getFacebookUser();
		break;

    case '/gg-success':
        getGoogleUser();
        break;

	case '/dc-success':
		getDiscordUser();
		break;
		
	default:
		echo "not_found";
		break;
}
