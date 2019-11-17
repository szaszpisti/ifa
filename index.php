<?php

session_start();

require_once('ifa.inc.php');
require_once('user.class.php');

require_once('osztaly.php');
require_once('admin.php');
require_once('fogado.php');
require_once('tanar.php');

$user = new User($_REQUEST);

Head('Fogadóóra - Szegedi Piaristák');

if (isset($_GET['code'])) {
    oauth();
}
// Ha van jelszó input, akkor ellenőrizzük
if (isset($_REQUEST['jelszo'])) {
    $user->login($_REQUEST['jelszo']);
}

# Ha a linkeket nem GET-tel hanem POST-tal akarom:
# https://stackoverflow.com/a/426417

print "<div id='osztaly' class='noprint'>\n";
print osztaly() . "\n";
print "</div>\n";

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

// Ha legalább típus van, akkor kirakjuk a bejelentkező ablakot
elseif (isset($_REQUEST['tip']) && isset($_REQUEST['id']))
{
    print $user->login_form() . '<br>';
}

else
{
    print leiras();
}

print "</div><!-- duma -->\n";
print "<div class='spacer'></div>\n";

Tail();

