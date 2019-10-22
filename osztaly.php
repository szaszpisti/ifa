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
 * Az osztályok azonosítója és megjelenítési módja az OSZTALY fájlban van,
 * soronként id1;nev1;id2;nev2 stb. alakban - ezt dolgozzuk fel itt
 */

function osztaly() {
    global $db;
    global $baseurl;
    $out = '';

    //! Az OSZTALY-t beolvassuk soronként
    $OSZTALY_file = file('OSZTALY', FILE_IGNORE_NEW_LINES);

    //! a soronkénti osztályszámok maximuma
    $oMax = 0;
    foreach ($OSZTALY_file as $oszt) {
        // $O = array('id1', 'nev1', 'id2', 'nev2'), vagyis kétszer hosszabb
        $O = explode(';', $oszt);

        // megkeressük a max sorhosszt a táblázat méretéhez
        if (sizeof($O) > $oMax) $oMax = sizeof($O);
        $OSZTALY[] = $O;
    }
    $oMax /= 2; // dupláját számoltuk

    $out .= "\n<p><a href=\"$baseurl?tip=admin&amp;id=0\">ADMIN</a><br>\n";

    //! kiírjuk egyesével az osztályokat
    foreach ($OSZTALY as $oszt) {
        if(sizeof($oszt) < 2) continue;
        for ($i=0; $i<sizeof($oszt)/2; $i++) {
            $out .= "<span><a href=\"?o=" . $oszt[2*$i] . "\">" . $oszt[2*$i+1] . "</a></span>";
        }
        for ( ; $i<$oMax; $i++) {
            $out .= "<span>&nbsp;</span>";
        }
        $out .= "<br>\n";
    }
    $out .= "<a href=\"?o=t\">tanárok</a><br>\n\n";

    // Ha van osztály paraméter, akkor az adott osztály listáját írjuk ki

    if (isset($_REQUEST['o'])) {
        $o = preg_replace('/\W/', '', $_REQUEST['o']);
        if ($o == "t") $q = "SELECT id, tnev AS dnev FROM Tanar";
        else $q = "SELECT * FROM Diak WHERE oszt='$o'";

        try { $res = $db->query($q); }
        catch (PDOException $e) { $out .= $e->getMessage(); }

        $index = array();
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $index[$row['id']] = $row['dnev'];
        }
        setlocale(LC_ALL, "hu_HU.UTF-8"); 
        asort($index, SORT_LOCALE_STRING);
        reset($index);
        foreach($index as $id => $dnev){
            $href = '<p><a href="' . $baseurl . "?o=$o&amp;tip=";
            $href .= $o=='t' ? 'tanar' : 'diak';
            $href .= "&amp;id=$id\">" . $dnev . "</a>\n";
            $out .= $href;
        }
        $out .= "\n";
    }

    return $out;
    }
