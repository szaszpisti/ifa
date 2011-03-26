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

# if (isset($_SESSION['valid']) && $_SESSION['valid']) { return 0; }

function redirect($uri = '') {
    if ($uri==='') $uri = $_SERVER['REQUEST_URI'];
    header ("Location: $uri");
}

// ellenőrzi, hogy adott típus, id esetén létezik-e az userid
function get_user($tip, $id) {
    global $db;
    if ($tip == 'admin') $tip='diak';
    if (($tip != 'tanar') && ($tip != 'diak')) return (NULL);

    try { $res = $db->query("SELECT * FROM $tip WHERE id=$id");
    } catch (PDOException $e) { echo $e->getMessage(); }
    $user = $res->fetch(PDO::FETCH_ASSOC);

    $user['nev'] = $user[$tip[0].'nev']; // 'tnev' vagy 'dnev' az oszlop neve
    return ($user);
}

// Ha tip, id jött a REQUEST-ben, akkor azt vesszük figyelembe,
// egyébként a SESSION-változót.

if (isset($_REQUEST['tip'])) $tip = $_REQUEST['tip'];
elseif (isset($_SESSION['tip'])) $tip = $_SESSION['tip'];
else unset($tip);

if (isset($_REQUEST['id'])) $id = $_REQUEST['id'];
elseif (isset($_SESSION['id'])) $id = $_SESSION['id'];
else unset($id);

#$tip = isset($_REQUEST['tip'])?$_REQUEST['tip']:$_SESSION['tip'];
#$id  = isset($_REQUEST['id'])?$_REQUEST['id']:$_SESSION['id'];
if (!isset($id)) { $tip = 'admin'; $id = 0; }

$user = get_user($tip, $id);
if (!isset($user)) $hiba = "Nincs ilyen felhasználó!";

if (!isset($_SESSION['admin']) && ($tip == 'diak') && (!$FA->valid)) {
    header ("Content-Type: text/html; charset=utf-8");
    print "<h3>Nincs bejelentkezési időszak!</h3>\n"
        . "<h3>Fogadóóra időpontja: " . $FA->datum_str . "</h3>"
        . "<b>" . $FA->valid_kezd_str . "</b> &nbsp; és &nbsp; <b>"
        . $FA->valid_veg_str . "</b> &nbsp; között lehet bejelentkezni.\n";
    exit;
}

if (isset($_SESSION['valid'])) {

    // ha kaptunk id-et, akkor vsz. új identitás kell
    if (isset($_REQUEST['tip']) && isset($_REQUEST['id'])) {

        // admin automatikusan megkapja, regisztráljuk a sessionbe.
        if (isset($_SESSION['admin']) && get_user($tip, $id)) {
            $_SESSION['tip'] = $tip;
            $_SESSION['id']  = $id;
        }
        // Ha változott, akkor újrakezdjük a bejelentkezést
        elseif (($_SESSION['tip'] !== $tip) || ($_SESSION['id'] !== $id)) {
            $_SESSION['valid'] = false;
            redirect();
        }
    }
}

elseif ( (isset($_POST['jelszo'])) && (strlen($_POST['jelszo']) > 0) ) {
    $jo = false;
    switch ($tip) {
        case 'tanar':
            switch ($tanar_auth) {
                case 'PAM':
                    $jo = (isset($user) && (pam_auth($user['emil'], $_POST['jelszo'], &$error)));
                    break;
                case 'DB':
                    $jo = (isset($user) && (md5($_POST['jelszo']) == $user['jelszo']));
                    break;
                case 'LDAP':
                    $dn = preg_replace ('/#USER#/', $user['emil'], $ldap['base']);
//                    $dn = 'uid=' . $user['emil'] . ',' . $ldap['base'];
                    if($connect = ldap_connect($ldap['host'])) {
                        ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, $ldap['version']);
                        $jo = @ldap_bind($connect, $dn, $_POST['jelszo']);
                        @ldap_unbind ($connect);
                    }
                    break;
            }
            break;

        case 'diak':
            $jo = (isset($user) && (md5($_POST['jelszo']) == $user['jelszo']));
            break;

        case 'admin':
            $jo = (isset($user) && (md5($_POST['jelszo']) == $user['jelszo']));
            if ($jo) $_SESSION['admin'] = true;
            break;
    }
    if ($jo) {
        $_SESSION['tip']   = $tip;
        $_SESSION['id']    = $id;
        $_SESSION['nev']   = $user['nev'];
        $_SESSION['valid'] = true;
    }
    if (isset($_SESSION['valid'])) ulog ($user['id'], $user['nev'] . " bejelentkezett.");
    elseif (!isset($hiba)) $hiba = "Érvénytelen bejelentkezés ($tip, $id)!";
}

if (!isset($_SESSION['valid'])) {
    session_destroy();

    head("Fogadóóra - " . $user['nev'], ' onLoad="document.login.jelszo.focus()"');

    print "<table width=\"100%\"><tr><td>\n";
    if (isset($hiba)) { hiba($hiba); }
    print "\n<h3>" . $user['nev'] . ($tip=='diak'?' ('.$user['onev'].')':'') . "</h3>\n"
        . "<form name=login action='" . $_SERVER['REQUEST_URI'] . "' method=post>\n"
        . "  Jelszó: <input type=password size=8 name=jelszo>\n"
        . "  <input type=hidden name=id value=" . $id . ">\n"
        . "  <input type=hidden name=tip value=" . $tip . ">\n"
        . "  <input type=submit value='Belépés'>\n"
        . "</form>\n\n"
        . "<td align=right valign=top><a href=\"leiras.html\"> Leírás </a>\n</table>\n";
    tail();
    exit;
}

session_write_close();

?>
