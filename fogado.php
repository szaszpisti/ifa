<?
require('fogado.inc');

$ADMIN = 0;

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
// VARIABLES tömb-ben benn vannak a létezõ változók.

Head("Fogadóóra - " . $USER['enev']);

print "<font size=+1><b>\n";
print Osztaly_select($USER['oszt']);
print "</b></font>\n";
print Diak_select($USER['oszt'], $id);

//var_dump($USER);

print "\n<h3>" . $USER['enev'] . " (" . $USER['onev'] . ")<br>\n";
print "<font size=-1>(Osztályfõnök: " . $USER['ofonev'] . ")</h3>\n";

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

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $K, $ADMIN;
	// TANAR: [0]['diak']=25, [1]['diak']=-1, ...

	$State = -3; // nem érvényes kezdeti értéket adunk neki
	$K[0] = array(array()); // páros idõket tesszük ebbe
	$K[1] = array(array()); // páratlanokat
	for ($i=$IDO_min; $i<$IDO_max; $i++) {
//		$d=$tanar[$i];
		switch ($tanar[$i]) {
			case -2:
				if ( ($USER['ofo'] == $tanar['tanar']) || $ADMIN ) { $d = szuloi; }
			case NULL:
				$d = foglalt; break;
			case -1:                   // helyben marad
				break;
			case 0:
				$d = szabad; break;
			case $USER['esz']:
				$d = sajat; break;
			default:
				$d = foglalt; break;
		}
		if ( $d != $pred || $d == szabad ) {
			array_push ( $K[0], array($d) );
			array_push ( $K[1], array($d) );
		}
		else {
			array_push ( $K[$i%2][count($K[$i%2])-1], 'x' );
		}
		$pred = $d;
	}
//	print "\n===" . sizeof($K[0]) . "===\n" ;

	$tmp = "\n<tr><th" . (isset($tanar['paratlan'])?" rowspan=2 valign=top":"") . ">&nbsp;" . $tanar['nev'] . " \n";
// var_dump($tmp);

	$t = $IDO_min; // a helye a tablazatban

// párosak:
	for ($i=1; $i<count($K[0]); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
		$span = (count($K[0][$i])>1)?" colspan=".count($K[0][$i]):"";
		switch ($K[0][$i][0]) {
			case foglalt: $tmp .= "  <td class=foglalt$span>&nbsp;\n"; break;
			case szuloi:  $tmp .= "  <td class=szuloi$span>&nbsp;\n"; break;
			case szabad:  $tmp .= "  <td class=szabad$span><input type=radio name=t" . $tanar['id'] . "r value=$t>\n"; break;
			case sajat:   $tmp .= "  <td class=sajat$span><input type=checkbox name=t" . $tanar['id'] . "c checked>\n"; break;
		}
		$t += count($K[0][$i]) * 2;
	}
	$tmp .= "  <td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";

// páratlanok:
	$t = $IDO_min+1; // a helye a tablazatban
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>";
		for ($i=1; $i<count($K[1]); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
			$span = (count($K[1][$i])>1)?" colspan=".count($K[1][$i]):"";
			switch ($K[1][$i][0]) {
				case foglalt: $tmp .= "  <td class=foglalt$span>&nbsp;\n"; break;
				case szuloi:  $tmp .= "  <td class=szuloi$span>&nbsp;\n"; break;
				case szabad:  $tmp .= "  <td class=szabad$span><input type=radio name=t" . $tanar['id'] . "r value=$t>\n"; break;
				case sajat:   $tmp .= "  <td class=sajat$span><input type=checkbox name=t" . $tanar['id'] . "c checked>\n"; break;
			}
			$t += count($K[1][$i]) * 2;
		}
	}

	return $tmp;

}

foreach (explode(' ', $VARIABLES) as $v) {
	$V="VAR_$v";
	print $V . " : " . $$V . "<br>\n";
}

$Idoszak = pg_fetch_array(pg_exec("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['min']%2);

// A fejléc sorok kiíratásához
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

// Vesszük az összes tanarakat:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$TANAR = pg_fetch_all($result);
	foreach ( $TANAR as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['enev']);
	}
}

// mindegyikhez az összes idõ => elfoglaltságot:

if( $result = pg_exec("SELECT tanar, ido, diak FROM Fogado"
			. " WHERE ido BETWEEN '" . $IDO_min . "' AND '" . $IDO_max . "' ORDER BY ido")) {
	$KUPAC = pg_fetch_all($result);
	foreach ( $KUPAC as $sor ) {
		if ( $sor['ido']%2 ) { $FOGADO[$sor['tanar']]['paratlan'] = 1; } // jelzõ, hogy itt az 5 perceket is írni kell
		$FOGADO[$sor['tanar']][$sor['ido']] = $sor['diak'];
	}
}

/*
-> ehelyett inkább egy kupacban kellene lekérdezni...

foreach sor
TANAR[$tanar
*/
print "\n<form name=tabla><table border=1>";
print $A . $B;
foreach ( $FOGADO as $tanar ) {
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

