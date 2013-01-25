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
 * Általános függvények: adatbázis kapcsolódás, fej- és lábléc, logolás,
 * időpont-átszámító, az űrlapokhoz konstans vezérlőelemek stb.
 */

require_once('ifa.ini.php');

session_start();
if (isset($_SESSION['admin'])) { define ('ADMIN', true); }
else { define('ADMIN', false); }

set_include_path(get_include_path() . ':./classes');

try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
#   $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    $db->exec('PRAGMA foreign_keys = true;');
} catch (PDOException $e) { hiba($e->getMessage()); }

// először megkeressük az aktuális időpontot
require_once('fogadoora.class.php');
$FA = new Fogadoora();

// Ha még nincs semmi bejegyezve, csak az admin tud újat létrehozni
if (!isset($FA)) require_once('login.php');

/**
 * Az aktuális fogadóóra bejegyzés azonosítója
 */
define('fid', $FA->id);

/**
 * @desc A paraméterként kapott hibaszöveget kiírja, majd logolja
 * @param string $err A kiírandó hibaüzenet
 * @param boot $utf Kell-e utf-8 headert küldeni?                                                                               
 */
function hiba($err, $utf=false) {
    if ($utf) header('Content-Type: text/html; charset=utf-8');
    print "<p><hr><b>!!! - $err - !!!</b><hr>\n";
    Ulog (0, $err);
}

// az ini idők átszámolása
$FogadoIdo = array (
    TimeToFive($Fogado_tartam[0], $Fogado_tartam[1]),
    TimeToFive($Fogado_tartam[2], $Fogado_tartam[3])
);

$SzuloiIdo = array (
    TimeToFive($Szuloi_tartam[0], $Szuloi_tartam[1]),
    TimeToFive($Szuloi_tartam[2], $Szuloi_tartam[3])
);

/**
 * @desc Idő átszámítása 5 perces sorszámúról HH:MM formátumra
 * @param int $ido Az átszámítandó idő (5 perces)
 * @return string
 */
function FiveToString($ido) { return sprintf("%02d:%02d", floor($ido/12), ($ido%12)*5); } // volt: tim

/**
 * @desc Idő átszámítása 5 perces sorszámúról (óra, perc) formátumú tömbbé.
 * @param int $ido Az átszámítandó idő (5 perces)
 * @return array
 */
function FiveToTime($ido) { return array ('ora' => floor($ido/12), 'perc' => ($ido%12)*5); }

/**
 * @desc (óra, perc) formájú tömb átszámítása 5 perces sorszámúra.
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
    $SELECT .= "    <option value=\"" . $i*12 . "\">$i\n";
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

    $ret = preg_replace("/value=\"$ora\">/", "value=\"$ora\" selected>", $SELECT);
    $ret = preg_replace("/#NAME#/", "\"$name_ora\"", $ret);

    $ret .= preg_replace("/value=\"$perc\">/", "value=\"$perc\" selected>",
                "  <select name=\"$name_perc\">\n    <option value=\"0\">00\n    <option value=\"2\">10\n    <option value=\"4\">20" .
                "\n    <option value=\"6\">30\n    <option value=\"8\">40\n    <option value=\"10\">50\n  </select>\n");
    return $ret;
}

/**
 * @desc Az időtartam választáshoz a listbox elkészítése, visszaadja HTML stringben.
 * @return string
 * @param string $name a HTML tag "name" értéke
 */
function SelectTartam($name) {
    return preg_replace("/#NAME#/", "\"$name\"", "  <select name=#NAME#>\n"
        . "    <option value=\"1\">5\n    <option value=\"2\" selected>10\n"
        . "    <option value=\"3\">15\n    <option value=\"4\">20\n  </select>\n");
}


/**
 * @desc Elkészíti a HTML fejlécet
 * @param string $cimsor A TITLE tagben megjelenő szöveg
 * @param string $onload A BODY tag kiegészítői - ha kell
 * @param string $css A használandó stíluslap neve kiterjesztés nélkül
 */
function Head($cimsor, $onload = '', $css = 'default') {
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
  <link rel="stylesheet" href="$css.css" type="text/css">
</head>
EnD;
print "\n\n<body$onload>\n";
}

/**
 * @desc Kiírja a HTML láblécet.
 */
function Tail() {
    print "\n\n<p><hr><img src=\"dugo.png\" align=\"top\" alt=\"dugo@szepi_PONT_hu\">\n";
    print "</body>\n</html>\n";
}

/**
 * @desc Az Ulog táblába beírja a kapott szöveget
 * @param int $uid A módosítást végző felhasználó-azonosító (ADMIN-nál 0)
 * @param string $s a bejegyzendő szöveg
 */
function ulog($uid, $s) {
    global $db;
    $res = $db->prepare("INSERT INTO Ulog (ido, uid, host, log) VALUES (?, ?, ?, ?)");
    $res->execute(array(date("Y-m-d H:i:s"), ADMIN?0:$uid, $_SERVER['REMOTE_ADDR'], $s));
}

/**
 * @desc Kiírja a diák összesítő táblázatát.
 */
function osszesit($USER, $FA, $db){
    $Output = "\n<table width='100%'>\n"
        . "<tr><td><h3>Fogadóóra: " . $FA->datum . "<br>\n"
        . $USER->dnev . " " . $USER->onev . "<br>\n"
        . "<font size=-1>(Osztályfőnök: " . $USER->ofonev . ")</h3>\n"
        . "<td align='right' valign='top'><span class='noprint'><a href='fogado.php?".$_SERVER["QUERY_STRING"]."'>Vissza</a></span>\n"
        . "</table>\n";

    $res = $db->prepare(
                  "SELECT MIN(ido) AS eleje, MAX(ido) AS vege"
                . "  FROM Fogado"
                . "    WHERE fid=?"
                . "        AND tanar=?"
                . "        AND diak=-2"
                );
    $res->execute(array(fid, $USER->ofo));
    $szuloi = $res->fetch(PDO::FETCH_ASSOC);

    $SzuloiSor  = '';
    $SzuloiEleje  = 0;
    if ($szuloi['eleje']) {
        $SzuloiSor = "<br><b>" . FiveToString($szuloi['eleje'])
            . "-" . FiveToString($szuloi['vege']+1)
            . " -- szülői értekezlet</b>\n";
        $SzuloiEleje = $szuloi['eleje'];
    }

    $res = $db->prepare(
                  "SELECT ido, tnev"
                . "  FROM Fogado, Tanar"
                . "    WHERE fid=?"
                . "        AND Tanar.id=tanar"
                . "        AND diak=?"
                . "      ORDER BY ido"
                );
    $res->execute(array(fid, $USER->id));
    $rows = $res->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        if ($SzuloiEleje < $row['ido']) {
            $Output .= $SzuloiSor;
            $SzuloiSor = "";
        }
        $Output .= "<br>" . FiveToString($row['ido']) . " -- " . $row['tnev'] . "\n";
    }
    $Output .= $SzuloiSor;

    $Output .= "<form class=\"noprint\">\n   <br><input type=\"button\" value=\"Nyomtatás\" onClick=\"window.print()\"\n</form>\n";
    return $Output;
}

/**
 * @desc Fájl tömbbe beolvasásához sor-trimmelő (levágja a sorvégi \n-t)
 */
function file_trim(&$value, $key) { $value = trim($value); }

?>
