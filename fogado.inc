<?

$DataBase = "iskola";
$Szulo = "fogado.php";
// $Host = "www.szepi.hu";

// $db = pg_connect("dbname=" . $DataBase . " user=" . $_SERVER['REMOTE_USER']) or die("Nem mén a kapcsolat, hö!");
$db = pg_connect("dbname=" . $DataBase . " user=szaszi") or die("Nem mén a kapcsolat, hö!");

function Err($hiba) {
	print "<p><hr><b>!!! - $hiba - !!!</b><hr>\n";
}

function Head($cimsor) {
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

function get_var($var){
	global ${"VAR_".$var}, $$var;
	if (isset(${"VAR_".$var})) {
		$$var = preg_replace("/^.*#/", '', ${"VAR_".$var});
//		print $var."='".$$var."' (".${"VAR_".$var}.") | ";
	}
}

function Diak_select($oszt, $id) {
	if ( $result = pg_exec("SELECT esz AS esz,enev FROM Ember WHERE oszt='" . $oszt . "' ORDER BY enev")) {
		$OSZTALY = pg_fetch_all($result);
		$res = "<form>\n<select name=id>\n";
		foreach ($OSZTALY as $d) {
			$res .= "<option value=" . $d['esz'] . (($d['esz']==$id)?" selected>":">") . $d['enev'] . "\n";
		}
		$res .= "</select>\n<input type=submit value=' OK '>\n</form>\n";
		return $res;
	}
}

function Osztaly_select($oszt) {
	if( $result = pg_exec("SELECT * FROM Osztaly_view ORDER BY evf, ab")) {
		$ISKOLA = pg_fetch_all($result);
		$res = "<form>\n<select name=o>\n";
		foreach ($ISKOLA as $d) {
			$res .= "<option value=" . $d['oszt'] . (($d['oszt']===$oszt)?" selected>":">") . $d['onev'] . "\n";
		}
		$res .= "</select>\n<input type=submit value=' OK '>\n</form>\n";
		return $res;
	}
}

?>
