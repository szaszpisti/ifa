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
 * @file admin.php
 *
 * Fogadóóra bejegyzése és letöltése
 *
 * Ha oldalszám nélkül hívjuk, akkor megnézi, hogy van-e időben következő fogadóóra:
 * ha van, akkor mindjárt a második oldalra ugrik, egyébként az első oldal az alapértelmezett.
 */

#require_once('login.php');
require_once('ifa.inc.php');

function admin() {
    global $db, $FA, $FogadoIdo, $SzuloiIdo;

    if (!isset($_REQUEST['page']) || !in_array($_REQUEST['page'], array(0, 1, 2, 3, 4))) {
        $_REQUEST['page'] = 0;
    }

    // Ha valami hiba történt, visszadob a 0. oldalra egy hibakóddal
    if(isset($_REQUEST['error'])) {
        switch ($_REQUEST['error']) {
            case "1": hiba("Nem sikerült regisztrálni a fogadóórát - nézd meg a webszerver error.log-ját!");
                break;
        }
    }

    /** $Out - ebbe gyűjtjük a kimenetet */
    $Out = "\n<table width=\"100%\"><tr><td>\n"
        . "<b><font color=\"#777777\">" . $_SESSION['nev'] . "</font></b>\n"
        . '<td align="right" valign="top" class="sans"><a href="' . $_SERVER['PHP_SELF'] . "?kilep=\">Kilépés</a>\n</table>\n\n"
        . "<hr>\n\n";

    if ($_REQUEST['page'] == 4) {
        $Out .= "Fogadóóra bejegyezve: " . $_REQUEST['datum'] . "\n\n";
        $_REQUEST['page'] = 0;
    }

    switch ($_REQUEST['page']) {
        case 0:
            if ($FA) $Out .= "<h3>Az aktuális (legutóbb bejegyzett) fogadóóra: &nbsp;" . $FA->datum_str . "</h3>\n<ul>\n";
                     $Out .= "<li><a href=\"?tip=admin&page=1\">Új időpont létrehozása</a>\n";
            if ($FA) $Out .= "<li><a href=\"".__TABLE__."\" target=\"_blank\">Táblázat letöltése (" . preg_replace('/.*-(.*)\..*/', '\1', __TABLE__) . ")</a>\n</ul>\n\n";
            break;

        case 1:  // 1. ADMIN OLDAL
            $MaiDatum = date('Y-m-d');
            $Ido_kora = SelectIdo("kora", "kperc", $FogadoIdo[0]);
            $Ido_vora = SelectIdo("vora", "vperc", $FogadoIdo[1]);
            $Ido_tartam = SelectTartam('tartam');
            $Ido_skora = SelectIdo("skora", "skperc", $SzuloiIdo[0]);
            $Ido_svora = SelectIdo("svora", "svperc", $SzuloiIdo[1]);

            $Out .= <<< Vege

    <style type="text/css" media="all">@import "js/datechooser.css";</style>
    <script type="text/javascript" src="js/datechooser.js"></script>
    <script type="text/javascript"><!--
    /* http://yellow5.us/projects/datechooser/example/ */
    events.add(window, 'load', WindowLoad);
    function WindowLoad() {
        var dc = document.getElementById('pdatum');
        dc.DateChooser = new DateChooser();
        dc.DateChooser.setCloseTime(400);
        dc.DateChooser.setWeekStartDay(1);
        dc.DateChooser.setUpdateField('datum', 'Y-m-d');
        dc.DateChooser.setUpdateFunction(valid_beir);
        dc.DateChooser.setIcon('js/datechooser.png', 'datum', true, 'Válasszon dátumot!');
    }
    function datum_copy() {
       old = document.forms[0].valid_veg.value;
       ora = old.substr(old.indexOf(' '), 10);
       document.forms[0].valid_veg.value = document.forms[0].datum.value + ora;
    }

    function valid_beir() {
       /* kezd = 1 (hétfő), veg = 4 (csütörtök) */
       var kezd_nap = 1;
       var veg_nap = 4;
       d = document.forms[0].datum.value;
       // a firefox csak mm/dd/yyyy formában szereti a dátumot
       var datum = new Date(d.substr(5,2) + '/' + d.substr(8,2) + '/' + d.substr(0,4));
       day = datum.getDay()==0?7:datum.getDay();
       epoch = datum.getTime() - (day-kezd_nap)*86400000; // ennyi napot visszaszámolunk a hétfőig
       datum = new Date(epoch); // ez az előző hétfő
       y = datum.getFullYear();
       m = datum.getMonth()+1;
       d = datum.getDate();
       var kezd_datum = y+'-'+(m<10?'0':'')+m+'-'+(d<10?'0':'')+d;
       document.forms[0].valid_kezd.value = kezd_datum + ' 08:00';

       d = document.forms[0].datum.value;
       // a firefox csak mm/dd/yyyy formában szereti a dátumot
       var datum = new Date(d.substr(5,2) + '/' + d.substr(8,2) + '/' + d.substr(0,4));
       day = datum.getDay()==0?7:datum.getDay();
       epoch = datum.getTime() + (veg_nap-day)*86400000; // ennyi napot visszaszámolunk a hétfőig
       datum = new Date(epoch); // ez az előző hétfő
       y = datum.getFullYear();
       m = datum.getMonth()+1;
       d = datum.getDate();
       var veg_datum = y+'-'+(m<10?'0':'')+m+'-'+(d<10?'0':'')+d;
       document.forms[0].valid_veg.value = veg_datum + ' 12:00';
    }

    //--></script>

    <h3>Új időpont létrehozása</h3>
    <div style="margin-left: 3em;"><form method="post"><table class="tanar" cellpadding="3">

    <tr><td class="left" colspan="2"><hr><b><i>Fogadóóra napja:</i></b></td>
    <tr><td>&nbsp;</td>
        <td class="left" id="pdatum"><input name="datum" id="datum" type="text" size="10" value="$MaiDatum"
            onKeyUp="datum_copy();" onChange="valid_beir();"></td>

    <tr><td class="left" colspan="2"><hr><b><i>Bejelentkezési időszak:</i></b></td>
    <tr><td class="right"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; kezdete:</td>
        <td><input name="valid_kezd" type="text" size="20" value="$MaiDatum 08:00"></td>
    <tr><td class="right">vége:</td>
        <td><input name="valid_veg" type="text" size="20" value="$MaiDatum 12:00"></td>

    <tr><td class="left" colspan="2"><hr><b><i>Alapértelmezések:</i></b></td>
    <tr><td class="right">jelenlét: <td>\n$Ido_kora$Ido_vora</td>
    <tr><td class="right">tartam: <td>\n$Ido_tartam perc</td>
    <tr><td class="right">szülői: <td>\n$Ido_skora$Ido_svora</td>
    <tr><td class="left" colspan="2"><hr></td>
    <tr><td>&nbsp;</td>
        <td class="right"><input type="hidden" name="page" value="2">
            <input type="submit" value=" Mehet "></td>
    </table>
    </form></div>
    <script type="text/javascript"><!--
    valid_beir();
    //--></script>
    Vege;

            break;

        case 2:  // 2. ADMIN OLDAL

            /**
            Ellenőrzések:
               ha nincs dátum: nem tudunk mit csinálni...
               ha van: ha már létezik az admin táblában, és nincs még fogadó bejegyzés, akkor mehet tovább
                       ha nem létezik, létrehozzuk, mehet tovább.
            */

            if ( !isset($_REQUEST['datum']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['datum'])) {
                hiba ("Nincs dátum megadva"); return 1;
            }

            $res = $db->prepare("SELECT * FROM Admin WHERE datum=?");
            $res->execute(array($_REQUEST['datum']));
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);

            if ( count($rows) === 0 ) { // nincs még ilyen nap, létre lehet hozni

                $FogadoIdo = array (
                    $_REQUEST['kora'] + $_REQUEST['kperc'],
                    $_REQUEST['vora'] + $_REQUEST['vperc']
                );

                if (!$_REQUEST['tartam']) { hiba ("Tartam nincs megadva"); return 1; }
                if (!$_REQUEST['valid_kezd']) { hiba ("Érvényesség kezdete nincs megadva"); return 1; }
                if (!$_REQUEST['valid_veg']) { hiba ("Érvényesség vége nincs megadva"); return 1; }

                $res = $db->prepare('INSERT INTO Admin (datum, kezd, veg, tartam, valid_kezd, valid_veg) VALUES (?, ?, ?, ?, ?, ?)');
                $ret = $res->execute(array(
                    $_REQUEST['datum'], $FogadoIdo[0], $FogadoIdo[1], $_REQUEST['tartam'],
                    $_REQUEST['valid_kezd'], $_REQUEST['valid_veg']));
                if (!$ret) {
                    redirect($_SERVER['REQUEST_URL'] . "?tip=admin&page=0&error=1");
                }

            }
            elseif ( count($rows) === 1 ) {

                $res = $db->prepare("SELECT COUNT(*) FROM Fogado WHERE fid=?");
                $res->execute(array($rows[0]['id']));
                if ($res->fetchColumn() > 0) { hiba ("E napon már vannak bejegyzések"); return 1; }
            }
            else {
                hiba ("HAJAJ! Nagy GÁZ van... (több egyforma dátum?)");
                return 1;
            }

            // túl vagyunk az időpontbejegyzésen, újból beolvassuk az aktuálisat
            // $FA a fogadóóra bejegyzés asszociatív tömbje

            $res = $db->prepare("SELECT * FROM Admin WHERE datum=?");
            $res->execute(array($_REQUEST['datum']));
            $FA = $res->fetch(PDO::FETCH_ASSOC);

            $Out .= "<b>Fogadóóra: " . $FA['datum'] . "</b>\n\n";

            // Kiírjuk soronként a tanárokat az egyéni beállításokhoz
            // eredmény: Tanar['id'] = array (emil, tnev, ofo)
            $res = $db->query("SELECT id, tnev, ' (' || onev || ')' AS onev, ofo FROM Tanar AS T"
                            . "    LEFT OUTER JOIN"
                            . "  (SELECT onev, ofo FROM Osztaly) AS O"
                            . "    ON (T.id=O.ofo) ORDER BY tnev" );
            $Tanar = $res->fetchAll(PDO::FETCH_ASSOC);

            // Out-ba gyűjtjük a kimenetet, kezdjük a fejléccel
            $Out .= "<form method=post>\n<table class=tanar>\n"
                . "<tr><th><th colspan=4>\n"
                . "<tr><th>Tanár neve<th><th>Fogadóóra<th>tartam<th><th colspan=2>Szülői<th>\n";

            // A tanár tömbön megyünk végig egyesével
            $paratlan = 0;
            foreach ($Tanar as $t) {
                $id = $t['id'];

                $paratlan = 1-$paratlan;   // a színezés miatt váltott sorosan haladunk

                $Out .= "\n<tr" . ($paratlan?" class=paratlan":"") . "><td>" . $t['tnev'] . $t['onev'] . "\n"
                    . "  <td><input type=checkbox name=a$id checked>\n"
                    . "  <td>\n" . SelectIdo("b$id", "c$id", $_REQUEST['kora'] + $_REQUEST['kperc']) . " &nbsp;\n"
                    . "      " . SelectIdo("d$id", "e$id", $_REQUEST['vora'] + $_REQUEST['vperc']) . " &nbsp;\n"
                    . "  <td align=center>\n" . SelectTartam("f$id", $_REQUEST['tartam']) . "<td>\n";

                if ( $t['ofo'] > 0 ) {
                    $Out .= "  <td><input type=checkbox name=g$id checked>\n"
                        . "  <td>\n" . SelectIdo("h$id", "i$id", $_REQUEST['skora'] + $_REQUEST['skperc']) . " &nbsp;\n"
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

            $res = $db->prepare("SELECT COUNT(*) FROM Admin WHERE id=?");
            $res->execute(array($_REQUEST['fid']));
            if ($res->fetchColumn() != 1) { hiba ("Nincs ilyen nap regisztrálva"); return 1; }

            $res = $db->prepare("SELECT COUNT(*) FROM Fogado WHERE fid=?");
            $res->execute(array($_REQUEST['fid']));
            if ($res->fetchColumn() > 0) { hiba ("E napon már vannak bejegyzések"); return 1; }

            // A kapott űrlap-változókat rendezzük használható tömbökbe
            //    $JelenVan['id'] (id, kezd, veg, tartam)
            //    $Szuloi['id'] (id, kezd, veg)
            reset($_REQUEST);
            $Szuloi = array();
            foreach ($_REQUEST as $k => $v) {
                if ( preg_match ("/^a([0-9]+)$/", $k, $match) ) {
                    $id = $match[1];
                    $JelenVan[$id] = array('id' => $id,
                        'kezd' => $_REQUEST["b" . $id] + $_REQUEST["c" . $id],
                        'veg'  => $_REQUEST["d" . $id] + $_REQUEST["e" . $id],
                        'tartam' => $_REQUEST["f" . $id] );
                }
                if ( preg_match ("/^g([0-9]+)$/", $k, $match) ) {
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

            try {
                $db->beginTransaction();
                $res = $db->prepare('INSERT INTO fogado VALUES (?, ?, ?, ?)');
                foreach ( array_keys($Tanar) as $id ) {
                    reset ($Tanar[$id]);
                    foreach ($Tanar[$id] as $key => $val) {
                        $res->execute( array($_REQUEST['fid'], $id, $key, $val) );
                    }
                }
                $db->commit();
                ulog (0, "Új időpont felvitele sikerült." );
            } catch (PDOException $e) {
                ulog (0, "SIKERTELEN ADATBEVITEL");
                echo $e->getMessage();
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?tip=admin&page=4&datum=" . $_REQUEST['datum']);

            break;

        // Rossz paraméterek
        default:
            hiba ("Érvénytelen oldal: " . $_REQUEST['page']);
            return 1;
            break;
    }

    return $Out;
}
