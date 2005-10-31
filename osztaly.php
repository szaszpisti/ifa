<?
/*
 *   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Szász Imre.
 *
 *   Ez egy szabad szoftver; terjeszthetõ illetve módosítható a GNU
 *   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
 *   késõbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

require_once('fogado.inc.php');
Head('Osztalyok', '', 'osztaly');

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
$oMax /= 2; // dupláját számoltuk

echo "<table>\n"; # <!-- border=1 cellpadding=1 cellspacing=1> -->
echo "<tr><td colspan=$oMax><a href=\"admin.php?tip=admin&amp;id=0\" target=duma>ADMIN</a>\n";

foreach ($OSZTALY as $oszt) {
	print "<tr>";
	for ($i=0; $i<sizeof($oszt)/2; $i++) {
		echo "<td><a href=\"?o=" . $oszt[2*$i] . "\">" . $oszt[2*$i+1] . "</a>";
	}
	for ( ; $i<$oMax; $i++) {
		echo "<td>&nbsp;";
	}
	print "\n";
}
echo "<tr><td colspan=$oMax><a href=\"?o=t\">tanárok</a>\n";
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
