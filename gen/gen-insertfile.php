#!/usr/bin/php
<?

if ($argc != 2)
	die("Az els� param�terben megadott n�vvel\n"
		. "l�trehozott f�jlba �rja az SQL INSERT-eket.\n");
if (file_exists($argv[1]))
	die("M�r l�tezik a f�jl, nem merek bele �rni: " . $argv[1] . "\n");

$outfile = $argv[1];

$fVnev = file('csaladnev.txt');
$fKnev = file('keresztnev.txt');
$fOsztaly = file('../OSZTALY');

$adminPwd = md5('x');
$tanarPwd = md5('t');
$diakPwd = md5('d');

$OUT = '';

function file_trim_tomb(&$value, $key) { $value = array($key, trim($value)); }
function file_trim(&$value, $key) { $value = trim($value); }

function nev () {
	global $fVnev, $aKnev;
	$v = rand(1, sizeof($fVnev)-1);
	$k = rand(0, sizeof($aKnev)-1);
	$nev = $fVnev[$v][1] . " " . $aKnev[$k][1];
	$fVnev[$v] = $fVnev[sizeof($fVnev)-1]; array_pop($fVnev);
	$aKnev[$k] = $aKnev[sizeof($aKnev)-1]; array_pop($aKnev);
	return (array($v, $nev));
}

// A v�g�re rakjuk az els�t, mert a 0 f�nntartott az adminnak
$fVnev[sizeof($fVnev)]=$fVnev[0];

@array_walk($fVnev, 'file_trim_tomb');
@array_walk($fKnev, 'file_trim');

// azokra a sorokra, melyek sz�mmal kezd�dnek, lev�gja a sz�mokat az elej�r�l
$aKnevek = preg_replace ('/^[0-9]+: /', '', preg_grep ('/^[0-9]+: /', $fKnev));

// el�sz�r egy hossz� stringbe f�zz�k a sorokat, azt�n ezt daraboljuk t�mbbe
$aKnev = explode (' ', implode (' ', $aKnevek));
@array_walk($aKnev, 'file_trim_tomb');

@array_walk($fOsztaly, 'file_trim');

foreach ($fOsztaly as $oszt) {
	$O = explode(';', $oszt);
	if (sizeof($O) > $oMax) $oMax = sizeof($O);
	$OSZTALY[] = $O;
}

// Mindenekel�tt az Admin �s egy �ltal�nos fogad��ra bejegyz�s besz�r�sa
$OUT .= "INSERT INTO Diak (id, jelszo, dnev, oszt, onev, ofo, ofonev) VALUES (0, '"
	. $adminPwd . "', 'Admin', '', '', 0, '');\n\n";
$OUT .= "INSERT INTO Admin (id, datum, kezd, veg, tartam, valid_kezd, valid_veg) "
	. "VALUES (1, '2000-01-01', 192, 228, 2, '2000-01-01 08:00:00', '3000-01-01 12:00:00');\n\n";

foreach ($OSZTALY as $oszt) {
	for ($o=0; $o<sizeof($oszt); $o+=2) {
		$oid = $oszt[$o];

		// gener�lunk egy of� nevet a soron k�vetkez� oszt�lyhoz
		$OFO = array_merge(nev(), array ($oid, $oszt[$o+1]));

		// az oszt�lyf�n�k�ket berakjuk a tan�rlist�ba
		$TANAR[] = $OFO;

		$Ostring = "'".$OFO[2]."', '".$OFO[3]."', ".$OFO[0].", '".$OFO[1]."'";
		$n = rand(25, 35);
		for ($i=0; $i<=$n; $i++) {
			list($id, $dnev) = nev();
			$q = "INSERT INTO Diak (id, jelszo, dnev, oszt, onev, ofo, ofonev) VALUES ("
				. $id . ", '" . $diakPwd . "', '" . $dnev . "', " . $Ostring . ");";
			$OUT .= $q . "\n";
		}
	}
}
$OUT .= "\n";

// m�g n�h�ny nevet hozz�adunk a tan�rokhoz
$n = rand(15, 25);
for ($i=0; $i<=$n; $i++) {
	$TANAR[] = nev();
}

foreach ($TANAR as $t) {
	list($id, $tnev) = $t;
	$q = "INSERT INTO Tanar (id, jelszo, tnev) VALUES ("
		. $id . ", '" . $tanarPwd . "', '" . $tnev . "');";
	$OUT .= $q . "\n";
}

$fh = fopen ($outfile, 'w');
fwrite ($fh, $OUT);
fclose ($fh);

?>
