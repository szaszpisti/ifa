<?
require('fogado.inc');

$ADMIN = 1;

if (!isset($VAR_id)) {
	if ( isset($VAR_o) ) {
		Head("Fogadóóra - $VAR_o");
		print Osztaly_select($VAR_o);
		if ( $result = pg_exec("SELECT esz AS esz,enev FROM Ember WHERE oszt='" . $VAR_o . "' ORDER BY enev")) {
			foreach (pg_fetch_all($result) as $d) {
				print "<li><a href=$Szulo?id=" . $d['esz'] . ">" . $d['enev'] . "</a>\n";
			}
		}
		Tail();
		return 0;
	}
	Head("Fogadóóra - iskola");
	print "<form>\n" . Osztaly_select(0) . "<input type=submit value=' OK '>\n</form>\n";
	Tail();
	return 0;
}
$id = $VAR_id;

if ( $result = pg_exec("SELECT O.esz AS ofo, O.enev AS ofonev, O.onev, E.*"
		. " FROM Osztaly_view AS O, Ember AS E"
		. " WHERE O.oszt=E.oszt AND E.tip='d' AND E.esz=" . $id)) {
	$USER = pg_fetch_array($result);
}
// phpinfo();
// VARIABLES tömb-ben benn vannak a létezõ változók.

Head("Fogadóóra - " . $USER['enev']);

print "<font size=+1><b>\n";
print Osztaly_select($USER['oszt']);
print "</b></font>\n";
print Diak_select($USER['oszt'], $id);

//var_dump($USER);

print "\n<h3>" . $USER['enev'] . " (" . $USER['onev'] . ")<br>\n";
print "<font size=-1>(Osztályfõnök: " . $USER['ofonev'] . ")</h3>\n";

// foreach ($VARIABLES as $v) {
	

// Egy cella kiírása
function td_ki($i, $VAL, $rows, $class) {
	global $TANAR;
	$j=$i+1;
	while ($TANAR[$j]['diak']==$VAL && $j<=$rows) $j++;
	$td = "  <td class=" . $class;
	if ( $j>$i+1 ) $td .= " colspan=" . ($j-$i);
	$td .= ">";
	return array('j' => $j, 'td' => $td);
}

// Idõ átszámítása 5 perces sorszámúról HH:MM formátumra
function tim($time) { return gmdate('H:i', $time*300); }

// A K tömbbe új elemet veszünk fel ha a sorban következõ idõpont típusa változott
function uj($d) {
	global $K;
	array_push ( $K, array($d) );
}

// A K tömbbe aktuális tömbjéhez adunk új bejegyzést, ha az idõpont típusa megegyezik az elõzõvel
function add() {
	global $K;
	array_push ( $K[count($K)-1], 'x' );
}

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $TANAR, $K, $ADMIN;
	print "\n<tr><th>&nbsp;" . $tanar['enev'] . " \n";
	print "SELECT diak FROM Fogado WHERE tanar=" . $tanar['tanar']
			. " AND ido BETWEEN '" . tim($IDO_min) . "' AND '" . tim($IDO_max) . "' ORDER BY ido";
	if( $result = pg_exec("SELECT diak FROM Fogado WHERE tanar=" . $tanar['tanar']
			. " AND ido BETWEEN '" . tim($IDO_min) . "' AND '" . tim($IDO_max) . "' ORDER BY ido")) {
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
					}                       // itt továbbcsúszunk:
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

		$t = $IDO_min; // a helye a tablazatban
		for ($i=1; $i<count($K); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
			$span = (count($K[$i])>1)?" colspan=".count($K[$i]):"";
			switch ($K[$i][0]) {
				case NULL: print "  <td class=foglalt$span>&nbsp;\n"; break;
				case -2: print "  <td class=szuloi$span>&nbsp;\n"; break;
				case 0: print "  <td class=szabad$span><input type=radio name=t" . $tanar['tanar'] . "r value=$t>\n"; break;
				default: print "  <td class=sajat$span><input type=checkbox name=t" . $tanar['tanar'] . "c checked>\n"; break;
			}
			$t += count($K[$i]) * 2;
		}
		print "  <td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";

	}
}

$sor = pg_fetch_array(pg_exec("SELECT extract(HOUR FROM min(ido))*12 + extract(MINUTE FROM min(ido))/5 AS ido"
		. " FROM Fogado WHERE diak IS NOT NULL AND ido LIKE '__:_0:__'"));
$IDO_min = $sor[0];
$sor = pg_fetch_array(pg_exec("SELECT extract(HOUR FROM max(ido))*12 + extract(MINUTE FROM max(ido))/5 + 2 AS ido"
		. " FROM Fogado WHERE diak IS NOT NULL AND ido LIKE '__:_0:__'"));
$IDO_max = $sor[0];

// print "\nFogadóóra: " . $IDO_min . " -- " . $IDO_max . "<br>\n";

// A fejléc sorok kiíratásához
for ($ido=$IDO_min; $ido<$IDO_max; $ido+=2) {
	$ora = floor($ido/12);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], ($ido % 12)/2);
}

var_dump($IDO);
$A = "\n<tr><td rowspan=2>";
$B = "\n<tr>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>" . $perc . "0";
}

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
print "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n";
print "  <input type=hidden name=id value=" . $USER['esz'] . ">\n";
print "  <input type=submit name=submit value=' Mehet '>\n";
print "</table>\n\n";
print "</form>\n";


pg_close ($db);
Tail();

// SELECT max(ido) + INTERVAL '10 min' AS ido FROM Fogado;
// SELECT extract(HOUR FROM max(ido))*12 + extract(MINUTE FROM max(ido))/5 AS ido FROM Fogado;
// SELECT extract(HOUR FROM ido)*12 + extract(MINUTE FROM ido)/5 AS ido FROM Fogado WHERE ido LIKE '__:_0:__';

?>

