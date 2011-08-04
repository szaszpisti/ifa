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

require_once('ifa.inc.php');

class Fogadoora {

/*
valid: Ha bejelentkezési időszak van -- különben csak az admin éri el
IDO_min:  a kiírást ennél a páros időpontnál kell kezdeni
IDO_max:  ezt a páros időpontot már nem kell kiírni
*/

    function Fogadoora() {
        global $db;

        // az utolsó id az éppen aktuális fogadóóra
        try { $res = $db->query("SELECT * FROM Admin WHERE id=(SELECT MAX(id) FROM Admin)");
        } catch (PDOException $e) { echo "HELLO"; echo $e->getMessage(); }

        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (count($row) == 0) { unset ($this); return(0); }

        while(list($k, $v) = each($row)) {
            $this->$k = $v;
        }
        setlocale(LC_TIME, 'hu_HU.UTF-8');
        $this->datum_str = strftime("%Y. %B %e., %A", strtotime($this->datum));
        $this->valid_kezd_str = strftime("%Y. %B %e., %A %H:%M", strtotime($this->valid_kezd));
        $this->valid_veg_str  = strftime("%Y. %B %e., %A %H:%M", strtotime($this->valid_veg ));


        $MaiDatum = date("Y-m-d H:i:s");
        $this->valid = ( ($this->valid_kezd <= $MaiDatum)
                    && ($MaiDatum <= $this->valid_veg) );

        try { $res = $db->query("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE fid=" . $this->id);
        } catch (PDOException $e) { echo $e->getMessage(); }

        $Idoszak = $res->fetch(PDO::FETCH_ASSOC);
        $this->IDO_min = $Idoszak['min']-($Idoszak['min']%2);
        $this->IDO_max = $Idoszak['max']-($Idoszak['max']%2)+2;

    }
}

?>
