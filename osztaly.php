<?  require_once('fogado.inc'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>Osztályok</title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
  <link rel="stylesheet" href="osztaly.css" type="text/css">
</head>
<body>

<? $f = $DOCUMENT_NAME ?>
<table><!-- border=1 cellpadding=1 cellspacing=1> -->
<tr><td colspan=3><a href=admin.php?tip=admin&id=0 target=duma>ADMIN</a><td>&nbsp;
<tr><td><a href=<?=$f?>?o=d10a>7.a</a> <td>&nbsp;
<tr><td><a href=<?=$f?>?o=d09a>8.a</a> <td>&nbsp;
<tr><td><a href=<?=$f?>?o=d08a>9.a</a> <td><a href=<?=$f?>?o=d08b>9.b</a>
<tr><td><a href=<?=$f?>?o=d07a>10.a</a><td><a href=<?=$f?>?o=d07b>10.b</a>
<tr><td><a href=<?=$f?>?o=d06a>11.a</a><td><a href=<?=$f?>?o=d06b>11.b</a>
<tr><td><a href=<?=$f?>?o=d05a>12.a</a><td><a href=<?=$f?>?o=d05b>12.b</a>
<tr><td colspan=3><a href=<?=$f?>?o=t>tanárok</a><td>&nbsp;<td>&nbsp;
</table>

<?
if (isset($_REQUEST['o'])) {
	$o = $_REQUEST['o'];
	print "<h2></h2>\n"; # csak egy kis helyet csinálunk
	if ($o == "t") $q = "SELECT id, tnev AS dnev FROM Tanar ORDER BY tnev";
	else $q = "SELECT * FROM Diak WHERE oszt='$o' ORDER BY dnev";

	if( $result = pg_query($q)) {
		$rows = pg_num_rows($result);
		for($i=0; $i<$rows; $i++) {
			$sor = pg_fetch_array($result, $i);
			print "<a href=".($o=='t'?'tanar.php?tip=tanar&':'fogado.php?tip=diak&')."id=" . $sor['id'] . " target=duma>" . $sor['dnev'] . "</a><br>\n";
		}
	}
	print "\n";
}
?>

</body>
</html>
