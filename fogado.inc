<?
require_once('fogado.ini');

$db = pg_connect(
			  "host=$db_host "
			. "dbname=$db_name "
			. "user=$db_user "
			. "password='$db_pass'"
	) or die("Nem mén a kapcsolat, hö!");

set_include_path(get_include_path() . ':./classes');

# if (!kuki_teszt()) { print "<b>Kérem engedélyezze a böngészõjében a sütik (cookies) fogadását!</b>"; exit; }

require_once('fogadoora.class');
$FA = new Fogadoora();
define('fid', $FA->id);

function hiba($err) {
	print "<p><hr><b>!!! - $err - !!!</b><hr>\n";
	Ulog (0, $err);
}

// az ini idõk átszámolása
$FogadoIdo = array (
	TimeToFive($Fogado_tartam[0], $Fogado_tartam[1]),
	TimeToFive($Fogado_tartam[2], $Fogado_tartam[3])
);

$SzuloiIdo = array (
	TimeToFive($Szuloi_tartam[0], $Szuloi_tartam[1]),
	TimeToFive($Szuloi_tartam[2], $Szuloi_tartam[3])
);


// Idõ átszámítása 5 perces sorszámúról HH:MM formátumra
function FiveToString($ido) { return sprintf("%02d:%02d", floor($ido/12), ($ido%12)*5); } // volt: tim
function FiveToTime($ido) { return array ('ora' => floor($ido/12), 'perc' => ($ido%12)*5); }
function TimeToFive($ora, $perc) { return $ora*12+floor($perc/5); }

/*
 * $SELECT: az óra választó konstans string
 */
$SELECT = "<select name=#NAME#>";
for ($i=$Kiir_tartam[0]; $i<=$Kiir_tartam[1]; $i++) {
	$SELECT .= "<option value=" . $i*12 . ">$i";
}
$SELECT .= "</select>";


/* SelectIdo($name_ora, $name_perc, $ora)
$name_ora : az óra select objektum neve az ûrlapon
$name_perc: a perc select objektum neve az ûrlapon
$ido : a jelölendõ idõpont (five)
*/
function SelectIdo($name_ora, $name_perc, $ido){
	global $SELECT;
	$ora = 12*floor($ido/12);
	$perc = $ido - $ora;

	$ret = preg_replace("/value=$ora>/", "value=$ora selected>", $SELECT);
	$ret = preg_replace("/#NAME#/", "$name_ora", $ret);

	$ret .= preg_replace("/value=$perc>/", "value=$perc selected>",
				"<select name=$name_perc><option value=0>00<option value=2>10<option value=4>20" .
				"<option value=6>30<option value=8>40<option value=10>50</select>");
	return $ret;
}

function SelectTartam($name) {
	return preg_replace("/#NAME#/", "$name", "<select name=#NAME#>"
		. "<option value=1>5<option value=2 selected>10"
		. "<option value=3>15<option value=4>20</select>");
}


function Head($cimsor, $onload = '') {
	header('Pragma: no-cache');
	header('Cache-Control: no-store, no-cache, must-revalidate, post-c heck=0, pre-check=0');
	header('Expires: Mon,26 Jul 1980 05:00:00 GMT');

print <<< EnD
<html>
<head>
  <title>$cimsor</title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
  <meta http-equiv="Expires" content="Tue, 20 Aug 1996 14:25:27 GMT">
  <link rel="stylesheet" href="default.css" type="text/css">
</head>
EnD;
print "\n\n<body$onload>\n";
}

function Tail() {
	print "\n<p><hr><a href=mailto:hiena@szepi.hu></a>\n";
	print "<address><a href=\"mailto:dugo@szepi.hu\">dugo@szepi.hu</a></address>\n";
	print "</body>\n</html>\n";
}

function ulog($id, $s) {
	$q = "INSERT INTO Ulog (ido, uid, host, log) VALUES ('"
		. date("Y-m-d H:i:s")."', ".$id.", '".$_SERVER['REMOTE_ADDR']."', '".pg_escape_string($s)."')";
	pg_query($q);
}

?>
