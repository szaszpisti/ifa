<?
if (defined("fogado.inc")) return;
define("fogado.inc", "1");

$db = pg_connect("host=localhost dbname=fogado user=fadmin"
	. " password='" . '$1$JV6c.fJ6$PZLyMROI/Pct3ywWyNhgQ.' . "'")
   	or die("Nem mén a kapcsolat, hö!");

set_include_path(get_include_path() . ":./classes");

session_start();
if ($_SESSION['admin']) define("ADMIN", 1);
else define("ADMIN", 0);
session_write_close();

include("fogadoora.class");
$FA = new Fogadoora();
define("fid", $FA->id);

function hiba($err) {
	print "<p><hr><b>!!! - $err - !!!</b><hr>\n";
	Ulog (0, $err);
}

// Idõ átszámítása 5 perces sorszámúról HH:MM formátumra
function tim($time) { return gmdate('H:i', $time*300); }

/*
// default kezdõ-, vég-  óra, perc:
*/
$KezdoOra=16;       $KezdoPerc=0;       $VegOra=18;       $VegPerc=0;
$SzuloiKezdoOra=17; $SzuloiKezdoPerc=0; $SzuloiVegOra=17; $SzuloiVegPerc=30;

$ORA = "<select name=#NAME#>";
for ($i=14; $i<21; $i++) { $I = sprintf("%02d", $i); $ORA .= "<option value=$I>$I"; }
$ORA .= "</select>";
function ora($name, $o){
	global $ORA;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/value=$o>/", "value=$o selected>", $ORA);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

$PERC = "<select name=#NAME#>";
foreach (array('00', '10', '20', '30', '40', '50') as $i ) { $PERC .= "<option value=".floor($i/10).">$i"; }
$PERC .= "</select>";
function perc($name, $p){
	global $PERC;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/>$p/", " selected>$p", $PERC);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

function tartam($name) {
	return preg_replace("/#NAME#/", "$name", "<select name=#NAME#>"
		. "<option value=1>5<option value=2 selected>10"
		. "<option value=3>15<option value=4>20</select>");
}


function Head($cimsor) {
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
  <script language=JavaScript><!--
    function torol(sor) {
    eval('var s = document.tabla.'+sor);
    for (var i=0; i<s.length; i++)
      s[i].checked=0;
    }
  //--></script>
</head>
EnD;
print "\n\n\n<body>\n";
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
