<?
require('fogado.inc');

include("user.class");
$USER = new User();

Head("Fogad��ra - " . $USER->dnev);

print "\n<h3>Fogad��ra: " . $FA->datum . "<br>\n"
	. $USER->dnev . " " . $USER->onev . "<br>\n"
	. "<font size=-1>(Oszt�lyf�n�k: " . $USER->ofonev . ")</h3>\n";

if ($res = pg_query("SELECT MIN(ido) AS eleje, MAX(ido) AS vege FROM Fogado WHERE fid=".fid." AND tanar=".$USER->ofo." AND diak=-2")) {
	$szuloi = pg_fetch_array($res);
	$SzuloiSor = "<br><b>" . FiveToString($szuloi['eleje']) . "-" . FiveToString($szuloi['vege']+1) . " -- sz�l�i �rtekezlet</b>\n";
	$SzuloiEleje = $szuloi['eleje'];
}

if ($res = pg_query("SELECT ido, tnev FROM Fogado, Tanar WHERE Tanar.id=tanar"
			. " AND fid=".fid." AND diak=".$USER->id." ORDER BY ido")) {
	while ($sor = pg_fetch_array($res)) {
		if ($SzuloiEleje < $sor['ido']) {
			$Output .= $SzuloiSor;
			$SzuloiSor = "";
		}
		$Output .= "<br>".FiveToString($sor['ido'])." -- ".$sor['tnev']."\n";
	}
}
$Output .= $SzuloiSor;

print $Output;

Tail();
pg_close ($db);

?>

