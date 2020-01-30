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
 * Kiírja a bal menüt: az osztályokat, és ha kell, az osztály ill. tanári névsort.
 *
 * Az osztályok azonosítóját az adatbázis "osztaly" táblájából veszi
 */

function osztaly() {
    global $db;
    global $baseurl;
    $ret = "\n<p><a href='$baseurl?tip=admin&amp;id=0'>ADMIN</a><br>\n";
    $q = "SELECT * FROM Osztaly ORDER BY CAST(onev AS INTEGER), onev";
    $osztalyok = $db->query($q);
    $prev = '';
    foreach($osztalyok as $osztaly) {
        $oszt = $osztaly[0];
        $onev = $osztaly[1];
        $ev = substr($oszt, 1, 2);
        if ($prev != $ev && $prev != '') {
            $ret .= "<br>\n";
        }
        $ret .= "  <span class='osztaly_nev'><a href='?oszt=$oszt'>$onev</a></span>";
        $prev = $ev;
    }
    $ret .= "<br>\n  <a href=\"?oszt=t\">tanárok</a><br>\n";

    // Ha van osztály paraméter, akkor az adott osztály listáját írjuk ki

    if (isset($_REQUEST['oszt'])) {
        $oszt = preg_replace('/\W/', '', $_REQUEST['oszt']);
        if ($oszt == "t") $q = "SELECT id, tnev AS dnev FROM Tanar";
        else $q = "SELECT * FROM Diak WHERE oszt='$oszt'";

        try { $res = $db->query($q); }
        catch (PDOException $e) { $ret .= $e->getMessage(); }

        $index = array();
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $index[$row['id']] = $row['dnev'];
        }
        setlocale(LC_ALL, "hu_HU.UTF-8"); 
        asort($index, SORT_LOCALE_STRING);
        reset($index);
        foreach($index as $id => $dnev){
            $href = '<p><a href="' . $baseurl . "?oszt=$oszt&amp;tip=";
            $href .= $oszt=='t' ? 'tanar' : 'diak';
            $href .= "&amp;id=$id\">" . $dnev . "</a>\n";
            $ret .= $href;
        }
        $ret .= "\n";
    }

    return $ret;
}

