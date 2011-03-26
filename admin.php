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

require_once('login.php');
require_once('ifa.inc.php');

if (!isset($_SESSION['admin'])) redirect('leiras.html');

if (!isset($_REQUEST['page'])) $_REQUEST['page'] = 0;

/*
Ha oldalszám nélkül hívjuk, akkor megnézi, hogy van-e időben következő fogadóóra:
ha van, akkor mindjárt a második oldalra ugrik, egyébként az első az alapértelmezett.
*/


$Out = "\n<table width=\"100%\"><tr><td>\n"
    . "<b><font color=\"#777777\">" . $_SESSION['nev'] . "</font></b>\n"
    . "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kilépés</a>\n</table>\n\n"
    . "<hr>\n\n";

if ($_REQUEST['page'] == 4) {
    $Out .= "Fogadóóra bejegyezve: " . $_REQUEST['datum'] . "\n\n";
    $_REQUEST['page'] = 0;
}

switch ($_REQUEST['page']) {
    case 0:
        if ($FA) $Out .= "<h3>Az aktuális (legutóbb bejegyzett) fogadóóra: &nbsp;" . $FA->datum_str . "</h3>\n<ul>\n";
                 $Out .= "<li><a href=\"admin.php?page=1\">Új időpont létrehozása</a>\n";
        if ($FA) $Out .= "<li><a href=\"fogado-xls.cgi\">Táblázat letöltése</a>\n</ul>\n\n";
        break;

    case 1:  // 1. ADMIN OLDAL
        $MaiDatum = date('Y-m-d');

        $Out .= "<h3>Új időpont létrehozása</h3>\n"
            . "<ul><form method=post><table class=tanar cellpadding=3>\n"
            . "<tr><td colspan=2>&nbsp;\n"

            . "<tr><td class=left colspan=2><b><i>Fogadóóra napja:</i></b>\n"
            . "<tr><td>\n"
            . "    <td class=right><input name=datum type=text size=10 value=\"$MaiDatum\">\n"
            . "<tr><td colspan=2>&nbsp;\n"

            . "<tr><td class=left colspan=2><b><i>Bejelentkezési időszak:</i></b>\n"
            . "<tr><td class=right> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; kezdete:\n"
            . "    <td><input name=valid_kezd type=text size=16 value=\"$MaiDatum 08:00\">\n"
            . "<tr><td class=right>vége:\n"
            . "    <td><input name=valid_veg type=text size=16 value=\"$MaiDatum 14:00\">\n"
            . "<tr><td colspan=2>&nbsp;\n"

            . "<tr><td class=left colspan=2><b><i>Alapértelmezések:</i></b>\n"
            . "<tr><td class=right>jelenlét: <td>\n"
            . "    " . SelectIdo("kora", "kperc", $FogadoIdo[0]) . "\n"
            . "    " . SelectIdo("vora", "vperc", $FogadoIdo[1]) . "\n"
            . "<tr><td class=right>tartam: <td>\n"
            . "    " . SelectTartam('tartam') . " perc\n"
            . "<tr><td class=right>szülői: <td>\n"
            . "    " . SelectIdo("skora", "skperc", $SzuloiIdo[0]) . "\n"
            . "    " . SelectIdo("svora", "svperc", $SzuloiIdo[1]) . "\n"
            . "<tr><td>\n"
            . "    <td class=right><input type=hidden name=page value=2>\n"
            . "        <input type=submit value=\" Mehet \">\n"
            . "</table>\n"
            . "</form></ul>\n";

        break;

    case 2:  // 2. ADMIN OLDAL

        /*
        Ellenőrzések:
           ha nincs dátum: nem tudunk mit csinálni...
           ha van: ha már létezik az admin táblában, és nincs még fogadó bejegyzés, akkor mehet tovább
                   ha nem létezik, létrehozzuk, mehet tovább.
        */

        if ( !isset($_REQUEST['datum']) ) { hiba ("Nincs dátum megadva"); return 1; }

        $result =& $db->query("SELECT * FROM Admin WHERE datum='" . $_REQUEST['datum'] . "'" );

        if ( $result->numRows() === 0 ) { // nincs még ilyen nap, létre lehet hozni
            $FogadoIdo = array (
                $_REQUEST['kora'] + $_REQUEST['kperc'],
                $_REQUEST['vora'] + $_REQUEST['vperc']
            );

            if (!$_REQUEST['tartam']) { hiba ("Tartam nincs megadva"); return 1; }
            if (!$_REQUEST['valid_kezd']) { hiba ("Érvényesség kezdete nincs megadva"); return 1; }
            if (!$_REQUEST['valid_veg']) { hiba ("Érvényesség vége nincs megadva"); return 1; }

            $fid = $db->nextId('admin_id');
            if (DB::isError($fid)) { die($fid->getMessage()); }

            $q = "INSERT INTO Admin (id, datum, kezd, veg, tartam, valid_kezd, valid_veg) VALUES ($fid, '"
                    . $_REQUEST['datum'] . "', $FogadoIdo[0], $FogadoIdo[1], "
                    . $_REQUEST['tartam'] . ", '"
                    . $_REQUEST['valid_kezd'] . "', '"
                    . $_REQUEST['valid_veg'] . "')";

            $res =& $db->query($q);
            if (DB::isError($res)) {
                hiba ("Nem sikerült regisztrálni a fogadóórát");
                die($res->getMessage());
            }
            else {
                Ulog(0, "RENDBEN: $q");
            }

        }
        elseif ( $result->numRows() === 1 ) {
            $result->fetchInto($row);
            $num =& $db->getOne("SELECT count(*) AS num FROM Fogado WHERE fid=" . $row['id']);
            if ($num > 0 ) { hiba ("E napon már vannak bejegyzések"); return 1; }
        }
        else {
            hiba ("HAJAJ! Nagy GÁZ van... (több egyforma dátum?)");
            return 1;
        }

        // túl vagyunk az időpontbejegyzésen, újból beolvassuk az aktuálisat
        // $FA a fogadóóra bejegyzés asszociatív tömbje

        $FA =& $db->getRow("SELECT * FROM Admin WHERE datum='" . $_REQUEST['datum'] . "'" );
        if (DB::isError($FA)) { die($FA->getMessage()); }

        $Out .= "<b>Fogadóóra: " . $FA['datum'] . "</b>\n\n";

        // Kiírjuk soronként a tanárokat az egyéni beállításokhoz
        // eredmény: Tanar['id'] = array (emil, tnev, ofo)
        $Tanar =& $db->getAssoc(
                          "SELECT id, emil, tnev, ofo FROM Tanar AS T"
                        . "    LEFT OUTER JOIN"
                        . "  (SELECT ofo FROM Osztaly) AS O"
                        . "    ON (T.id=O.ofo) ORDER BY tnev",
                        true, array(), DB_FETCHMODE_ASSOC);

        // Out-ba gyűjtjük a kimenetet, kezdjük a fejléccel
        $Out .= "<form method=post>\n<table class=tanar>\n"
            . "<tr><th><th colspan=4>\n"
            . "<tr><th>Tanár neve<th><th>Fogadóóra<th>tartam<th><th colspan=2>Szülői<th>\n";

        // A tanár tömbön megyünk végig egyesével
        foreach (array_keys($Tanar) as $id) {
            $t = $Tanar[$id];

            $paratlan = 1-$paratlan;   // a színezés miatt váltott sorosan haladunk

            $Out .= "\n<tr" . ($paratlan?" class=paratlan":"") . "><td>" . $t['tnev'] . "\n"
                . "  <td><input type=checkbox name=a$id checked>\n"
                . "  <td>" . SelectIdo("b$id", "c$id", $FogadoIdo[0]) . " &nbsp;\n"
                . "      " . SelectIdo("d$id", "e$id", $FogadoIdo[1]) . " &nbsp;\n"
                . "  <td align=center>" . SelectTartam("f$id") . "<td>\n";

            if ( $t['ofo'] > 0 ) {
                $Out .= "  <td><input type=checkbox name=g$id checked>\n"
                    . "  <td>" . SelectIdo("h$id", "i$id", $_REQUEST['skora'] + $_REQUEST['skperc']) . " &nbsp;\n"
                    . "      " . SelectIdo("j$id", "k$id", $_REQUEST['svora'] + $_REQUEST['svperc']) . " &nbsp;\n";
            }
            else {
                $Out .= "  <td colspan=4>\n";
            }
        }

        // Lábléc
        $Out .= "<tr><td colspan=12 class=right>\n"
            . "<input type=hidden name=fid value=" . $FA['id'] . ">\n"
            . "<input type=hidden name=page value=3>\n"
            . "<input type=hidden name=datum value=" . $_REQUEST['datum'] . ">\n"
            . "<input type=submit value=\" Mehet \">\n"
            . "</table>\n"
            . "</form>\n";

        break;


    // A bejegyzések alapján a fogadóóra táblájának feltöltése
    case 3:

        // ha nem tudjuk, melyik fogadó-azonosítóhoz kell bejegyzéseket csinálni
        if (!isset($_REQUEST['fid'])) { hiba ("Nincs fogadó-azonosító"); return 1; }

        // csak akkor tudunk továbblépni, ha 1! bejegyzés van az adott napon
        $num =& $db->getOne("SELECT count(*) AS num FROM Admin WHERE id=" . $_REQUEST['fid'] );
        if (DB::isError($num)) { die($num->getMessage()); }
        if ( $num != 1 ) { hiba ("Nincs ilyen nap regisztrálva"); return 1; }

        // ha ilyen id van már bejegyezve az időpontoknál, akkor már jártunk itt -> hiba
        $num =& $db->getOne("SELECT count(*) AS num FROM Fogado WHERE fid=" . $_REQUEST['fid'] );
        if (DB::isError($num)) { die($num->getMessage()); }
        if ( $num > 0 ) { hiba ("E napon már vannak bejegyzések"); return 1; }


        // A kapott űrlap-változókat rendezzük használható tömbökbe
        //    $JelenVan['id'] (id, kezd, veg, tartam)
        //    $Szuloi['id'] (id, kezd, veg)

        reset($_REQUEST);
        while (list($k, $v) = each ($_REQUEST)) {
            if ( ereg ("^a([0-9]+)$", $k, $match) ) {
                $id = $match[1];
                $JelenVan[$id] = array('id' => $id,
                    'kezd' => $_REQUEST["b" . $id] + $_REQUEST["c" . $id],
                    'veg'  => $_REQUEST["d" . $id] + $_REQUEST["e" . $id],
                    'tartam' => $_REQUEST["f" . $id] );
            }
            if ( ereg ("^g([0-9]+)$", $k, $match) ) {
                $id = $match[1];
                $Szuloi[$id] = array('id' => $id,
                    'kezd' => $_REQUEST["h" . $id] + $_REQUEST["i" . $id],
                    'veg'  => $_REQUEST["j" . $id] + $_REQUEST["k" . $id] );
            }
        }

        // Feltöltjük a Tanar tömböt, ez ilyen formán fog majd az adatbázisba kerülni

        foreach ($JelenVan as $t) {
            if ( $t['kezd'] && $t['veg'] && $t['tartam'] ) {
                // először az összes időpontját nem foglalhatóvá (-1) tesszük
                for ($i=$t['kezd']; $i<$t['veg']; $i++) {
                    $Tanar[$t['id']][$i]=-1;
                }
                // majd bejelöljük a foglalhatókat (0)
                for ($i=$t['kezd']; $i<$t['veg']; $i+=$t['tartam']) {
                    $Tanar[$t['id']][$i]=0;
                }
            }
        }

        if (sizeof($Szuloi) > 0) {
            foreach ($Szuloi as $t) {
                if ( $t['kezd'] && $t['veg'] && isset($JelenVan[$t['id']]) ) {
                    for ($i=$t['kezd']; $i<$t['veg']; $i++) $Tanar[$t['id']][$i]=-2;
                }
            }
        }

        foreach ( array_keys($Tanar) as $id ) {
            reset ($Tanar[$id]);
            while (list ($key, $val) = each ($Tanar[$id])) {
               $Tanar_copy[] = array($_REQUEST['fid'], $id, $key, $val);
            }
        }

        $sth = $db->prepare('INSERT INTO fogado VALUES (?, ?, ?, ?)');
        $res =& $db->executeMultiple($sth, $Tanar_copy);
        if (DB::isError($res)) {
            ulog (0, "SIKERTELEN ADATBEVITEL");
            die($res->getMessage());
        }
        else {
            ulog (0, "Új időpont felvitele sikerült." );
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?page=4&datum=" . $_REQUEST['datum']);

        break;

    // Rossz paraméterek
    default:
        hiba ("Érvénytelen oldal: " . $_REQUEST['page']);
        return 1;
        break;
}

Head("Fogadó admin - " . $_REQUEST['page'] . ". oldal");
print $Out;
Tail();
?>
