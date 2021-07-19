<?php
const CLIENT_ID = "client_606c5bfe886e14.91787997";
const CLIENT_SECRET = "2ce690b11c94aca36d9ec493d9121f9dbd5c96a5";
const FBCLIENT_ID = "313096147158775";
const FBCLIENT_SECRET = "c4ac86c990ffd48b3322d3734ec4ed1a";


function getUser($params)
{
    $result = file_get_contents("http://oauth-server:8081/token?"
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
    $result = file_get_contents("http://oauth-server:8081/api", false, $context);
    $user = json_decode($result, true);
    var_dump($user);
}
function getFbUser($params)
{
    $result = file_get_contents("https://graph.facebook.com/oauth/access_token?"
        . "redirect_uri=https://localhost/fb-success"
        . "&client_id=" . FBCLIENT_ID
        . "&client_secret=" . FBCLIENT_SECRET
        . "&" . http_build_query($params));
    $token = json_decode($result, true)["access_token"];
    // GET USER by TOKEN
    $context = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' => "Authorization: Bearer " . $token
        ]
    ]);
    $result = file_get_contents("https://graph.facebook.com/me", false, $context);
    $user = json_decode($result, true);
    var_dump($user);
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
    case '/auth-code':
        // Gérer le workflow "authorization_code" jusqu'à afficher les données utilisateurs
        echo '<h1>Login with Auth-Code</h1>';
        echo "<a href='http://localhost:8081/auth?"
            . "response_type=code"
            . "&client_id=" . CLIENT_ID
            . "&scope=basic&state=dsdsfsfds'>Login with oauth-server</a>";
        echo "<a href='https://facebook.com/v2.10/dialog/oauth?"
            . "response_type=code"
            . "&client_id=" . FBCLIENT_ID
            . "&redirect_uri=https://localhost/fb-success"
            . "&scope=email&state=dsdsfsfds'>Login with facebook</a>";
        break;
    case '/success':
        // GET CODE
        ["code" => $code, "state" => $state] = $_GET;
        // ECHANGE CODE => TOKEN
        getUser([
            "grant_type" => "authorization_code",
            "code" => $code
        ]);
        break;
    case '/fb-success':
        // GET CODE
        ["code" => $code, "state" => $state] = $_GET;
        // ECHANGE CODE => TOKEN
        getFbUser([
            "grant_type" => "authorization_code",
            "code" => $code
        ]);
        break;
    case '/error':
        ["state" => $state] = $_GET;
        echo "Auth request with state {$state} has been declined";
        break;
    case '/password':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            ['username' => $username, 'password' => $password] = $_POST;
            getUser([
                "grant_type" => "password",
                "username" => $username,
                "password" => $password,
            ]);
        } else {
            // Gérer le workflow "password" jusqu'à afficher les données utilisateurs
            echo "<form method='post'>";
            echo "Username <input name='username'>";
            echo "Password <input name='password'>";
            echo "<input type='submit' value='Submit'>";
            echo "</form>";
        }
        break;
    default:
        echo 'not_found';
        break;
}

// $sdk = new OauthSDK([
//         [
//             "name" => "Facebook",
//             "client_id" => "363716535469209",
//             "client_secret" => "d41d939c08c108c28bc935d53baedbe0"
//         ],
//         [
//             "name" => "Github",
//             "client_id" => "Iv1.08788a9b13edb91a",
//             "client_secret" => "564d4b5d930d60b450a503bb2b41866f56d61a0d"]
//         ],
//         [
//             "name" => "Google",
//             "client_id" => "324837892217-5daq38h2b02mgds10nk09lftvecbof1n.apps.googleusercontent.com",
//             "client_secret" => "_BD6x1EyHn-H-MzZmPmyiopN"]
//     ]
// );


// if (!isset($_GET["code"])) {
//     $links = $sdk->getLinks();
//     foreach ($links as $key => $link){
//         echo "<a href='".$link."'>".$key."</a><br>";
//     }
// } else {
//     var_dump($sdk->getUserData());
// }


//$sdk = new OauthSDK([
//    "github" => [
//        "app_id",
//        "app_secret"
//    ],
//    "oauth-server" => [
//        "app_id",
//        "app_secret"
//    ]
//    ]);
//
//() => [
//    "facebook" => "https://",
//    "oauth-server" => "http://localhost:8081/auth"
//]z
//
//$token = $sdk->handleCallback();
//$sdk->getUser();
// return [
//     "firstname"=>$facebookUSer["firstname"],
//     "lastname"=>$facebookUSer["lastname"],
//     "email"=>$facebookUSer["email"],
//     "phone" =>$facebookUSer["phone_number"]
// ];
