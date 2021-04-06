<?php

function read_file($file)
{
    if (!file_exists($file)) {
        throw new \Exception("{$file} not exists");
    }
    $data = file($file);
    return array_map(fn ($item) => unserialize($item), $data);
}

function write_file($data, $file) {
    $data = array_map(fn($item) => serialize($item), $data);
    return file_put_contents($file, implode(PHP_EOL, $data));
}

function register()
{
    [
        "name" => $name
    ] = $_POST;

    if (findApp(["name" => $name]) !== null) throw new InvalidArgumentException("{$name} already registered");
   
    $clientID = uniqid('client_', true);
    $clientSecret = sha1($clientID);
    $apps = read_file('./data/app.data');
    $apps[] = array_merge(
        [ "client_id" => $clientID, "client_secret" => $clientSecret ], 
        $_POST
    );
    write_file($apps, "./data/app.data");

    http_response_code(201);
    header("Content-Type: application/json");
    echo json_encode([
        "client_id" => $clientID, "client_secret" => $clientSecret
    ]);
}

function findApp($criteria) {
    $apps = read_file("./data/app.data");
    $results = array_values(
        array_filter($apps, function($item) use ($criteria) {
            return count(array_intersect_assoc($criteria, $item)) === count($criteria);
        })
    );

    return count($results) === 1 ? $results[0] : null;
}

function auth() {
    ["client_id" => $clientID, "scope" => $scope] = $_GET;
    $app = findApp(["client_id" => $clientID]);
    if (!$app) throw new RuntimeException("{$clientID} not found");
    http_response_code(200);
    echo "{$app['client_id']}/{$app['name']} {$app['uri']}";
    echo $scope;
    echo "<a href='/auth-Oui?client_id={$app['client_id']}&state={$_GET['state']}'>Oui</a>";
    echo "<a href='/auth-Non?client_id={$app['client_id']}&state={$_GET['state']}'>Non</a>";
}

function handleAuth($success) {
    ["client_id" => $clientID, "state" => $state] = $_GET;
    $app = findApp(["client_id" => $clientID]);
    if (!$app) throw new RuntimeException("{$clientID} not found");

    $urlRedirect = $success ? $app["redirect_success"] : $app["redirect_error"];
    $queryParams = [
        "state" => $state
    ];
    if ($success) {
        $code = uniqid('code_');
        $codes = read_file("./data/code.data");
        $codes[] = [
            "code" => $code,
            "client_id" => $clientID,
            "user_id" => uniqid('user_', true),
            "expired_in" => (new \DateTimeImmutable())->modify('+5 minutes')
        ];
        write_file($codes, './data/code.data');
        $queryParams['code'] = $code;
    }
    //header("Location: {$urlRedirect}?" . http_build_query($queryParams));
    echo("Location: {$urlRedirect}?" . http_build_query($queryParams));
}


$route = strtok($_SERVER['REQUEST_URI'], '?');
switch ($route) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    case '/auth-Oui':
        handleAuth(true);
        break;
    case '/auth-Non':
        handleAuth(false);
        break;
    default:
        echo 'not_found';
        break;
}
