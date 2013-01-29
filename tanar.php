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
 * @file tanar.php
 *
 * Az admin tanár-táblája
 */

require_once('login.php');
require_once('ifa.inc.php');
require_once('tanar.class.php');

/** Az aktuális tanár objektum */
$TANAR = new Tanar($_SESSION['id']);

if (isset($_REQUEST['mod'])) switch ($_REQUEST['mod']) {

    # a tanár fogadó-időpontjainak módosítása
    case 1:
        reset($_POST);
        $db->beginTransaction();
        while (list($key, $diak) = each($_POST)) {
            if ( ereg ("^r([0-9]+)$", $key, $match) ) {
                unset($q);
                $ido = $match[1];
                if (isset($TANAR->fogado_ido[$ido]['diak'])) {
                    if ($diak=="x") $q = "DELETE FROM Fogado WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
                    elseif ($diak != $TANAR->fogado_ido[$ido]['diak'])
                        $q = "UPDATE Fogado SET diak=" . $diak . " WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
                }
                else {
                    if ($diak != "x") $q = "INSERT INTO Fogado VALUES (" . fid . ", " . $TANAR->id . ", " . $ido . ", " . $diak . ")";
                }
                if (isset($q)) {
                    $db->query($q);
                    ulog(0, $q);
                }
            }
        }
        $db->commit();
        break;

    # az intervallum bővítése
    case 2:
        $UJ_min = $_REQUEST['kora'] +  $_REQUEST['kperc'];
        $UJ_max = $_REQUEST['vora'] +  $_REQUEST['vperc'];
        $tartam = $_REQUEST['tartam'];
        unset ($INSERT);

        while ($UJ_min%$tartam) $UJ_min++;

        /* Ha már van bejegyzett időpontja, akkor a bővítés az ez előtti
           és az ez utáni időkre vonatkozik */

        if ($TANAR->fogad) {
            for ($ido = $UJ_min; $ido < $TANAR->IDO_min; $ido++ ) {
                $INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
            }
            for ($ido = $TANAR->IDO_max; $ido < $UJ_max; $ido++) {
                $INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
            }
        }
        else { // még nem volt fogadóórája bejegyezve
            for ($ido = $UJ_min; $ido < $UJ_max; $ido++) {
                $INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
            }
        }

        if (isset($INSERT)) {
            try {
                $db->beginTransaction();
                $res = $db->prepare('INSERT INTO fogado (fid, tanar, ido, diak) VALUES (?, ?, ?, ?)');
                foreach ($INSERT as $sor) $res->execute($sor);
                ulog (0, $TANAR->tnev . " bővítés: $UJ_min -> $UJ_max ($tartam)" );
                $db->commit();
            } catch (PDOException $e) {
                ulog (0, "SIKERTELEN BŐVÍTÉS: " . $TANAR->tnev . "($UJ_min -> $UJ_max)" );
                die($res->getMessage());
            }
        }

        break;
}

$TANAR = new Tanar($_REQUEST['id']); # újra beolvassuk az adatbázisból

Head("Fogadóóra - " . $TANAR->tnev);

$res = $db->prepare("SELECT ' &ndash; ' || onev AS onev FROM Tanar, Osztaly WHERE Tanar.id=? AND Tanar.id=Osztaly.ofo");
$res->execute(array($TANAR->id));
$onev = $res->fetchColumn();

echo "\n<table width='100%'><tr>\n"
    . "<td><h3>" . $TANAR->tnev .  " (" . $FA->datum . ")$onev</h3>\n"
    . "<td align='right' class=\"sans\"><a href='" . $_SERVER['PHP_SELF'] . "?id=" . $TANAR->id . "&amp;kilep='> Kilépés </a>\n</table>\n";

# A külső táblázat első cellájában az időpont-lista
$TABLA = "<table border='0'><tr><td>\n";

if (ADMIN) {
    if ($TANAR->fogad) {
        $TABLA .= "<form method='post' name='tabla'>\n<table border='1' id=\"tanar\">\n"
            . "<tr><th><th>A<th>B<th>C<th>D<th>E\n"
            . "    <td colspan='2' align='right'><input type='hidden' name='mod' value='1'>\n"
            . "       <input type='reset' value='RESET'>\n"
            . "       <input type='submit' value=' Mehet '>\n";
        for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
            $TABLA .= ($ido%2?"<tr class='paratlan'>":"<tr>");
            if (array_key_exists($ido, $TANAR->fogado_ido)) $diak = $TANAR->fogado_ido[$ido]['diak'];
            else $diak = NULL;
            $TABLA .= "<td>" . FiveToString($ido);
            $TABLA .= "  <td><input type='radio' name='r$ido' value='x'" . (!isset($diak)?" checked":"") . ">\n";
            $TABLA .= "  <td><input type='radio' name='r$ido' value='0'" . ($diak=="0"?" checked":"") . ">\n";
            $TABLA .= "  <td><input type='radio' name='r$ido' value='-1'" . ($diak=="-1"?" checked":"") . ">\n";
            $TABLA .= "  <td><input type='radio' name='r$ido' value='-2'" . ($diak=="-2"?" checked":"") . ">\n";
            if ($diak>0) {
                $TABLA .= "  <td class='sajat'><input type='radio' name=r$ido value='$diak' checked><td><a class='diak' href='fogado.php?"
                    . "tip=diak&amp;id=" . $diak . "'>" . $TANAR->fogado_ido[$ido]['dnev'] . "</a>\n";
            } else {
                $TABLA .= "  <td colspan='2'>&nbsp;\n";
            }
        }
        $TABLA .= "<tr><td colspan='7' align='right'><input type='hidden' name='mod' value='1'>\n"
            . "       <input type='reset' value='RESET'>\n"
            . "       <input type='submit' value=' Mehet '>\n"
            . "</table>\n"
            . "</form>\n"
    # A külső táblázat második cellája
            . "<td>&nbsp;\n"
            . "<td valign=top>\n";
    }

    $TABLA .= "<br><b>Jelmagyarázat:</b><ul>\n"
        . "   <li>A: nincs itt<br>\n"
        . "   <li>B: fogadó időpont kezdete<br>\n"
        . "   <li>C: - időpont folytatása<br>\n"
        . "   <li>D: szülői értekezlet<br>\n"
        . "   <li>E: már bejelentkezett diák\n"
        . "</ul>\n"
        . "<script language=JavaScript type='text/javascript'><!--\n"
        . "function fivedel() {\n"
        . "  for (var i=0; i<document.tabla.length; i++) {\n"
        . "    o = document.tabla.elements[i]; // az űrlap elemeit veszi sorra\n"
        . "    if (o.value == '-1') {          // ha éppen '-1'-es gombnál tartunk\n"
        . "      ido = parseInt(o.name.substr(1,10));\n"
        . "      if (o.checked) eval ('document.tabla.' + o.name + '[1].checked = 1');\n"
        . "    }\n"
        . "  }\n"
        . "}\n"
        . "function nincs() {\n"
        . "  for (var i=0; i<document.tabla.length; i++) {\n"
        . "    o = document.tabla.elements[i]; // az űrlap elemeit veszi sorra\n"
        . "    if (o.value == 'x') o.checked = true;\n"
        . "  }\n"
        . "}\n"
        . "//--></script>\n"
        . "<form method='post'>\n"
        . "  <input type='hidden' name='mod' value='2'>\n";

    if ($TANAR->fogad) {
        $TABLA .= "<p>Bővítés: "
            . SelectIdo("kora", "kperc", $TANAR->IDO_min) . " - \n"
            . SelectIdo("vora", "vperc", $TANAR->IDO_max) . "\n &nbsp; &nbsp;"
            . SelectTartam('tartam') . "\n"
            . "  <input type='submit' value=' Uccu! '></p><br><br>\n"
            . "</form>\n"
            . "<p class='elso'><i>Gombok gyors állítása:</i>\n<ul>\n"
            . "  <li>Ha mégsem fog fogadni (összes -> A):\n"
            . "      <br> &nbsp; &nbsp; <input type='button' value=' Megjelöl ' onClick='nincs()'>\n"
            . "  <li>Ha az 5 percekben is fogadni akar (összes: C -> B):\n"
            . "      <br> &nbsp; &nbsp; <input type='button' value=' Megjelöl ' onClick='fivedel()'>\n"
            . "  <br>(Ezek után még kell a ,,Mehet'' gomb!)\n</ul>\n\n"
            . "</table>\n";
    }
    else {
        $TABLA .= "  Fogad: "
            . SelectIdo("kora", "kperc", $FA->IDO_min) . " - \n"
            . SelectIdo("vora", "vperc", $FA->IDO_max) . "\n &nbsp; &nbsp;"
            . SelectTartam('tartam') . "\n"
            . "  <input type='submit' value=' Uccu! '><br>\n"
            . "</form>\n"
            . "</table>\n";
    }

} else {
    if (isset($TANAR->IDO_min)) {
#        $elso = floor((($TANAR->IDO_min)+1)/2)*2;
        $elozo = 0;
        // ha van páratlan, akkor csak egyesével lépkedünk, egyébként kettesével
        for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido+=(2-$TANAR->ODD)) {
            $ora = floor($ido/12);
            if ($ora != $elozo) { $elozo = $ora; $TABLA .= "<tr><td colspan='3'><hr>\n"; }
            $TABLA .= ($ido%2?"<tr class='paratlan'>":"<tr>");
            if (isset($TANAR->fogado_ido[$ido]['diak'])) $diak = $TANAR->fogado_ido[$ido]['diak'];
            else $diak = 0;
            $TABLA .= "<td" . ($diak=="-2"?" class=szuloi":"") . ">" . FiveToString($ido)
                . "<td> -- <td>" . ($diak>0?$TANAR->fogado_ido[$ido]['dnev']:"&nbsp;") . "\n";
        }
    }
    $TABLA .= "</table>\n";

}

print $TABLA;

Tail();

?>

