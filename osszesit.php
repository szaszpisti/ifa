<?
/*
 *   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Szász Imre.
 *
 *   Ez egy szabad szoftver; terjeszthetõ illetve módosítható a GNU
 *   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
 *   késõbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

/**
 * Szülõi összesített lista
 */

require_once('login.php');
require_once('ifa.inc.php');
require_once('diak.class.php');

$USER = new Diak($_SESSION['id']);

Head("Fogadóóra - " . $USER->dnev);

print "\n<h3>Fogadóóra: " . $FA->datum . "<br>\n"
    . $USER->dnev . " " . $USER->onev . "<br>\n"
    . "<font size=-1>(Osztályfõnök: " . $USER->ofonev . ")</h3>\n";

$szuloi =& $db->getRow(
              "SELECT MIN(ido) AS eleje, MAX(ido) AS vege"
            . "  FROM Fogado"
            . "    WHERE fid=" . fid
            . "        AND tanar=" . $USER->ofo
            . "        AND diak=-2",
            array(), DB_FETCHMODE_ASSOC);

if (DB::isError($data)) {
    die($data->getMessage());
}

if ($szuloi['eleje']) {
    $SzuloiSor = "<br><b>" . FiveToString($szuloi['eleje'])
        . "-" . FiveToString($szuloi['vege']+1)
        . " -- szülõi értekezlet</b>\n";
    $SzuloiEleje = $szuloi['eleje'];
}

$res =& $db->query(
              "SELECT ido, tnev"
            . "  FROM Fogado, Tanar"
            . "    WHERE fid=" . fid
            . "        AND Tanar.id=tanar"
            . "        AND diak=" . $USER->id
            . "      ORDER BY ido");

while ($res->fetchInto($row)) {
    if ($SzuloiEleje < $row['ido']) {
        $Output .= $SzuloiSor;
        $SzuloiSor = "";
    }
    $Output .= "<br>" . FiveToString($row['ido']) . " -- " . $row['tnev'] . "\n";
}
$Output .= $SzuloiSor;

print $Output;

Tail();

?>

