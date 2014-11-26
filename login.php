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

/**
 * @file login.php
 *
 * Bejelentkezés
 *
 * A bejelentkezéseket, identitásokat ellenőrző függvények,
 * minden oldalt csak ezen keresztül lehet elérni.
 */

/**
 * Bejelentkezés
 *
 * A bejelentkezéseket, identitásokat ellenőrző függvények,
 * minden oldalt csak ezen keresztül lehet elérni.
 */

require_once('ifa.inc.php');

/**
 * Átirányítás tetszőleges helyre
 *
 * @param string $uri -
 *    Ide irányít át - ha ez üres, akkor helyben marad.
 */
function redirect($uri = '') {
    if ($uri==='') $uri = $_SERVER['REQUEST_URI'];
    header ("Location: $uri");
}

/**
 * Ellenőrzi, hogy adott típus, id esetén létezik-e az userid
 *
 * @param array $param -
 *    paraméterként pl. a _REQUEST vagy a _SESSION tömböt várja, amiben van 'tip' és 'id'
 *
 * @return 
 *   - FALSE - ha nem talált megfelelő usert
 *   - különben: ('nev': nev, 'tip': tip, 'id': id)
 */
function get_user($param) {
    global $db;
    if ( !isset($param['tip']) || !isset($param['id']) ) return false;  // valami nincs megadva
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
    $user['id'] = $param['id'];
    return ($user);
}

/**
 * Kirakja a bejelentkező (jelszókérő) ablakot.
 *
 * @param array $user - Az *id*, amihez kérjük a jelszót.
 * @param string $hiba - Ha van kiírandó hiba, itt lehet megadni.
 */
function login($user, $hiba=NULL) {
    global $FA;
    session_destroy();

    head("Fogadóóra - " . $user['nev'], ' onLoad="document.login.jelszo.focus()"');

    if (isset($hiba)) hiba($hiba);

    if ($user['tip'] == 'diak' && !$FA->valid) {
        print "<h2 style='color: red;'>Nincs feliratkozási időszak!</h2>\n"
            . "<h3>Fogadóóra időpontja: " . $FA->datum_str . "\n"
            . "<br><span class=\"kicsi\">" . $FA->valid_kezd_str . "</b> &nbsp; és &nbsp; <b>"
            . $FA->valid_veg_str . "</b> &nbsp; között lehet feliratkozni.</span>\n<hr>\n";
    }

    print "<table width=\"100%\"><tr><td>\n";
    print "\n<h3>" . $user['nev'] . ($user['tip']=='diak'?' ('.$user['onev'].')':'') . "</h3>\n"
        . "<form name=\"login\" action=\"" . $_SERVER['REQUEST_URI'] . "\" method=\"post\">\n"
        . "  Jelszó: <input type=\"password\" size=\"8\" name=\"jelszo\">\n"
        . "  <input type=\"hidden\" name=\"id\" value=\"" . $user['id'] . "\">\n"
        . "  <input type=\"hidden\" name=\"tip\" value=\"" . $user['tip'] . "\">\n"
        . "  <input type=\"submit\" value=\"Belépés\">\n"
        . "</form>\n\n"
        . "<td align=\"right\" valign=\"top\" class=\"sans\"><a href=\"leiras.html\"> Leírás </a>\n</table>\n";
    tail();
    exit;
}

@session_start();

// Ha kilépett, a leírást mutatjuk.
if (isset($_REQUEST['kilep']) ) {
    session_destroy();
    redirect('leiras.html');
}

//! @brief valami
$user = get_user($_REQUEST);

// Csak az admin teheti meg, hogy '$user' nélkül lépjen be
if (!ADMIN && !isset($user)) redirect('leiras.html');

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
                    if ((strlen(trim($_POST['jelszo'])) != 0) && $connect = ldap_connect($ldap['host'])) {
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
    else { login ($user, "Érvénytelen bejelentkezés!"); }
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
