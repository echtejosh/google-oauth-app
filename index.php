<?php

/*
|--------------------------------------------------------------------------
| Introduction
|--------------------------------------------------------------------------
|
| This application is made using our personal Framework. This Framework
| contains every tooling that makes a solid application abiding by PSR
| convention. Have fun
|
|--------------------------------------------------------------------------
*/

use Framework\Foundation\Application;
use Framework\Foundation\Config;
use Framework\Foundation\Container;
use Framework\Foundation\Session;
use Framework\Http\HeaderBag;
use Framework\Http\Kernel;
use Framework\Support\Url;

require 'vendor/autoload.php';
require_once 'autoload.php';
require_once 'Framework/helpers.php';
require_once 'routes/web.php';

Session::start();

$app = new Application(__DIR__);

Config::set_many(
    [
        'app' => include base_path('/config/app.php'),
        'smtp' => include base_path('/config/smtp.php')
    ]
);

global $client;


$client = new Google_Client();

$jsonfile = file_get_contents(__DIR__ . '/client_secret.json');
$json = json_decode($jsonfile, true);

try {
    $client->setAuthConfig($json);
} catch (\Google\Exception $e) {
}


$client->setRedirectUri(url('/'));
$client->addScope(['openid', 'profile', 'email']);

if (isset($_GET['code'])) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $revokecode = $_GET['code'];

    if (isset($accessToken['error'])) {
        unset($_GET['code']);
        header('Location: index.php');
    }

    $_SESSION['access_token'] = $accessToken;

    $googleOAuthService = new Google_Service_Oauth2($client);

    try {
        $_SESSION['user_account'] = $googleOAuthService->userinfo->get();
    } catch (\Google\Service\Exception $e) {
        throw new Error('Unable to retrieve userinfo');
    }
} else {
    if (!isset($_SESSION['user_account'])) {
        $authUrl = $client->createAuthUrl();
        $headers = new HeaderBag(getallheaders());

        $redirect = redirect(filter_var($authUrl, FILTER_SANITIZE_URL));

        foreach ($headers->all() as $header => $header_body) {
            $redirect->with_header($header, $header_body);
        }

        $redirect->send();
        $revokecode = $_GET['code'];
        exit();
    }
}

app(Kernel::class)->handle(request());