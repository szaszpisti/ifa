<?
require('fogado.inc');
Head('Névsor');
$Szulo = 'szulo.php';

if (isset($VAR_o)) {
	$o = $VAR_o;
	print "<h2>$VAR_oszt</h2>\n\n<ul>";
	if( $result = pg_exec("SELECT * FROM Ember_nevsor WHERE oszt='$o'")) {
		$rows = pg_numrows($result);
		for($i=0; $i<$rows; $i++) {
			$sor = pg_fetch_array($result, $i);
			print "<li><a href=" . $Szulo . "?id=" . $sor['esz'] . ">" . $sor['enev'] . "</a>\n";
		}
	}
	print "</ul>\n";
}

Tail();
return 0;
?>
