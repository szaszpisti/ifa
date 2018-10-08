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
 * @file fogadoora.class.php
 *
 * Fogadóóra osztály
 */

require_once('ifa.inc.php');

/**
 * @class Fogadoora
 *
 * Az időponttal kapcsolatos változókat tartalmazza, *dátum* és *string* formában is
 */
class Fogadoora {

    /**
     * - id, datum, kezd, veg, tartam, valid_kezd, valid_veg - az adatbázisból
     * - valid    Ha bejelentkezési időszak van -- különben csak az admin éri el
     * - IDO_min  a kiírást ennél a páros időpontnál kell kezdeni
     * - IDO_max  ezt a páros időpontot már nem kell kiírni
     * - datum_str, valid_kezd_str, valid_veg_str: az idők stringben
     */
    function __construct() {
        global $db;

        // beolvassuk az legnagyobb id sorát, az éppen aktuális fogadóórát
        $row = $db->query("SELECT * FROM Admin ORDER BY id DESC LIMIT 1;")->fetch(PDO::FETCH_ASSOC);

        // az adatbázis sor minden mezőjét beemeljük
        foreach($row as $key => $value) {
            $this->$key = $value;
        }
        // magyarul legyen a dátumszöveg
        setlocale(LC_TIME, 'hu_HU.UTF-8');
        $this->datum_str = strftime("%Y. %B %e., %A", strtotime($this->datum));
        $this->valid_kezd_str = strftime("%Y. %B %e., %A %H:%M", strtotime($this->valid_kezd));
        $this->valid_veg_str  = strftime("%Y. %B %e., %A %H:%M", strtotime($this->valid_veg ));


        $MaiDatum = date("Y-m-d H:i:s");
        $this->valid = ( ($this->valid_kezd <= $MaiDatum) && ($MaiDatum <= $this->valid_veg) );

        try { $res = $db->query("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE fid=" . $this->id);
        } catch (PDOException $e) { echo $e->getMessage(); }

        $Idoszak = $res->fetch(PDO::FETCH_ASSOC);
        $this->IDO_min = $Idoszak['min']-($Idoszak['min']%2);
        $this->IDO_max = $Idoszak['max']-($Idoszak['max']%2)+2;

    }
}

?>
