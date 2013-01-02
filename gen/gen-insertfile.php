#!/usr/bin/php
<?php

if ($argc != 2)
    die("Az első paraméterben megadott névvel\n"
        . "létrehozott fájlba írja az SQL INSERT-eket.\n");
if (file_exists($argv[1]))
    die("Már létezik a fájl, nem merek bele írni: " . $argv[1] . "\n");

$outfile = $argv[1];

$fVnev = file('csaladnev.txt');
$fKnev = file('keresztnev.txt');
$fOsztaly = file('OSZTALY');

$adminPwd = md5('a');
$tanarPwd = md5('t');
$diakPwd = md5('d');

$OUT = "BEGIN TRANSACTION;\n\n";

function file_trim_tomb(&$value, $key) { $value = array($key, trim($value)); }
function file_trim(&$value, $key) { $value = trim($value); }

function nev () {
    global $fVnev, $aKnev, $vmax, $kmax;
    $v = rand(1, $vmax);
    $k = rand(0, $kmax);
    $nev = $fVnev[$v][1] . " " . $aKnev[$k][1];
    $fVnev[$v] = $fVnev[$vmax]; array_pop($fVnev); $vmax -= 1;
    $aKnev[$k] = $aKnev[$kmax]; array_pop($aKnev); $kmax -= 1;
    return (array($v, $nev));
}

// A végére rakjuk az elsőt, mert a 0 fönntartott az adminnak
$fVnev[sizeof($fVnev)]=$fVnev[0];

@array_walk($fVnev, 'file_trim_tomb');
@array_walk($fKnev, 'file_trim');

// azokra a sorokra, melyek számmal kezdődnek, levágja a számokat az elejéről
$aKnevek = preg_replace ('/^[0-9]+: /', '', preg_grep ('/^[0-9]+: /', $fKnev));

// először egy hosszú stringbe fűzzük a sorokat, aztán ezt daraboljuk tömbbe
$aKnev = explode (' ', implode (' ', $aKnevek));
@array_walk($aKnev, 'file_trim_tomb');

@array_walk($fOsztaly, 'file_trim');

$vmax = count($fVnev)-1;
$kmax = count($aKnev)-1;

$oMax = 0;
foreach ($fOsztaly as $oszt) {
    $O = explode(';', $oszt);
    if (sizeof($O) > $oMax) $oMax = sizeof($O);
    $OSZTALY[] = $O;
}

# az Admin és egy általános fogadóóra bejegyzés beszúrása
$OUT .= "INSERT INTO Admin VALUES (1, '2000-01-01', 192, 228, 2, '2000-01-01 08:00', '3000-01-01 12:00');\n\n";

$osztalyOUT = '';
$diakOUT = "INSERT INTO Diak_base VALUES (0, '$adminPwd', 'Admin', '', '', 0, '');\n\n";
$tanarOUT = '';
foreach ($OSZTALY as $oszt) {
    for ($o=0; $o<sizeof($oszt); $o+=2) {
        $oid = $oszt[$o];

        // generálunk egy ofő nevet a soron következő osztályhoz
        $OFO = array_merge(nev(), array ($oid, $oszt[$o+1]));

        // az osztályfőnököket berakjuk a tanárlistába
        $TANAR[] = $OFO;

        $osztalyOUT .= "INSERT INTO Osztaly VALUES ('" . $OFO[2] . "','" . $OFO[3] . "'," . $OFO[0] . ");\n";
        $n = rand(25, 35);
        for ($i=0; $i<=$n; $i++) {
            list($id, $dnev) = nev();
            $diakOUT .= "INSERT INTO Diak_base VALUES ($id, '$diakPwd', '$dnev', '" . $OFO[2] . "');\n";
        }
    }
}

// még néhány nevet hozzáadunk a tanárokhoz
$n = rand(15, 25);
for ($i=0; $i<=$n; $i++) {
    list($id, $tnev) = nev();
    $tanarOUT .= "INSERT INTO Tanar VALUES ($id,'$tanarPwd',NULL,'$tnev');\n";
}

$OUT .= "$tanarOUT\n$osztalyOUT\n$diakOUT\nCOMMIT;\n";
$fh = fopen ($outfile, 'w');
fwrite ($fh, $OUT);
fclose ($fh);

?>
