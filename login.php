<?php
/*
 *   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Szász Imre.
 *
 *   Ez egy szabad szoftver; terjeszthető illetve módosítható a GNU
 *   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
 *   későbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

require_once('ifa.inc.php');

@session_start();

if (isset($_REQUEST['kilep']) ) {
    session_destroy();
    redirect('leiras.html');
}

function redirect($uri = '') {
    if ($uri==='') $uri = $_SERVER['REQUEST_URI'];
    header ("Location: $uri");
}

// ellenőrzi, hogy adott típus, id esetén létezik-e az userid
// paraméterként pl. a _REQUEST vagy a _SESSION tömböt várja
function get_user($param) {
    global $db;
    if ( !isset($param['tip']) || !isset($param['id']) ) return false;           // valami nincs megadva
    $tip = $param['tip'];
    $id = $param['id'];
    if (!preg_match('/^[0-9]{1,10}$/', $id)) return false;              // nem jó az id
    if (!in_array($tip, array('admin', 'tanar', 'diak'))) return false; // nem jó a típus
    if ($tip == 'admin') $tip='diak';

    try {
        $user = $db->query("SELECT * FROM $tip WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        if (!isset($user['id'])) return false; // nincs találat
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    if ($tip == 'tanar') $user['onev'] = '';

    $user['nev'] = $user[$tip[0].'nev']; // 'tnev' vagy 'dnev' az oszlop neve
    $user['tip'] = $param['tip'];
    return ($user);
}

function login($user, $hiba=NULL) {
    session_destroy();

    head("Fogadóóra - " . $user['nev'], ' onLoad="document.login.jelszo.focus()"');

    if (isset($hiba)) hiba($hiba);

    print "<table width=\"100%\"><tr><td>\n";
    print "\n<h3>" . $user['nev'] . ($user['tip']=='diak'?' ('.$user['onev'].')':'') . "</h3>\n"
        . "<form name=\"login\" action=\"" . $_SERVER['REQUEST_URI'] . "\" method=\"post\">\n"
        . "  Jelszó: <input type=\"password\" size=\"8\" name=\"jelszo\">\n"
        . "  <input type=\"hidden\" name=\"id\" value=\"" . $user['id'] . "\">\n"
        . "  <input type=\"hidden\" name=\"tip\" value=\"" . $user['tip'] . "\">\n"
        . "  <input type=\"submit\" value=\"Belépés\">\n"
        . "</form>\n\n"
        . "<td align=\"right\" valign=\"top\"><a href=\"leiras.html\"> Leírás </a>\n</table>\n";
    tail();
    exit;
}

function timeout() {
    global $FA, $user;
    header ("Content-Type: text/html; charset=utf-8");
    print "<h3>Nincs bejelentkezési időszak!</h3>\n"
        . "<h3>Fogadóóra időpontja: " . $FA->datum_str . "</h3>"
        . "<b>" . $FA->valid_kezd_str . "</b> &nbsp; és &nbsp; <b>"
        . $FA->valid_veg_str . "</b> &nbsp; között lehet bejelentkezni.\n";
    tail();
    Ulog ($user['tip']." ".$user['id'], "Nincs bejelentkezési időszak!");
    @session_destroy();
    exit;
}

$user = get_user($_REQUEST);
if (!ADMIN && !isset($user)) redirect('leiras.html');

if (($user['tip'] == 'diak') && !isset($_SESSION['admin']) && (!$FA->valid)) timeout();

// Ha jelszót kaptunk, mindenképpen ellenőrizni kell.
if ( isset($_POST['jelszo']) ) {
    $_POST['jelszo'] = stripslashes($_POST['jelszo']);
    $jo = false;
    switch ($user['tip']) {
        case 'tanar':
            switch ($tanar_auth) {
                case 'PAM':
                    $jo = (pam_auth($user['emil'], $_POST['jelszo'], $error));
                    break;
                case 'DB':
                    $jo = (md5($_POST['jelszo']) == $user['jelszo']);
                    break;
                case 'LDAP':
                    if($connect = ldap_connect($ldap['host'])) {
                        ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, $ldap['version']);
                        $filter = "(uid=".$user['emil'].")";
                        $result = ldap_search($connect, $ldap['base'], $filter);
                        $entries = ldap_get_entries($connect, $result);
                        if ($entries['count'] != 1) { break; }
                        $dn = $entries[0]['dn'];
                        $jo = @ldap_bind($connect, $dn, $_POST['jelszo']);
                        @ldap_unbind ($connect);
                    }

                    break;
            }
            break;

        case 'diak':
            $jo = (md5($_POST['jelszo']) == $user['jelszo']);
            break;

        case 'admin':
            $jo = (md5($_POST['jelszo']) == $user['jelszo']);
            if ($jo) $_SESSION['admin'] = true;
            break;
    }
    if ($jo) {
        $_SESSION['tip']   = $user['tip'];
        $_SESSION['id']    = $user['id'];
        $_SESSION['nev']   = $user['nev'];
        ulog ($user['id'], $user['nev'] . " bejelentkezett.");
    }
    else { login ($user, "Érvénytelen bejelentkezés (".($user['tip'].", ".$user['id']).")!"); }
}

if ($user) {
    if (ADMIN) {
        // Az admin felveszi az identitást.
        $_SESSION['tip'] = $user['tip'];
        $_SESSION['id'] = $user['id'];
    } elseif ( get_user($_SESSION) == $user ) {
        // Nem-admin próbálkozhat a saját identitásával; semmit nem kell csinálni.
    } else {
        // Egyébként login a kért azonosítóval.
        login($user);
    }
} elseif (!ADMIN && ( !get_user($_SESSION) || !$user ) ) {
    // Ha _REQUEST-ben és _SESSION-ben sincs rendes user
    @session_destroy();
    redirect('leiras.html');
    exit;
}

session_write_close();

?>
