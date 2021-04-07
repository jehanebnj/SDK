<?php

function read_file($filename)
{
    if (!file_exists($filename)) {
        throw new InvalidArgumentException("{$filename} not found");
    }
    $data = file($filename);
    return array_map(fn ($item) => unserialize($item), $data);
}

function write_file($data, $filename)
{
    if (!file_exists($filename)) {
        throw new InvalidArgumentException("{$filename} not found");
    }
    $data = array_map(fn ($item) => serialize($item), $data);
    return file_put_contents($filename, implode(PHP_EOL, $data));
}

function findApp($criteria) {
    $apps = read_file('./data/app.data');
    $results = array_values(
        array_filter(
            $apps,
            fn ($item) => count(array_intersect_assoc($item, $criteria)) === count($criteria)
        )
    );

    return count($results) === 1 ? $results[0] : null;
}

function register()
{
    ["name" => $name] = $_POST;

    if (findApp(["name" => $name])!== null) throw new InvalidArgumentException("{$name} already registered");
    
    $clientID = uniqid("client_", true);
    $clientSecret = sha1($clientID);
    
    $apps = read_file('./data/app.data');
    $apps[] = array_merge(
        ["client_id" => $clientID, "client_secret" => $clientSecret],
        $_POST
    );
    write_file($apps, "./data/app.data");
    http_response_code(201);
    echo json_encode(["client_id" => $clientID, "client_secret" => $clientSecret]);
}

function auth()
{
    ["client_id" => $clientID, "state" => $state, "scope"=> $scope] = $_GET;
    if (null === ($app = findApp(["client_id" => $clientID]))) throw new RuntimeException("{$clientID} not exists");
    if (wasAppAuthorized($clientID)) return handleAuth(true);
    
    echo "<div>{$app['name']} - <a href=\"{$app['uri']}\">Website</a><br>";
    echo "{$scope}<br>";
    echo "<a href=\"/auth-success?state={$state}&client_id={$clientID}\">Oui</a>";
    echo "&nbsp;<a href=\"/auth-failed?state={$state}&client_id={$clientID}\">Non</a>";
    echo "</div>";
}

function wasAppAuthorized($clientID) {
    $codes = read_file('./data/code.data');
    foreach($codes as $code) {
        if ($code["client_id"] === $clientID) return true;
    }
    return false;
}

function handleAuth($success)
{
    ["state" => $state, "client_id" => $clientID] = $_GET;
    
    if (null === ($app = findApp(["client_id" => $clientID]))) throw new RuntimeException("{$clientID} not exists");

    $queryParams = ["state" => $state];
    if ($success) {
        $code = uniqid();
        $queryParams["code"] = $code;
        $codes = read_file("./data/code.data");
        $codes[] = [
            "code" => $code,
            "expires_in" => (new DateTimeImmutable())->modify("+5 minutes"),
            "client_id" => $clientID,
            "user_id" => uniqid()
        ];
        write_file($codes, "./data/code.data");
    }
    $redirectUrl = $app[$success ? "redirect_success" : "redirect_error"];
    $redirectUrl .= "?" . http_build_query($queryParams);
    //header("Location: {$redirectUrl}");
    echo("Location: {$redirectUrl}");
}

$route = strtok($_SERVER["REQUEST_URI"], "?");
switch ($route) {
    case '/register':
        register();
        break;
    //    /auth?response_type=code&client_id=...&scope=...&state=...
    case '/auth':
        auth();
        break;
    case '/auth-success':
        handleAuth(true);
        break;
    case '/auth-failed':
        handleAuth(false);
    break;
    default:
        http_response_code(404);
    break;
}
