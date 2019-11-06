<?php

include_once __DIR__ . '/vendor/autoload.php';

function oauth() {
    global $user;

    $out = '';
    define('TITLE', 'IFA - Iskolai Fogadóóra Adminisztráció');
    define('CLIENT_ID', '852761388240-1e9b5a72o8g70esa14ljqsg8mrld3n21.apps.googleusercontent.com');
    define('CLIENT_SECRET', 'ywgzXeQ21HrRtKrnJdMTtLvy');

    $client = new Google_Client();
    $client->setApplicationName(TITLE);
    $client->setClientId(CLIENT_ID);
    $client->setClientSecret(CLIENT_SECRET);
    $client->setRedirectUri(URI);
    $client->setPrompt('select_account consent');
    $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
    $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);

    if (isset($_REQUEST['kilep'])) {
        # Úgy látszik, ide sosem jut a vezérlés
        unset($_SESSION['access_token']);
        $_SESSION['logged_in'] = FALSE;
        $client->revokeToken();
        header('Location: https://gmail.com');
        header('Location: ' . filter_var(URI, FILTER_SANITIZE_URL));
    }

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $service = new Google_Service_Oauth2($client);
        $googleuser = $service->userinfo->get();
        if ($googleuser->email == $user->emil) {
            $_SESSION['access_token'] = $token;
            $_SESSION['logged_in'] = TRUE;
            Ulog($user->id, $user->nev . ' (' . $user->emil . ') bejelentkezett.');
        }
        header('Location: ' . URI . '?oszt=t&tip=tanar&id=' . $user->id);
    }

    if (empty($_SESSION['access_token'])) {
        $authUrl = $client->createAuthUrl();
        $out .= "  <div class='request'>\n"
        . "    <form action='$authUrl' method='POST'>\n"
        . "      <h3>" . $user->nev . "</h3>\n"
        . "      <input type='submit' value='Bejelentkezés a @szeged.piarista.hu azonosítóval' />\n"
        . "    </form>\n"
        . "  </div>\n";
    } else {
        $client->setAccessToken($_SESSION['access_token']);
        $out .= ""
        . "  <div class='request'>\n"
        . "    <form method='POST'>\n"
        . "      <input type='submit' name='logout' value='Logout' />\n"
        . "    </form>\n"
        . "  </div>\n";
    }

    return $out;
}

// Ha (pl. tesztelési célból) csak ezt a oldalt használjuk
if (preg_match('/oauth.php/', $_SERVER['SCRIPT_NAME'])) {
    session_start();
    define('URI', 'https://ifa.szepi.hu/h/oauth.php');
#    print_r(array(URI));
#        print '<hr>$user (oauth.php): '; print_r($user);
        #header('Location: ' . filter_var(URI, FILTER_SANITIZE_URL));
    print oauth();
}
