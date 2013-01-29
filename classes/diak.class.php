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
 * @file diak.class.php
 *
 * Diák osztály
 */

require_once('login.php');
require_once('ifa.inc.php');

class Diak {

    function Diak($did) {
        global $db;

        // Az azonosítóhoz tartozó adatbázis bejegyzés beolvasása,
        // - mezőnként egy változó

        $row = $db->query("SELECT * FROM Diak WHERE id=$did")->fetch(PDO::FETCH_ASSOC);

        // nem is kellene ellenőrizni, csak jó id-k jönnek
        if (count($row) == 0) { die("Nincs ilyen diák: $did"); }

        while(list($k, $v) = each($row)) {
            $this->$k = $v;
        }
    }
}

?>
