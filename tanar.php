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

function tanar() {
    global $user;
    global $db;
    global $FA;
    #require_once('login.php');
    #require_once('ifa.inc.php');
    #require_once('tanar.class.php');

    /** Az aktuális tanár objektum */
#    $TANAR = new User($_SESSION['id']);
    $TANAR = $user;

    if (isset($_REQUEST['mod'])) {
      switch ($_REQUEST['mod']) {

        # a tanár fogadó-időpontjainak módosítása
        case 1:
            reset($_POST);
            $db->beginTransaction();
            foreach($_POST as $key => $diak) {
                if ( preg_match ("/^r([0-9]+)$/", $key, $match) ) {
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
                    ulog (0, $TANAR->nev . " bővítés: $UJ_min -> $UJ_max ($tartam)" );
                    $db->commit();
                } catch (PDOException $e) {
                    ulog (0, "SIKERTELEN BŐVÍTÉS: " . $TANAR->nev . "($UJ_min -> $UJ_max)" );
                    die($res->getMessage());
                }
            }

            break;
      }
    }

#    $TANAR = new User($_REQUEST['id']); # újra beolvassuk az adatbázisból
    $TANAR = new User($user->id); # újra beolvassuk az adatbázisból

    #Head("Fogadóóra - " . $TANAR->nev);

    $res = $db->prepare("SELECT ' &ndash; ' || onev AS onev FROM Tanar, Osztaly WHERE Tanar.id=? AND Tanar.id=Osztaly.ofo");
    $res->execute(array($TANAR->id));
    $onev = $res->fetchColumn();

    $Fejlec = "\n<table width='100%'><tr>\n"
        . "<td><h3>" . $TANAR->nev .  " (" . $FA->datum . ")$onev</h3></td></tr></table>\n";
#        . "<td align='right'><span class='noprint sans'>\n"
#        . "<a href='" . $_SERVER['PHP_SELF'] . "?id=" . $TANAR->id . "&amp;kilep='> Kilépés </a>\n<!--#--></span></td></tr></table>\n";

    if (ADMIN) {
        # A külső táblázat első cellájában az időpont-lista
        $TABLA = "<table border='0'><tr><td>\n";
        if ($TANAR->fogad) {
            $TABLA .= "<form method='post' name='tabla' action=''>\n<table border='1' id=\"tanar\">\n"
                . "<tr><th><th>A<th>B<th>C<th>D<th>E\n"
                . "    <td colspan='2' align='right'><input type='hidden' name='mod' value='1'>\n"
                . "       <input type='reset' value='RESET'>\n"
                . "       <input type='submit' value=' Mehet '>\n";
            $idok = array();
            for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
                $idok[] = 'r' . $ido;
                $TABLA .= ($ido%2?"<tr class='paratlan'>":"<tr>");
                if (array_key_exists($ido, $TANAR->fogado_ido)) $diak = $TANAR->fogado_ido[$ido]['diak'];
                else $diak = NULL;
                $TABLA .= "<td>" . FiveToString($ido);
                $TABLA .= "  <td class='nincs'><input type='radio' name='r$ido' value='x'" . (!isset($diak)?" checked":"") . ">\n";
                $TABLA .= "  <td class='idoKezd'><input type='radio' name='r$ido' value='0'" . ($diak=="0"?" checked":"") . ">\n";
                $TABLA .= "  <td class='idoFolytat'><input type='radio' name='r$ido' value='-1'" . ($diak=="-1"?" checked":"") . ">\n";
                $TABLA .= "  <td class='szuloi'><input type='radio' name='r$ido' value='-2'" . ($diak=="-2"?" checked":"") . ">\n";
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
            . "   <li><span class='nincs'     >A</span>: nincs itt<br>\n"
            . "   <li><span class='idoKezd'   >B</span>: fogadó időpont kezdete<br>\n"
            . "   <li><span class='idoFolytat'>C</span>: - időpont folytatása<br>\n"
            . "   <li><span class='szuloi'    >D</span>: szülői értekezlet<br>\n"
            . "   <li><span class='foglalt'   >E</span>: már bejelentkezett diák\n"
            . "</ul>\n"
            . "<script language=JavaScript type='text/javascript'><!--\n";
        $TABLA .= "var idok = ['" . implode("', '", $idok) . "']; // a létező időpontok\n";
        $TABLA .=
              "function fivedel() {\n"
            . "  for (const ido of idok) {\n"
            . "    val = document.querySelector('input[name=' + ido + ']:checked').value;\n"
            . "    if (val == -1) {\n"
            . "      document.getElementsByName(ido)[1].checked = true;\n"
            . "    }\n"
            . "  }\n"
            . "}\n"
            . "function nincs() {\n"
            . "  for (const ido of idok) {\n"
            . "    val = document.querySelector('input[name=' + ido + ']:checked').value;\n"
            . "    if (val < 1) {\n"
            . "      document.getElementsByName(ido)[0].checked = true;\n"
            . "    }\n"
            . "  }\n"
            . "}\n"
            . "//--></script>\n"
            . "<form method='post' action=''>\n"
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

    } else { // nem admin, hanem tanár -> a listát írjuk ki
        $TABLA = "<table id=\"lista\">\n";
        # Ha egyáltalán itt van...
        if (isset($TANAR->IDO_min)) {
            $elozoOra = 0;
            # ha van páratlan, akkor csak egyesével lépkedünk, egyébként kettesével
            for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido+=(2-$TANAR->ODD)) {
                # minden óra után rakunk egy vonalat
                $ora = floor($ido/12);
                if ($ora != $elozoOra) {
                    $elozoOra = $ora;
                    $TABLA .= '<tr class="borderTop">';
                }
                else {
                    $TABLA .= '<tr>';
                }
                # ha ebben az időpontban van foglalás, akkor kiírjuk
                if (isset($TANAR->fogado_ido[$ido]['diak'])) $diak = $TANAR->fogado_ido[$ido]['diak'];
                else $diak = 0;
                $TABLA .= '<td' . ($diak=='-2'?' class="szuloi"':'') . '>' . FiveToString($ido)
                    . '<td> &ndash; </td><td>' . ($diak>0?$TANAR->fogado_ido[$ido]['dnev']:'&nbsp;') . "</td></tr>\n";
            }
            $Fejlec = preg_replace('/<!--#-->/', '<br><input type="button" value="Nyomtatás" onClick="window.print()">', $Fejlec);
        }
        $TABLA .= "</table>\n";

    }

    return $Fejlec . $TABLA;
}

