<?php
const CLIENT_ID = "client_60a3778e70ef02.05413444";
const CLIENT_SECRET = "cd989e9a4b572963e23fe39dc14c22bbceda0e60";
const STATE = "fdzefzefze";
function handleLogin()
{
    // http://.../auth?response_type=code&client_id=...&scope=...&state=...
    echo "<h1>Login with OAUTH</h1>";
    echo "<a href='http://localhost:8081/auth?response_type=code"
        . "&client_id=" . CLIENT_ID
        . "&scope=basic"
        . "&state=" . STATE . "'>Se connecter avec Oauth Server</a>";
}

function handleError()
{
    ["state" => $state] = $_GET;
    echo "{$state} : Request cancelled";
}

function handleSuccess()
{
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) throw new RuntimeException("{$state} : invalid state");
    // https://auth-server/token?grant_type=authorization_code&code=...&client_id=..&client_secret=...
    $url = "http://oauth-server:8081/token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_ID . "&client_secret=" . CLIENT_SECRET;
    $result = file_get_contents($url);
    $result = json_decode($result, true);
    $token = $result['access_token'];

    $apiUrl = "http://oauth-server:8081/me";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer '.$token
        ]
    ]);
    echo file_get_contents($apiUrl, false, $context);
}

/**
 * AUTH CODE WORKFLOW
 * => Generate link (/login)
 * => Get Code (/auth-success)
 * => Exchange Code <> Token (/auth-success)
 * => Exchange Token <> User info (/auth-success)
 */
$route = strtok($_SERVER["REQUEST_URI"], "?");
switch ($route) {
    case '/login':
        handleLogin();
        break;
    case '/auth-success':
        handleSuccess();
        break;
    case '/auth-cancel':
        handleError();
        break;
    default:
        http_response_code(404);
        break;
}
