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

// Idõ átszámítása 5 perces sorszámúról HH:MM formátumra
// function tim($time) { return gmdate('H:i', $time*300); }

function tr_string($K, $tid, $t) {
	for ($i=1; $i<count($K); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
		$span = (count($K[$i])>1)?" colspan=".count($K[$i]):"";
		switch ($K[$i][0]) {
			case foglalt: $tmp .= "  <td class=foglalt$span>&nbsp;\n"; break;
			case szuloi:  $tmp .= "  <td class=szuloi$span>&nbsp;\n"; break;
			case szabad:  $tmp .= "  <td class=szabad$span><input type=radio name=r$tid value=$t>\n"; break;
			case szabad2: $tmp .= "  <td class=szabad$span>&nbsp;\n"; break;
			case sajat:   $tmp .= "  <td class=sajat$span><input type=checkbox name=c$tid checked>\n"; break;
			case sajat2:  $tmp .= "  <td class=sajat$span>&nbsp;\n"; break;
		}
		$t += count($K[$i]) * 2;
	}
	return $tmp;
}

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $K, $ADMIN;
	// TANAR: [0]['diak']=25, [1]['diak']=-1, ...

	$State = -3; // nem érvényes kezdeti értéket adunk neki
	$K[0] = array(array()); // páros idõket tesszük ebbe
	$K[1] = array(array()); // páratlanokat
	for ($i=$IDO_min; $i<$IDO_max; $i++) {
		if (!isset($tanar['paratlan']) && $i%2) { continue; }
		switch ($tanar[$i]) {
			case -2:
				if ( ($USER['ofo'] == $tanar['id']) || $ADMIN ) { $d = szuloi; }
				else { $d = foglalt; }
				break;
			case NULL:
				$d = foglalt; break;
			case -1:                   // az elõzõ folytatása
				if ( $pred == szabad ) { $d = szabad2; }
				if ( $pred == sajat ) { $d = sajat2; }
				break;
			case 0:
				$d = szabad; break;
			case $USER['esz']:
				$d = sajat;
				break;
			default:
				$d = foglalt; break;
		}
		if ( ( $d != $pred && $d != szabad2 && $d != sajat2 ) || $d == szabad ) {
			array_push ( $K[$i%2], array($d) );
			array_push ( $K[1-$i%2], array() );
//			print "\nNEW: $i (".($i%2).") -> $d";
		}
		else {
			array_push ( $K[$i%2][count($K[$i%2])-1], $d );
//			print "\n     $i (".($i%2).") -> $d";
		}
		$pred = $d;
	}
//	print "\n===" . sizeof($K[0]) . "===\n" ;
// var_dump($K);

	$tmp = "\n<tr><th align=left" . (isset($tanar['paratlan'])?" rowspan=2 valign=top":"") . ">&nbsp;" . $tanar['nev'] . " \n";

// párosak:
	$tmp .= tr_string($K[0], $tanar['id'], $IDO_min);
	$tmp .= "  <td><input type=button value=x onClick='torol(\"r" . $tanar['id'] . "\")'>\n";

// páratlanok:
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>" . tr_string($K[1], $tanar['id'], $IDO_min+1);
	}

	return $tmp;

}

$Idoszak = pg_fetch_array(pg_exec("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['max']%2);

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

if ( $VAR_tip == 'mod' ) {
	foreach ( $FOGADO as $tanar ) {
		foreach ( array_keys($tanar) as $ido ) {
			if ( $tanar[$ido] == $id ) {
				$v = "VAR_c".$tanar['id'];
				if ( !isset($$v) ) {
					print "<h3>UPDATE Fogado SET diak=0 WHERE tanar=".$tanar['id']." AND ido=$ido</h3>";
				}
			}
	// $CHECKBOX["c".$tanar['id']] = $id; }
		}
	}
}

function ValidateRadio ( $Teacher, $Time ) {
	global $FOGADO, $USER;
	if ( $FOGADO[$Teacher][$Time] != 0 ) { return $FOGADO[$Teacher]['nev'] . " ezen idõpontja már foglalt, nem választhatja!"; }
	foreach ( $FOGADO as $t ) {
		if ( $t[$Time] == $USER['esz'] ) return "Önnek már foglalt ez az idõpontja (" . $t['nev'] . ") - elõbb onnan iratkozzon le!";
	}
	if ( $FOGADO[$USER['ofo']][$Time] == -2 ) return "Önnek szülõi értekezlete van ebben az idõpontban!";
	foreach ( $FOGADO[$Teacher] as $i ) {
		if ( $i == $USER['esz'] ) { return "Egy tanárnál csak egy idõpontra iratkozhat fel - elõbb jelentkezzen le a másikról!"; }
	}
	return NULL;
}

foreach (explode(' ', $VARIABLES) as $var) {
	print "$var <br>\n";
	if ( ereg ("^r([0-9]+)$", $var, $match) ) {
		$Teacher = $match[1];
		$VAR = "VAR_$var";
		$Time = $$VAR;
//	print ValidateRadio ($Teacher, $Time) . "\n\n";
		if ( $validate = ValidateRadio ($Teacher, $Time) ) {
			print $validate;
		}
		else { // rendben, lehet adatbázisba rakni
			print "<h3>UPDATE Fogado SET diak=$id WHERE tanar=$Teacher AND ido=$Time</h3>";
		}
	}
}

/*

Esetleg: SELECT * FROM Fogado WHERE diak=$DIAK[id]
trigger?
for i in valtozo {
	if valtozonev =~ /^r([0-9]+)$/
		és tényleg üres a hely (különben print ez a hely már foglalt)
		és user még nincs másnál bejegyezve erre az idõre (különben print önnek már van erre az idõpontra bejegyzése!)
		és ennél a tanárnál még nincs felírva (különben print elõször iratkozzon le (tanar.enev-tõl))
		(persze ezeket jó lenne valami triggerként berakni a tábla-definícióba...)

			UPDATE Fogado set diak=$USER['id'] WHERE tanar=$1 AND ido=$valtozonev
	else { print 
*/



print "\n<form name=tabla><table border=1>";
print $A . $B;
foreach ( $FOGADO as $tanar ) {
	$ttabla .= tanar_ki($tanar);
}

print $ttabla;
print "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n";
print "  <input type=hidden name=tip value=mod>\n";
print "  <input type=hidden name=id value=" . $id . ">\n";
print "  <input type=submit value=' Mehet '>\n";
print "</table>\n\n";
print "</form>\n";


pg_close ($db);
Tail();

?>

