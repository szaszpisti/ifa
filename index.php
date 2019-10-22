<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<base target="_top">
<head>
  <title>Fogadóóra</title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Content-Language" content="hu">
  <link rel="stylesheet" href="default.css" type="text/css">
</head>

<body>
<?php

session_start();

require_once('ifa.inc.php');
require_once('user.class.php');

require_once('osztaly.php');
require_once('admin.php');
require_once('fogado.php');
require_once('tanar.php');

$user = new User($_REQUEST);

$baseurl = $_SERVER['SCRIPT_URI'];

# Ha a linkeket nem GET-tel hanem POST-tal akarom:
# https://stackoverflow.com/a/426417

print "<div id='osztaly' class='noprint'>\n";
print osztaly() . "\n";
print "</div>\n";

print "<div id='duma-container'>\n";
print "<div id='duma'>\n";

if (array_key_exists('kilep', $_REQUEST)) {
    $user->logout();
}

if ($user->logged_in())
{
    if (array_key_exists('leiras', $_REQUEST)) {
        print leiras();
    }
    if (array_key_exists('tablazat', $_REQUEST)) {
        print tablazat();
    }
    elseif (array_key_exists('osszesit', $_REQUEST)) {
        print osszesit();
    }
    else {
        switch ($user->tip)
        {
            case "tanar":
                print tanar();
                break;
            case "diak":
                print fogado();
                break;
            case "admin":
                print admin();
                break;
        }
    }
    print $user->menu();

}

// Ha van jelszó input, akkor ellenőrizzük
elseif (isset($_REQUEST['jelszo']))
{
    $user->login($_REQUEST['jelszo']);
}

// Ha legalább típus van, akkor kirakjuk a bejelentkező ablakot
elseif (isset($_REQUEST['tip']) && isset($_REQUEST['id']))
{
    print '<br>' . "login_form (" . $_REQUEST['tip'] . ")" . '<br>';
    print $user->login_form() . '<br>';
}

else
{
    print leiras();
}

Tail();

