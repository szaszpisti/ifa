<?
require('fogado.inc');
Head('Osztályok');

print "<base target=duma>";

print "<b>\n";

if( $result = pg_exec("SELECT * FROM Osztaly_view")) {
	$rows = pg_numrows($result);
	for($i=0; $i<$rows; $i++) {
		$sor = pg_fetch_array($result, $i);
		print "<li><a href='szulo.php?o=".$sor['oszt']."&oszt=".$sor['onev']."'>".$sor['onev']."</a>\n";
	}
}

print "\n</body>\n</html>\n";
?>
