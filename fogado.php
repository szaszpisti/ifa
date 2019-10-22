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
 * @file fogado.php
 * Egy diák számára kiírja a fogadóóra jelentkezési táblázatot
 * vagy az összesítést.
 */

#require_once('login.php');
#require_once('ifa.inc.php');

# Egy saján összehasonlítást készítünk, ezzel fogjuk a $FOGADO-t a nevek szerint rendezni
# Kell hozzá a php5-intl csomag
$coll = collator_create( 'hu_HU.UTF-8' );

function myCmp($a, $b){
    global $coll;
    return collator_compare( $coll, $a['nev'], $b['nev'] );
}

// egy tanár-sor a táblázatban
function table_row($K, $tid, $t) {
    $tmp = '';
    for ($i=1; $i<count($K); $i++) { // 1-től kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
        $span = (count($K[$i])>1)?" colspan='".count($K[$i])."'":"";
        if (count($K[$i]) == 0) continue;
        switch ($K[$i][0]) {
            case foglalt: $tmp .= "  <td class='foglalt'$span>&nbsp;\n"; break;
            case szuloi:  $tmp .= "  <td class='szuloi'$span>&nbsp;\n"; break;
            case szabad:  $tmp .= "  <td class='szabad'$span><input type='radio' name='r$tid' value='$t'>\n"; break;
            case szabad2: $tmp .= "  <td class='szabad'$span>&nbsp;\n"; break;
            case sajat:   $tmp .= "  <td class='sajat'$span><input type='checkbox' name='c$tid' checked>\n"; break;
            case sajat2:  $tmp .= "  <td class='sajat'$span>&nbsp;\n"; break;
        }
        $t += count($K[$i]) * 2;
    }
    return $tmp;
}

/**
 * Egy tanár-sor kiírása
 *
 * @param tanar $tanar - az ő sorát írjuk ki
 */
function tanar_ki($user, $tanar) {
    global $FA, $K;
    // TANAR: [0]['diak']=25, [1]['diak']=-1, ...

    /* $K A színezéshez használjuk, ebben vannak számolva az egymás utáni
    cellatípusok, hogy lehessen csoportosítani őket (colspan) */
    $K[0] = array(array()); // páros időket tesszük ebbe
    $K[1] = array(array()); // páratlanokat

    $ElozoCellaSzin = null; # az előző cella értéke
    // $i-vel megyünk végig az időpontokon
    for ($i=$FA->IDO_min; $i<$FA->IDO_max; $i++) {
        // ha az aktuális tanárnak nincsenek páratlan időpontjai, és éppen ott tartunk, akkor mehetünk tovább
        if (!isset($tanar['paratlan']) && $i%2) { continue; }

        if (!isset($tanar[$i])) { $CellaSzin = foglalt; }
        else switch ($tanar[$i]) {
            case -2:
                if ( ($user->ofo == $tanar['id']) || ADMIN ) { $CellaSzin = szuloi; }
                else { $CellaSzin = foglalt; }
                break;
            case -1:  // az előző folytatása
                if ( $ElozoCellaSzin == szabad ) { $CellaSzin = szabad2; }
                if ( $ElozoCellaSzin == sajat ) { $CellaSzin = sajat2; }
                break;
            case 0:
                $CellaSzin = szabad; break;
            case $user->id:
                $CellaSzin = sajat;
                break;
            default:
                $CellaSzin = foglalt; break;
        }
        if ( ( $CellaSzin != $ElozoCellaSzin && $CellaSzin != szabad2 && $CellaSzin != sajat2 ) || $CellaSzin == szabad ) {
            array_push ( $K[$i%2], array($CellaSzin) );
            array_push ( $K[1-$i%2], array() );
        }
        else {
            array_push ( $K[$i%2][count($K[$i%2])-1], $CellaSzin );
        }
        $ElozoCellaSzin = $CellaSzin;
    }

    $tmp = "\n<tr><th align='left' nowrap" . (isset($tanar['paratlan'])?" rowspan='2' valign='top'":"") . ">&nbsp;"
        . (ADMIN?"<a href='?o=" . $user->oszt . "&tip=tanar&amp;id=" . $tanar['id'] . "'>" . $tanar['nev'] . "</a>":$tanar['nev']) . "\n";

// párosak:
    $tmp .= table_row($K[0], $tanar['id'], $FA->IDO_min);
    $tmp .= "  <td><input type=button value=x onClick='torol('r" . $tanar['id'] . "')'>\n";

// páratlanok:
    if (isset($tanar['paratlan'])) {
        $tmp .= "<tr>" . table_row($K[1], $tanar['id'], $FA->IDO_min+1);
    }

    return $tmp;

}

/**
 * Minden időpontfoglalásra megnézzük, hogy az működhet-e?
 * - ha a tanár foglalt, akkor nem iratkozhat fel,
 * - ha a szülő foglalt, akkor csak figyelmeztet (jöhetnek többen is)
 *
 * @param tanar $Teacher
 * @param ido $Time
 *
 * @return array (bool b, string s)
 * - b: true ha végre kell hajtani a változtatást
 * - s: a logba írandó üzenet, ha üres, akkor nem kell írni
*/
function ValidateRadio ( $Teacher, $Time ) {
// (ezeket jó lenne triggerként berakni a tábla-definícióba...)
    global $FOGADO, $user;
    $ret = array ('valid' => true, 'value' => NULL);
    if ( $FOGADO[$Teacher][$Time] != 0 ) {
        return array(false, '<b>' . $FOGADO[$Teacher]['nev'] . " " . FiveToString($Time) . " időpontja már foglalt, ide nem iratkozhat fel!</b>");
    }

    foreach ( $FOGADO as $tan ) {
        if ( isset($tan[$Time]) &&  $tan[$Time] == $user->id ) {
            return array(false, "<b>Önnek már foglalt a " . FiveToString($Time) . " időpontja (" . $tan['nev'] . ") - előbb arról iratkozzon le!</b>");
        }
    }
    foreach ( array_keys($FOGADO[$Teacher]) as $k ) {
        if ( $FOGADO[$Teacher][$k] == $user->id ) {
            return array(false, '<b>' . $FOGADO[$Teacher]['nev'] . " " . FiveToString($k) . " időpontjára már feliratkozott - ha változtatni akar, előbb azt törölje!</b>");
        }
    }
    if ( isset($FOGADO[$user->ofo][$Time]) &&  $FOGADO[$user->ofo][$Time] == -2 ) {
        return array(true, "<b>Önnek szülői értekezlete van ebben az időpontban (" . FiveToString($Time) . ")!</b>");
    }
    return array(true, NULL);
}



function fogado($osszesit=FALSE) {
    require_once('user.class.php');

    global $FA;
    global $db;
    global $user;
    global $FOGADO;

    /** @brief Már foglalt időpont */
    define ('foglalt', 'foglalt');
    /** @brief A blokkban az első szabad időpont */
    define ('szabad', 'szabad');
    /** @brief Ha a megelőző időpont szabad volt */
    define ('szabad2', 'szabad2');
    /** @brief Szülői értekezlet */
    define ('szuloi', 'szuloi');
    /* @brief A blokkban az első saját időpont */
    define ('sajat', 'sajat'); //!<@brief A blokkban az első saját időpont
    /** @brief Ha a megelőző időpont saját volt */
    define ('sajat2', 'sajat2');

    /*
     * Egy diák számára kiírja a fogadóóra táblázatot az összes tanárral
     */

    $out = '';

    $USER_LOG = array();

    $queryString = "?tip=diak&amp;id=" . $user->id;
    $Fejlec = 
          "  <script language=JavaScript type='text/javascript'><!--\n"
        . "    function torol(sor) {\n"
        . "    eval('var s = document.tabla.'+sor);\n"
        . "    for (var i=0; i<s.length; i++)\n"
        . "      s[i].checked=0;\n"
        . "    }\n"
        . "  //--></script>\n\n"
        . "<table width='100%'><tr><td>\n"
        . "<h3>" . $user->dnev . " " . $user->onev
        . " <span class='kicsi'>(" . $FA->datum . ")</span><br>\n"
        . "<span class='kicsi'>(Osztályfőnök: " . $user->ofonev . ")</span></h3>\n"
        . "<td align=right valign=top><span class='noprint sans'></tr></table>\n";

    // Ha a "leírást" kell kitenni:
    if (isset($_REQUEST['tartalom']) && $_REQUEST['tartalom'] == 'leiras') {
        $out .= $Fejlec;
        $out .= "<hr>\n";
        $content = file_get_contents ('leiras.html');
        $tmp = preg_split ('/\n{2,}/', trim($content));
        $out .= join ("\n\n", ( array_slice($tmp, 1, -1 )) ); # head és tail nélkül a leírás.html tartalma
        #Tail();
        return 0;
    }

    // Ha az összesítést kell kiírni (akkor is, ha nincs bejelentkezési idő):
    if ((!ADMIN && !$FA->valid) || $osszesit) {
        # ide berakjuk még a "Nyomtatás" gombot:
        $out .= preg_replace('/<!--#-->/', '<br><input type="button" value="Nyomtatás" onClick="window.print()">', $Fejlec);
        $out .= osszesit($user, $FA, $db);
        #Tail();
        if (!$FA->valid) @session_destroy();
        return $out;
    }

    /*
    A fejléc sorok kiíratásához
    IDO: ebben lesznek a kiírandó időpontok
    IDO[16] = (30, 40, 50)
    IDO[17] = (00, 10, 20, ...)
    */
    for ($ido=$FA->IDO_min; $ido<$FA->IDO_max; $ido+=2) {
        $ora = floor($ido/12);
        if (!isset($IDO[$ora]))
            $IDO[$ora] = array();
        array_push ($IDO[$ora], ($ido % 12)/2);
    }

    $A = "\n<tr bgcolor='lightblue'><td rowspan='2'>";
    $B = "\n<tr bgcolor='lightblue'>";

    if (!sizeof($IDO)) die('Nincsenek még időpontok!');
    foreach (array_keys($IDO) as $ora) {
        $A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
        foreach (array_values($IDO[$ora]) as $perc )
            $B .= "<td>" . $perc . "0";
    }
    $TablazatIdosor = $A . $B;

    // Az összes fogadó tanár nevét kigyűjtjük // FOGADO[id]=('id', 'nev')
    $res = $db->query( "SELECT tanar, tnev FROM Fogado, Tanar "
                    . "  WHERE fid=" . fid . " AND tanar=id "
                    . "    GROUP BY tanar, tnev "
                    . "    ORDER BY tnev");

    $FOGADO = array();
    foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $tanar) {
        $FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['tnev']);
    }

    // mindegyikhez az összes idő => elfoglaltságot (A FOGADO-hoz rakunk még mezőket)
    // FOGADO[id]=('id', 'nev', 'paratlan', 'ido1', 'ido2', ... )
    $res = $db->query( "SELECT tanar, ido, diak FROM Fogado "
                    . "  WHERE fid=" . fid
                    . "    ORDER BY ido");

    foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $sor) {
        // Ha egy páratlan sorszámú időpontban lehet érték..., azt jelezzük
        if ( ($sor['ido']%2 == 1) && ($sor['diak']>=0) ) { #  && ($sor['diak'] != "") ) {
            $FOGADO[$sor['tanar']]['paratlan'] = 1;
        }
        $FOGADO[$sor['tanar']][$sor['ido']] = $sor['diak'];
    }

    // Az Ulog-ot mög köllene csinálni, hogy az adminnál 0 legyen az id
    // és figyelmeztetéseket ne logolja
    //
    // checkboxok ellenőrzése (leiratkozás)
    //
    if ( isset($_POST['page']) &&  $_POST['page'] == 'mod' ) {
        $db->beginTransaction();
        foreach ( $FOGADO as $tanar ) {
            $v = "c" . $tanar['id'];
            foreach ( array_keys($tanar) as $Time ) {
                if ( ( $tanar[$Time] == $user->id ) && !isset($_POST[$v]) ) {
                    $q = "UPDATE Fogado SET diak=0 WHERE fid=" . fid . " AND tanar=" . $tanar['id'] . " AND ido=$Time";
                    if ( $db->query($q) ) {
                        $FOGADO[$tanar['id']][$Time] = "0";
                        $USER_LOG[] = "--- RENDBEN: " . $FOGADO[$tanar['id']]['nev'] . ", " . FiveToString($Time) . " - törölve.";
                        Ulog($user->id, $q);
                    }
                    else { Ulog($user->id, "Légy került a levesbe: $q!"); }
                }
            }
        }
        $db->commit();
    }

    //
    // rádiógombok ellenőrzése (feliratkozás)
    //
    reset($_POST);
    $db->beginTransaction();
    foreach($_POST as $k => $v){
        if ( preg_match ("/^r([0-9]+)$/", $k, $match) ) {
            $Teacher = $match[1];
            $Time = $v;
            $validate = ValidateRadio ($Teacher, $Time);
            if ( $validate[1] ) {
                Ulog($user->id, $validate[1]);
                $USER_LOG[] = $validate[1];
            }
            if ( $validate[0] ) { // rendben, lehet adatbázisba rakni
                $q = "UPDATE Fogado SET diak=" . $user->id . " WHERE fid=" . fid . " AND tanar=$Teacher AND ido=$Time";
                if ( $db->query($q) ) {
                    $FOGADO[$Teacher][$Time] = $user->id;
                    $USER_LOG[] = "--- RENDBEN: " . $FOGADO[$Teacher]['nev'] . ", " . FiveToString($Time) . " - bejegyezve.";
                    Ulog($user->id, $q);
                }
                else { Ulog($user->id, "Légy került a levesbe: $q!"); }
            }
        }
    }
    $db->commit();

    # 10 vagy valahány soronként kirakjuk a fejlécet, hogy lehessen követni
    $szamlalo = 0;
    # $TablaOutput .= $TablazatIdosor;
    $TablaOutput = '';

    if (count($FOGADO) > 0) {
        uasort($FOGADO, "myCmp");
        foreach ( $FOGADO as $tanar ) {
            if (($szamlalo%8) == 0) $TablaOutput .= $TablazatIdosor;
            $TablaOutput .= tanar_ki($user, $tanar);
            $szamlalo++;
        }
    }


    // Itt jön az összes kiírás

    $out .= $user->fejlec();

    if ($USER_LOG) {
        $out .= "<hr>\n";
        $out .= "<div class='userlog'>\n";
        foreach ($USER_LOG as $log) $out .= "$log<br>\n";
        $out .= "</div>\n";
    }

    $out .= "\n<form name='tabla' method='post' action=''><table border='1'>"
        . "<tr><td colspan='" . (($FA->IDO_max-$FA->IDO_min)/2+2) . "' align='right' class='right'>\n"
        . "  <input type='submit' value=' Mehet '>\n"
        . $TablaOutput
        . "<tr><td colspan='" . (($FA->IDO_max-$FA->IDO_min)/2+2) . "' align='right' class='right'>\n"
        . "  <input type='hidden' name='page' value='mod'>\n"
        . "  <input type='submit' value=' Mehet '>\n"
        . "</table>\n\n"
        . "</form>\n";

#    Tail();
    return $out;

}
