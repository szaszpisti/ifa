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

// Id� �tsz�m�t�sa 5 perces sorsz�m�r�l HH:MM form�tumra
// function tim($time) { return gmdate('H:i', $time*300); }

function tr_string($K, $tid, $t) {
	for ($i=1; $i<count($K); $i++) { // 1-t�l kell kezdeni, mert a K inicializ�l�sakor ker�lt bele egy f�l�s elem
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

	$State = -3; // nem �rv�nyes kezdeti �rt�ket adunk neki
	$K[0] = array(array()); // p�ros id�ket tessz�k ebbe
	$K[1] = array(array()); // p�ratlanokat
	for ($i=$IDO_min; $i<$IDO_max; $i++) {
		if (!isset($tanar['paratlan']) && $i%2) { continue; }
		switch ($tanar[$i]) {
			case -2:
				if ( ($USER['ofo'] == $tanar['id']) || $ADMIN ) { $d = szuloi; }
				else { $d = foglalt; }
				break;
			case NULL:
				$d = foglalt; break;
			case -1:                   // az el�z� folytat�sa
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

// p�rosak:
	$tmp .= tr_string($K[0], $tanar['id'], $IDO_min);
	$tmp .= "  <td><input type=button value=x onClick='torol(\"r" . $tanar['id'] . "\")'>\n";

// p�ratlanok:
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>" . tr_string($K[1], $tanar['id'], $IDO_min+1);
	}

	return $tmp;

}

$Idoszak = pg_fetch_array(pg_exec("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['max']%2);

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

// Vessz�k az �sszes tanarakat:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$TANAR = pg_fetch_all($result);
	foreach ( $TANAR as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['enev']);
	}
}

// mindegyikhez az �sszes id� => elfoglalts�got:

if( $result = pg_exec("SELECT tanar, ido, diak FROM Fogado"
			. " WHERE ido BETWEEN '" . $IDO_min . "' AND '" . $IDO_max . "' ORDER BY ido")) {
	$KUPAC = pg_fetch_all($result);
	foreach ( $KUPAC as $sor ) {
		if ( $sor['ido']%2 ) { $FOGADO[$sor['tanar']]['paratlan'] = 1; } // jelz�, hogy itt az 5 perceket is �rni kell
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
	if ( $FOGADO[$Teacher][$Time] != 0 ) { return $FOGADO[$Teacher]['nev'] . " ezen id�pontja m�r foglalt, nem v�laszthatja!"; }
	foreach ( $FOGADO as $t ) {
		if ( $t[$Time] == $USER['esz'] ) return "�nnek m�r foglalt ez az id�pontja (" . $t['nev'] . ") - el�bb onnan iratkozzon le!";
	}
	if ( $FOGADO[$USER['ofo']][$Time] == -2 ) return "�nnek sz�l�i �rtekezlete van ebben az id�pontban!";
	foreach ( $FOGADO[$Teacher] as $i ) {
		if ( $i == $USER['esz'] ) { return "Egy tan�rn�l csak egy id�pontra iratkozhat fel - el�bb jelentkezzen le a m�sikr�l!"; }
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
		else { // rendben, lehet adatb�zisba rakni
			print "<h3>UPDATE Fogado SET diak=$id WHERE tanar=$Teacher AND ido=$Time</h3>";
		}
	}
}

/*

Esetleg: SELECT * FROM Fogado WHERE diak=$DIAK[id]
trigger?
for i in valtozo {
	if valtozonev =~ /^r([0-9]+)$/
		�s t�nyleg �res a hely (k�l�nben print ez a hely m�r foglalt)
		�s user m�g nincs m�sn�l bejegyezve erre az id�re (k�l�nben print �nnek m�r van erre az id�pontra bejegyz�se!)
		�s enn�l a tan�rn�l m�g nincs fel�rva (k�l�nben print el�sz�r iratkozzon le (tanar.enev-t�l))
		(persze ezeket j� lenne valami triggerk�nt berakni a t�bla-defin�ci�ba...)

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

