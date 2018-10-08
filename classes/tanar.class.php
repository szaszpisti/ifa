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
 * @file tanar.class.php
 *
 * Tanár osztály
 */

require_once('login.php');
require_once('ifa.inc.php');

class Tanar {

    function __construct($tid) {
        global $db;

        $this->id = $tid;
        $this->ODD = false;
        $this->fogad = false;

/*

        [id] => 83                # A tanár azonosítója
        [ODD] => 0                # Van-e benne páratlan (5 perces) időpont
        [fogad] => 1              # Van-e a tanárnál fogadóóra bejegyezve
        [emil] => 'monoton'       # azonosító
        [tnev] => 'Monoton Manó'  # név
        [fogado_ido] => Array (
                [192] => Array ( [diak] => 371,  [dnev] => 'Pumpa Pál (12. X)' )
                [193] => Array ( [diak] => -1,   [dnev] => '' )
                ...
            )
        [IDO_min] => 192          # első időpontja
        [IDO_max] => 228          # utolsó időpontja + 1

*/

        # Feltöltjük a tanár tulajdonságait
        try {
            $res = $db->prepare("SELECT * FROM Tanar WHERE id=?");
            $res->execute(array($this->id));
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { die($e->getMessage()); }

        if (count($rows) == 0) { die("Nincs ilyen tanár: $tid"); }

        foreach($rows[0] as $key => $value) {
            $this->$key = $value;
        }

        $q = "SELECT ido, diak, dnev || ' (' || onev || ')' AS dnev"
                . "    FROM Fogado AS F"
                . "  LEFT OUTER JOIN"
                . "    Diak AS D"
                . "      ON (F.diak=D.id AND D.id>0)"
                . "  WHERE F.fid=" . fid . " AND F.tanar=" . $tid
                . "      ORDER BY ido";

        try {
            $res = $db->query($q);

            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) $this->fogad = true;             // ha van időpontja akkor fogad
            else $this->fogad = false;
            foreach ($rows as $f) {
                $this->fogado_ido[$f['ido']] = array('diak'=>$f['diak'], 'dnev'=>$f['dnev']);
            }
        }
        catch (PDOException $e) { echo $e->getMessage(); }

        if (!isset($this->fogado_ido)) { return; }

        // ODD: ido1 | ido2 | ido3 ... ha volt benne páratlan, végül páratlan lesz
        foreach (array_keys($this->fogado_ido) as $ido) {
            if ($this->fogado_ido[$ido]['diak'] >= 0) { // ha fogad ebben az időben
                $this->ODD |= $ido;
            }
        }
        // az ODD, az "összegzett" idők paritása kell nekünk
        $this->ODD &= 1;

        if ($this->fogad) {
            $this->IDO_min = min(array_keys($this->fogado_ido));
            $this->IDO_max = max(array_keys($this->fogado_ido)) + 1;
        }

    }

}

?>
