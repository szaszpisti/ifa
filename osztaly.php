<?
require_once('fogado.inc.php');
Head('Osztalyok', '', 'osztaly');

$f = $DOCUMENT_NAME;
echo "<table>\n"; # <!-- border=1 cellpadding=1 cellspacing=1> -->
echo "<tr><td colspan=3><a href=\"admin.php?tip=admin&amp;id=0\" target=duma>ADMIN</a><td>&nbsp;\n";

// az osztályok azonosítója és megjelenítési módja az OSZTALY fájlban van,
// soronként id1;nev1;id2;nev2 stb. alakban - ezt dolgozzuk fel itt

$OSZTALY_file = file('OSZTALY');
@array_walk($OSZTALY_file, 'file_trim');

foreach ($OSZTALY_file as $oszt) {
	// $O = array('id1', 'nev1', 'id2', 'nev2'), vagyis kétszer hosszabb
   $O = explode(';', $oszt);

	// megkeressük a max sorhosszt a táblázat méretéhez
   if (sizeof($O) > $oMax) $oMax = sizeof($O);
   $OSZTALY[] = $O;
}

foreach ($OSZTALY as $oszt) {
   print "<tr>";
   for ($i=0; $i<sizeof($oszt); $i+=2) {
      echo "<td><a href=\"?o=" . $oszt[$i] . "\">" . $oszt[$i+1] . "</a>";
   }
   for ( ; $i<$oMax; $i+=2) {
      echo "<td>&nbsp;";
   }
   print "\n";
}
echo "<tr><td colspan=3><a href=\"?o=t\">tanárok</a><td>&nbsp;<td>&nbsp;\n";
echo "</table>\n\n";

// Ha van osztály paraméter, akkor az adott osztály listáját írjuk ki

if (isset($_REQUEST['o'])) {
	$o = $_REQUEST['o'];
	print "<h2></h2>\n"; # csak egy kis helyet csinálunk
	if ($o == "t") $q = "SELECT id, tnev AS dnev FROM Tanar ORDER BY tnev";
	else $q = "SELECT * FROM Diak WHERE oszt='$o' ORDER BY dnev";

	$res =& $db->query($q);
	if (DB::isError($res)) { die($res->getMessage()); }

	while ($res->fetchInto($row)) {
		print "<a href=\"" . ($o=='t'?'tanar.php?tip=tanar&amp;':'fogado.php?tip=diak&amp;')
			. "id=" . $row['id'] . "\" target=duma>" . $row['dnev'] . "</a><br>\n";
	}
	print "\n";
}
?>

</body>
</html>
