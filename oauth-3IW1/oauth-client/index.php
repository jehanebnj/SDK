<?php

$route = strtok($_SERVER['REQUEST_URI'], '?');
switch ($route) {
    case '/auth-code':
        // Gérer le workflow "authorization_code" jusqu'à afficher les données utilisateurs
        echo "auth-code";
        break;
    case '/password':
        // Gérer le workflow "password" jusqu'à afficher les données utilisateurs
        echo "password";
        break;
    default:
        echo 'not_found';
        break;
}
