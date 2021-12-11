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
 * @file
 *
 * Általános függvények: adatbázis kapcsolódás, fej- és lábléc, logolás,
 * időpont-átszámító, az űrlapokhoz konstans vezérlőelemek stb.
 */

require_once('ifa.ini.php');
# Valahonnan megpróbáljuk a tcpdf-et betölteni:
@include_once('tcpdf/tcpdf.php'); // vagy csomagból
@include_once('vendor/autoload.php'); // vagy composerből

/** Ha van tcpdf, akkor azt használjuk, egyébként az xls-t */
if (class_exists('TCPDF')) {
    define('__TABLE__', 'fogado-pdf.php');
} else {
    define('__TABLE__', 'fogado-xls.cgi');
}

if (php_sapi_name() == "cli") {
    define('__DEBUG__', true);
} else {
    define('__DEBUG__', false);
}

if (__DEBUG__) {
    define('ADMIN', true);
} else {
#    session_start();
    // Ha "admin", azt a session-ban tároltuk, itt visszaolvassuk.
    if (isset($_SESSION['admin']) && $_SESSION['admin'] == true ) { define ('ADMIN', true); }
    else { define('ADMIN', false); }
}

$yaml = yaml_parse_file('ifa.yaml');
define('CLIENT_ID', $yaml['CLIENT_ID']);
define('CLIENT_SECRET', $yaml['CLIENT_SECRET']);

define('URI', 'https://'
     . $_SERVER['SERVER_NAME']
     . $_SERVER['SCRIPT_NAME']);

define('URIQ', URI . '?' . $_SERVER['QUERY_STRING']);

set_include_path(get_include_path() . ':./classes');

try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $db->exec('PRAGMA foreign_keys = true;');
} catch (PDOException $e) { hiba($e->getMessage()); }

// először megkeressük az aktuális időpontot
require_once('fogadoora.class.php');
//! Az aktuális fogadóóra
$FA = new Fogadoora();

// Ha még nincs semmi bejegyezve, csak az admin tud újat létrehozni
if (!isset($FA)) require_once('login.php');

/**
 * Az aktuális fogadóóra bejegyzés azonosítója
 */
define('fid', $FA->id);

/**
 * A paraméterként kapott hibaszöveget kiírja, majd logolja
 *
 * @param string $err A kiírandó hibaüzenet
 * @param boot $utf Kell-e utf-8 headert küldeni?                                                                               
 */
function hiba($err) {
    Ulog (0, $err);
    return "<p><hr><b>!!! - $err - !!!</b><hr>\n";
}

//! az ini fájlban található default fogadóidők átszámolása
$FogadoIdo = array (
    TimeToFive($Fogado_tartam[0], $Fogado_tartam[1]),
    TimeToFive($Fogado_tartam[2], $Fogado_tartam[3])
);

//! az ini fájlban található default szülői értekezlet idők átszámolása
$SzuloiIdo = array (
    TimeToFive($Szuloi_tartam[0], $Szuloi_tartam[1]),
    TimeToFive($Szuloi_tartam[2], $Szuloi_tartam[3])
);

/**
 * Idő átszámítása 5 perces sorszámúról HH:MM formátumra
 *
 * @param int $ido Az átszámítandó idő (5 perces)
 * @return string
 */
function FiveToString($ido) { return sprintf("%02d:%02d", floor($ido/12), ($ido%12)*5); } // volt: tim

/**
 * Idő átszámítása 5 perces sorszámúról (óra, perc) formátumú tömbbé.
 *
 * @param int $ido Az átszámítandó idő (5 perces)
 * @return array
 */
function FiveToTime($ido) { return array ('ora' => floor($ido/12), 'perc' => ($ido%12)*5); }

/**
 * (óra, perc) formájú tömb átszámítása 5 perces sorszámúra.
 *
 * @return int
 * @param int $ora Az idő órája
 * @param int $perc Az idő perce
 */
function TimeToFive($ora, $perc) { return $ora*12+floor($perc/5); }

/**
 * $SELECT: az óra választó konstans string
 */
$SELECT = "  <select name=#NAME#>\n";
for ($i=$Kiir_tartam[0]; $i<=$Kiir_tartam[1]; $i++) {
    $SELECT .= "    <option value='" . $i*12 . "'>$i\n";
}
$SELECT .= "  </select>\n";


/**
 * Kezdő/záró időpont választáshoz a listbox elkészítése, visszaadja HTML stringben.
 *
 * @param string $name_ora az óra select objektum neve az űrlapon
 * @param string $name_perc a perc select objektum neve az űrlapon
 * @param int $ido a jelölendő időpont (five)
*/
function SelectIdo($name_ora, $name_perc, $ido){
    global $SELECT;
    $ora = 12*floor($ido/12);
    $perc = $ido - $ora;

    $ret = preg_replace("/value='$ora'>/", "value='$ora' selected>", $SELECT);
    $ret = preg_replace("/#NAME#/", "'$name_ora'", $ret);

    $ret .= preg_replace("/value='$perc'>/", "value='$perc' selected>",
                "  <select name='$name_perc'>\n    <option value='0'>00\n    <option value='2'>10\n    <option value='4'>20" .
                "\n    <option value='6'>30\n    <option value='8'>40\n    <option value='10'>50\n  </select>\n");
    return $ret;
}

/**
 * Az időtartam választáshoz a listbox elkészítése, visszaadja HTML stringben.
 *
 * @return string
 * @param string $name a HTML tag "name" értéke
 */
function SelectTartam($name, $selected=2) {
    $select =
          "  <select name=#NAME#>\n"
        . "    <option value='1'>5\n"
        . "    <option value='2'>10\n"
        . "    <option value='3'>15\n"
        . "    <option value='4'>20\n"
        . "  </select>\n";
    $select = preg_replace("/#NAME#/", "'$name'", $select);
    $select = preg_replace("/'$selected'/", "'$selected' selected", $select);
    return $select;
}


/**
 * Elkészíti a HTML fejlécet
 *
 * @param string $cimsor A TITLE tagben megjelenő szöveg
 * @param string $onload A BODY tag kiegészítői - ha kell
 * @param string $css A használandó stíluslap neve kiterjesztés nélkül
 */
function Head($cimsor, $onload='', $css='default') {
    $time = time();
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate, post-c heck=0, pre-check=0');
    header('Expires: Mon,26 Jul 1980 05:00:00 GMT');

print <<< EnD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>$cimsor</title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Expires" content="Tue, 20 Aug 1996 14:25:27 GMT">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Language" content="hu">
  <link rel="stylesheet" href="$css.css?$time" type="text/css">
  <script type="text/javascript" src="js/ifa.js"></script>
</head>
EnD;
print "\n\n<body$onload>\n";
}

/**
 * Kiírja a HTML láblécet.
 *
 */
function Tail() {
    print "\n<div class='noprint'>\n";
    print "<p><hr><img src='dugo.png' align='top' alt='dugo@szepi_PONT_hu'>\n";
    print "</div>\n";
    print "</body>\n</html>\n";
}

/**
 * Az Ulog táblába beírja a kapott szöveget
 *
 * @param int $uid A módosítást végző felhasználó-azonosító (ADMIN-nál 0)
 * @param string $s a bejegyzendő szöveg
 */
function ulog($uid, $s) {
    if (__DEBUG__) return 0;
    global $db;
    $res = $db->prepare("INSERT INTO Ulog (ido, uid, host, log) VALUES (?, ?, ?, ?)");
    $res->execute(array(date("Y-m-d H:i:s"), ADMIN?0:$uid, $_SERVER['REMOTE_ADDR'], $s));
}

/**
 * A tanar_id-hez tartozó linket keresi elő.
 *
 * @param int $tanar_id - a tanár azonosítója
 * @param string $felirat - ez lesz a link szövege
 *
 * @return string - az összeállított HTML link, ha van meet, egyébként a felirat
 */
function get_meet($tanar_id, $felirat) {
    global $db;
    $res = $db->prepare("SELECT * FROM tanar WHERE id=?");
    $res->execute(array($tanar_id));
    $tanar = $res->fetch(PDO::FETCH_ASSOC);

    if($tanar['meet'] != "" && ONLINE) {
        return "<a href='" . $tanar['meet'] . "'>$felirat</a>";
    } else {
        return "<span class='halvany'>$felirat</span>";
    }
}
/**
 * Kiírja a diák összesítő táblázatát.
 *
 * @param user $user - a felhasználó adatai
 * @param FA $FA - az aktuális fogadóóra
 * @param db $db - az adatbázis-leíró
 *
 * @return string - az összeállított HTML táblázat
 */
function osszesit() {
    global $user, $FA, $db;
    # a szülői értekezlet eleje és vége
    $res = $db->prepare(
                  "SELECT MIN(ido) AS eleje, MAX(ido) AS vege"
                . "  FROM Fogado"
                . "    WHERE fid=?"
                . "        AND tanar=?"
                . "        AND diak=-2"
                );
    $res->execute(array(fid, $user->ofo));
    $szuloi = $res->fetch(PDO::FETCH_ASSOC);

    $SzuloiEleje  = 0;
    $SzuloiSor = '';
    if (isset($szuloi['eleje'])) {
        $SzuloiSor = "<b>" . FiveToString($szuloi['eleje'])
            . "-" . FiveToString($szuloi['vege']+1)
            . " &ndash; " . get_meet($user->ofo, 'szülői értekezlet') . "</b>";
        $SzuloiEleje = $szuloi['eleje'];
    }

    $res = $db->prepare(
                  "SELECT ido, tnev, tanar"
                . "  FROM Fogado, Tanar"
                . "    WHERE fid=?"
                . "        AND Tanar.id=tanar"
                . "        AND diak=?"
                . "      ORDER BY ido"
                );
    $res->execute(array(fid, $user->id));
    $rows = $res->fetchAll(PDO::FETCH_ASSOC);

    $SzuloiKesz = false;
    foreach ($rows as $row) {
        if (!$SzuloiKesz && $SzuloiEleje < $row['ido']) {
            $Output[] = $SzuloiSor;
            $SzuloiKesz = true;
        }
        $Output[] = FiveToString($row['ido']) . " &ndash; " . get_meet($row['tanar'], $row['tnev']);
    }
    if (!$SzuloiKesz) { $Output[] = $SzuloiSor; }

    return $user->fejlec() . join ("\n<br>", $Output) . "\n";
}

function get_osztaly($oszt) {
    $now = new DateTime('-180 days');
    $tanev = $now->format('Y');
    preg_match('/^d(\d\d)(\w$)/', $oszt, $m);
    $vegzes = intval($m[1]);
    $ab = strtoupper($m[2]);
    $evfolyam = $tanev - 2000 - $vegzes + 13;
    return "$evfolyam. $ab";
}

function leiras() {
    $ret = file_get_contents('leiras.html');
    if (ONLINE) {
        $ret = str_replace('<div id="leiras">', file_get_contents('leiras-online.html'), $ret);
    }
    return $ret;
}

function get($key) {
    $val = '';
    if (isset($_SESSION[$key])) {
        $val = $_SESSION[$key];
    } elseif (isset($_REQEST[$key])) {
        $val = $_REQEST[$key];
    }
    return "$key=$val";
}

function get_link($url, $text) {
    return "<a href='$url'>$text</a>";
}

