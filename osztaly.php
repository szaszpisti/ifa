<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>Osztályok</title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
  <link rel="stylesheet" href="osztaly.css" type="text/css">
</head>
<body>

<table><!-- border=1 cellpadding=1 cellspacing=1> -->
<tr><td colspan=3 align=center><a href=install.php?page=1 target=duma>ADMIN</a><td>&nbsp;<td>&nbsp;
<tr><td><a href=?o=d09a>7.a</a> <td>&nbsp;<td>&nbsp;
<tr><td><a href=?o=d08a>8.a</a> <td>&nbsp;<td>&nbsp;
<tr><td><a href=?o=d07a>9.a</a> <td><a href=?o=d07b>9.b</a> <td>&nbsp;
<tr><td><a href=?o=d06a>10.a</a><td><a href=?o=d06b>10.b</a><td>&nbsp;
<tr><td><a href=?o=d05a>11.a</a><td><a href=?o=d05b>11.b</a><td>&nbsp;
<tr><td><a href=?o=d04a>12.a</a><td><a href=?o=d04b>12.b</a><td><a href=?o=d04c>12.c</a>
<tr><td colspan=3><a href=?o=t>tanárok</a><td>&nbsp;<td>&nbsp;
</table>

<?
require('fogado.inc');

if (isset($VAR_o)) {
	$o = $VAR_o;
	print "<h2>$VAR_oszt</h2>\n\n";
	if( $result = pg_exec("SELECT * FROM Ember_nevsor WHERE oszt='$o'")) {
		$rows = pg_numrows($result);
		for($i=0; $i<$rows; $i++) {
			$sor = pg_fetch_array($result, $i);
//			print "<li><a href=$Szulo?o=" . $o . "&id=" . $sor['esz'] . " target=duma>" . $sor['enev'] . "</a>\n";
			print "<a href=".($o=='t'?'tanar.php':$Szulo)."?o=" . $o . "&id=" . $sor['esz'] . " target=duma>" . $sor['enev'] . "</a><br>\n";
		}
	}
	print "\n";
}

?>

</body>
</html>
