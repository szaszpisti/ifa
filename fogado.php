<?
require('fogado.inc');

$ADMIN = 0;

if (!isset($VAR_id)) {
	if ( isset($VAR_o) ) {
		Head("Fogad��ra - $VAR_o");
		print Osztaly_select($VAR_o);
		if ( $result = pg_exec("SELECT esz AS esz,enev FROM Ember WHERE oszt='" . $VAR_o . "' ORDER BY enev")) {
			foreach (pg_fetch_all($result) as $d) {
				print "<li><a href=$Szulo?id=" . $d['esz'] . ">" . $d['enev'] . "</a>\n";
			}
		}
		Tail();
		return 0;
	}
	Head("Fogad��ra - iskola");
	print Osztaly_select(0);
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
// VARIABLES t�mb-ben benn vannak a l�tez� v�ltoz�k.

Head("Fogad��ra - " . $USER['enev']);

print "<font size=+1><b>\n";
print Osztaly_select($USER['oszt']);
print "</b></font>\n";
print Diak_select($USER['oszt'], $id);

//var_dump($USER);

print "\n<h3>" . $USER['enev'] . " (" . $USER['onev'] . ")<br>\n";
print "<font size=-1>(Oszt�lyf�n�k: " . $USER['ofonev'] . ")</h3>\n";

// Egy cella ki�r�sa
function td_ki($i, $VAL, $rows, $class) {
	global $TANAR;
	$j=$i+1;
	while ($TANAR[$j]['diak']==$VAL && $j<=$rows) $j++;
	$td = "  <td class=" . $class;
	if ( $j>$i+1 ) $td .= " colspan=" . ($j-$i);
	$td .= ">";
	return array('j' => $j, 'td' => $td);
}

// Id� �tsz�m�t�sa 5 perces sorsz�m�r�l HH:MM form�tumra
function tim($time) { return gmdate('H:i', $time*300); }

// A K t�mbbe �j elemet vesz�nk fel ha a sorban k�vetkez� id�pont t�pusa v�ltozott
function uj($d) {
	global $K;
	array_push ( $K, array($d) );
}

// A K t�mbbe aktu�lis t�mbj�hez adunk �j bejegyz�st, ha az id�pont t�pusa megegyezik az el�z�vel
function add() {
	global $K;
	array_push ( $K[count($K)-1], 'x' );
}

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $TANAR, $K, $ADMIN;
	$tmp = "\n<tr><th>&nbsp;" . $tanar['enev'] . " \n";
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
					}                       // itt tov�bbcs�szunk:
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
		for ($i=1; $i<count($K); $i++) { // 1-t�l kell kezdeni, mert a K inicializ�l�sakor ker�lt bele egy f�l�s elem
			$span = (count($K[$i])>1)?" colspan=".count($K[$i]):"";
			switch ($K[$i][0]) {
				case NULL: $tmp .= "  <td class=foglalt$span>&nbsp;\n"; break;
				case -2: $tmp .= "  <td class=szuloi$span>&nbsp;\n"; break;
				case 0: $tmp .= "  <td class=szabad$span><input type=radio name=t" . $tanar['tanar'] . "r value=$t>\n"; break;
				default: $tmp .= "  <td class=sajat$span><input type=checkbox name=t" . $tanar['tanar'] . "c checked>\n"; break;
			}
			$t += count($K[$i]) * 2;
		}
		$tmp .= "  <td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";
		return $tmp;

	}
}

$sor = pg_fetch_array(pg_exec("SELECT extract(HOUR FROM min(ido))*12 + extract(MINUTE FROM min(ido))/5 AS ido"
		. " FROM Fogado WHERE diak IS NOT NULL AND ido LIKE '__:_0:__'"));
$IDO_min = $sor[0];
$sor = pg_fetch_array(pg_exec("SELECT extract(HOUR FROM max(ido))*12 + extract(MINUTE FROM max(ido))/5 + 2 AS ido"
		. " FROM Fogado WHERE diak IS NOT NULL AND ido LIKE '__:_0:__'"));
$IDO_max = $sor[0];

// print "\nFogad��ra: " . $IDO_min . " -- " . $IDO_max . "<br>\n";

// A fejl�c sorok ki�rat�s�hoz
for ($ido=$IDO_min; $ido<$IDO_max; $ido+=2) {
	$ora = floor($ido/12);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], ($ido % 12)/2);
}

$A = "\n<tr><td rowspan=2>";
$B = "\n<tr>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>" . $perc . "0";
}

// Vessz�k a tanarakat sorban:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$FOGADO_orig = pg_fetch_all($result);
}

// K�l�n k�ne n�zni az eg�sz �s az �t perceseket
// SELECT count(*) FROM Fogado WHERE ido LIKE '__:_5:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;
// ha nem 0 -- azaz van 5 perces, akkor k�l�n kell kezelni.
// SELECT to_char(ido, 'HH24:MI') FROM Fogado WHERE ido LIKE '__:_0:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;

foreach (explode(' ', $VARIABLES) as $v) {
	$V="VAR_$v";
	print $V . " : " . $$V . "<br>\n";
}

print "\n<form name=tabla><table border=1>";
print $A . $B;
foreach ( $FOGADO_orig as $tanar ) {
	$ttabla .= tanar_ki($tanar);
}

print $ttabla;
print "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n";
print "  <input type=hidden name=id value=" . $USER['esz'] . ">\n";
print "  <input type=submit value=' Mehet '>\n";
print "</table>\n\n";
print "</form>\n";


pg_close ($db);
Tail();

// SELECT max(ido) + INTERVAL '10 min' AS ido FROM Fogado;
// SELECT extract(HOUR FROM max(ido))*12 + extract(MINUTE FROM max(ido))/5 AS ido FROM Fogado;
// SELECT extract(HOUR FROM ido)*12 + extract(MINUTE FROM ido)/5 AS ido FROM Fogado WHERE ido LIKE '__:_0:__';

?>

