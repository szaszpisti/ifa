<?
require('fogado.inc');

$ADMIN = 1;
if (!isset($VAR_id)) { $VAR_id = 275; }


if (isset($VAR_id)) {
	if ( $result = pg_exec("SELECT O.esz AS ofo, O.enev AS ofonev, E.*"
			. " FROM Osztaly_view AS O, Ember AS E"
			. " WHERE O.oszt=E.oszt AND E.tip='d' AND E.esz=" . $VAR_id)) {
		$USER = pg_fetch_array($result);
	}
} else {
	$USER['id'] = 300;
	$USER['enev'] = 'Eleki Gergely';
}

head("Fogadóóra - " . $USER['enev']);

print "\n<h3>" . $USER['enev'] . "<br>\n";
print "<font size=-1>(Osztályfõnök: " . $USER['ofonev'] . ")</h3>\n";

function td_ki($i, $VAL, $rows, $class) {
	global $TANAR;
	$j=$i+1;
	while ($TANAR[$j]['diak']==$VAL && $j<=$rows) $j++;
	$td = "  <td class=" . $class;
	if ( $j>$i+1 ) $td .= " colspan=" . ($j-$i);
	$td .= ">";
	return array('j' => $j, 'td' => $td);
}

function uj($d) {
	global $K;
	array_push ( $K, array($d) );
}

function add() {
	global $K;
	array_push ( $K[count($K)-1], 'x' );
}

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $TANAR, $K, $ADMIN;
	print "\n<tr><th>&nbsp;" . $tanar['enev'] . " \n";
	if( $result = pg_exec("SELECT diak FROM Fogado WHERE tanar=" . $tanar['tanar']
			. "AND ido BETWEEN '$IDO_min' AND '$IDO_max' ORDER BY ido")) {
		$TANAR = pg_fetch_all($result);
		// TANAR: [0]['diak']=25, [1]['diak']=-1, ...
		$rows = pg_numrows($result);

		$State = -3; // ilyen nincs
		$K = array(array());
		for ($i=0; $i<$rows; $i++) {
			$d=$TANAR[$i]['diak'];
			switch ($d) {
				case -2:
					if ( ($USER['ofo'] == $tanar['tanar']) || $ADMIN ) {
						if ($State != szuloi) { uj($d); $State = szuloi; }
						else add();
						break;
					}                       // különben továbbcsúszunk:
				case NULL:
					if ($State != foglalt) { uj($d); $State = foglalt; }
					else add();
					break;
				case -1:                   // helyben marad
					add();
					break;
				case 0:
					uj($d); $State = szabad;
					break;
				default:
					if ($d == $USER['esz']) {
						if ($State != sajat) { uj($d); $State = sajat; }
						else add();
					}
					else {
						if ($State != foglalt) { uj(NULL); $State = foglalt; }
						else add();
					}
					break;
			}
		}

		$t = 0; // a helye a tablazatban
		for ($i=1; $i<count($K); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
			$span = (count($K[$i])>1)?" colspan=".count($K[$i]):"";
			switch ($K[$i][0]) {
				case NULL: print "  <td class=foglalt$span>&nbsp;\n"; break;
				case -2: print "  <td class=szuloi$span>&nbsp;\n"; break;
				case 0: print "  <td class=szabad$span><input type=radio name=t" . $tanar['tanar'] . "r value=$t>\n"; break;
				default: print "  <td class=sajat$span><input type=checkbox name=t" . $tanar['tanar'] . "c checked>\n"; break;
			}
			$t += count($K[$i]);
		}
		print "  <td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";

	}
}

// A minimális és maximális kiírandó idõ kiválasztása:
$sor = pg_fetch_array(pg_exec("SELECT min(ido) FROM Fogado WHERE diak IS NOT NULL"));
$IDO_min = $sor[0];
$IDO_min_array = explode (':', $IDO_min);
$sor = pg_fetch_array(pg_exec("SELECT max(ido) FROM Fogado WHERE diak IS NOT NULL"));
$IDO_max = $sor[0];
$IDO_max_array = explode (':', $IDO_max);

$IDO_min_d = 6*$IDO_min_array[0]+floor($IDO_min_array[1]/10);
$IDO_max_d = 6*$IDO_max_array[0]+floor($IDO_max_array[1]/10);

for ($ido=$IDO_min_d; $ido<=$IDO_max_d; $ido++) {
	$ora = floor($ido/6);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], $ido % 6);
}

$A = "\n<tr><td rowspan=2>";
$B = "\n<tr>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>" . $perc . "0";
}

print "\nFogadóóra: " . $IDO_min . " -- " . $IDO_max . "<br>\n";

// Vesszük a tanarakat sorban:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$FOGADO_orig = pg_fetch_all($result);
}

// Külön kéne nézni az egész és az öt perceseket
// SELECT count(*) FROM Fogado WHERE ido LIKE '__:_5:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;
// ha nem 0 -- azaz van 5 perces, akkor külön kell kezelni.
// SELECT to_char(ido, 'HH24:MI') FROM Fogado WHERE ido LIKE '__:_0:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;

print "\n<form name=tabla><table border=1>";
print $A . $B;
reset ( $FOGADO_orig );
array_walk ( $FOGADO_orig, 'tanar_ki' );
print "<tr><td colspan=" . ($IDO_max_d-$IDO_min_d+3) . " align=right class=right>\n";
print "  <input type=hidden name=id value=" . $USER['esz'] . ">\n";
print "  <input type=submit name=submit value=' Mehet '>\n";
print "</table>\n\n";
print "</form>\n";


pg_close ($db);
print "\n</body>\n";
print "</html>\n";
return(0);

?>

