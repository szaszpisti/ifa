<?
/*
 *   Ez a f�jl az IFA (Iskolai Fogad��ra Adminisztr�ci�) csomag r�sze,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Sz�sz Imre.
 *
 *   Ez egy szabad szoftver; terjeszthet� illetve m�dos�that� a GNU
 *   �ltal�nos K�zread�si Felt�telek dokumentum�ban le�rtak -- 2. vagy
 *   k�s�bbi verzi� -- szerint, melyet a Szabad Szoftver Alap�tv�ny ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

/**
 * Sz�l�i �sszes�tett lista
 */

require_once('login.php');
require_once('ifa.inc.php');
require_once('diak.class.php');

$USER = new Diak($_SESSION['id']);

Head("Fogad��ra - " . $USER->dnev);

print "\n<h3>Fogad��ra: " . $FA->datum . "<br>\n"
    . $USER->dnev . " " . $USER->onev . "<br>\n"
    . "<font size=-1>(Oszt�lyf�n�k: " . $USER->ofonev . ")</h3>\n";

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
        . " -- sz�l�i �rtekezlet</b>\n";
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

