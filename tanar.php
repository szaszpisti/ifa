<?
require('fogado.inc');

/*
Dezsoke
fogad: x kezd, veg

      nincs fogad folyt szuloi  diak
15:50  ()    ()    ()     ()    nagyfeju
*/

$ADMIN = 0;

if ( !isset($VAR_id) ) { return 0; }

$id = $VAR_id;

// A tanár adatai
if ( $result = pg_exec("SELECT * FROM Ember WHERE tip='t' AND esz=$id") ) {
	$USER = pg_fetch_array($result);
}

if ( $result = pg_exec("SELECT * FROM Fogado_admin WHERE id=(SELECT MAX(id) FROM Fogado_admin)" )) {
	$FA = pg_fetch_array($result);
}
$fid = $FA['id'];

$QUERY_LOG = array();
$USER_LOG = array();

Head("Fogadóóra - " . $USER['enev']);

print "\n<h3>" . $USER['enev'] . "</h3>\n";

// Idõ átszámítása 5 perces sorszámúról HH:MM formátumra
function tim($time) { return gmdate('H:i', $time*300); }

// Kicsit béna lett: annyi kellene, hogy a Fogadó táblából a diák id-ekhez
// hozzá kell rendelni a teljes nevet és az osztály nevét. Lehet egyszerûbben?

if ($result = pg_exec( "SELECT tanar, ido, diak, o, nev, ' (' || onev || ')' AS onev FROM"
					. "	(SELECT *, X.oszt AS o, X.enev AS nev FROM"
					. "		(SELECT * FROM Fogado AS F"
					. "			LEFT OUTER JOIN Ember AS E"
					. "			ON (F.diak=E.esz AND E.tip='d')"
					. "		) AS X"
					. "		LEFT OUTER JOIN Osztaly_view AS O"
					. "		ON (X.oszt=O.oszt)"
					. "	) AS FOG WHERE fid=$fid AND tanar=$id AND diak IS NOT NULL ORDER BY ido" )) {

	$rows = pg_numrows($result);
	for($i=0; $i<$rows; $i++) {
		$sor = pg_fetch_array($result, $i);
		if ($i==0) { $IDO_min = $sor['ido']; }
		$TANAR[$sor['ido']] = $sor;
	}
	$IDO_max = $sor['ido'];
}

// print "_: $IDO_min, ^: $IDO_max\n";

// Meg kell nézni, hogy van-e benne páratlan, azaz kell-e 5-ösöket írni a táblába?
$ODD = 0;
for ($i=$IDO_min-($IDO_min%2)-1; $i<=$IDO_max; $i+=2) if ( isset($TANAR[$i]['diak']) && $TANAR[$i]['diak']>=0 ) $ODD = 1;

$ORA_min=floor($IDO_min/12);
$ORA_max=floor($IDO_max/12)+1;

$TABLA[-1] = "<tr><th>";
for ($i=0; $i<12; $i+=(2-$ODD)) {
	$TABLA[$i] = "<tr><th>" . sprintf("%02d", $i*5);
}

for ($o=$ORA_min; $o<$ORA_max; $o++) { 
	$TABLA[-1] .= "<th>$o";
	for ($i=0; $i<12; $i+=(2-$ODD)) {
		$Time = $o*12+$i;
		switch ($TANAR[$Time]['diak']) {
			case NULL: $TABLA[$i] .= "  <td class=foglalt>\n"; break;
			case  0:   $TABLA[$i] .= "  <td class=szabad>&nbsp;\n"; break;
			case -1:   $TABLA[$i] .= "  <td class=szabad>&nbsp;\n"; break;
			case -2:   $TABLA[$i] .= "  <td class=szuloi>&nbsp;\n"; break;
			default:   $TABLA[$i] .= "  <td class=szabad><a class=diak href=fogado.php?o=" . $TANAR[$Time]['o']
				. "&id=" . $TANAR[$Time]['diak'] . ">" . $TANAR[$Time]['nev'] . $TANAR[$Time]['onev'] . "</a>\n"; break;
		}
	}
}

print "<table border=1>\n";
print implode($TABLA);
print "</table>\n";

if ($ADMIN) {
	foreach ($QUERY_LOG as $log) print "<b>$log</b><br>\n";
	foreach (explode(' ', $VARIABLES) as $v) {
		$V="VAR_$v";
		print $V . " : " . $$V . "<br>\n";
	}
} else {
	foreach ($USER_LOG as $log) print "<b>$log</b><br>\n";
}

pg_close ($db);
Tail();

?>

