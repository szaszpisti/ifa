<?
require('fogado.inc');

$ADMIN = 0;

if ( !isset($VAR_id) ) { return 0; }

$id = $VAR_id;

if ( $result = pg_exec("SELECT O.esz AS ofo, O.enev AS ofonev, O.onev, E.*"
		. " FROM Osztaly_view AS O, Ember AS E"
		. " WHERE O.oszt=E.oszt AND E.tip='d' AND E.esz=" . $id)) {
	$USER = pg_fetch_array($result);
}

if ( $result = pg_exec("SELECT * FROM Fogado_admin WHERE id=(SELECT MAX(id) FROM Fogado_admin)" )) {
	$FA = pg_fetch_array($result);
}
$fid = $FA['id'];

$QUERY_LOG = array();
$USER_LOG = array();

Head("Fogad��ra - " . $USER['enev'] . " (" . $FA['datum'] . ")");

print "\n<h3>" . $USER['enev'] . " (" . $USER['onev'] . ")<br>\n";
print "<font size=-1>(Oszt�lyf�n�k: " . $USER['ofonev'] . ")</h3>\n";

// Id� �tsz�m�t�sa 5 perces sorsz�m�r�l HH:MM form�tumra
function tim($time) { return gmdate('H:i', $time*300); }

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
			case -1:  // az el�z� folytat�sa
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
		}
		else {
			array_push ( $K[$i%2][count($K[$i%2])-1], $d );
		}
		$pred = $d;
	}

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

$Idoszak = pg_fetch_array(pg_exec("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE fid=$fid AND diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['max']%2)+2;

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

// Az �sszes fogad� tan�r nev�t kigy�jtj�k // FOGADO[id]=('id', 'nev')
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE fid=$fid AND tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	foreach ( pg_fetch_all($result) as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['enev']);
	}
}

// mindegyikhez az �sszes id� => elfoglalts�got (A FOGADO-hoz rakunk m�g mez�ket)
// FOGADO[id]=('id', 'nev', 'paratlan', 'ido1', 'ido2', ... )
if( $result = pg_exec("SELECT tanar, ido, diak FROM Fogado"
			. " WHERE fid=$fid AND ido BETWEEN '" . $IDO_min . "' AND '" . $IDO_max . "' ORDER BY ido")) {
	foreach ( pg_fetch_all($result) as $entry ) {
		// Ha egy p�ratlan sorsz�m� id�pontban lehet �rt�k..., azt jelezz�k
		if ( $entry['ido']%2 && $entry['diak']>=0 ) { $FOGADO[$entry['tanar']]['paratlan'] = 1; }
		$FOGADO[$entry['tanar']][$entry['ido']] = $entry['diak'];
	}
}

function ValidateRadio ( $Teacher, $Time ) {
// (ezeket j� lenne triggerk�nt berakni a t�bla-defin�ci�ba...)
	global $FOGADO, $USER;
	if ( $FOGADO[$Teacher][$Time] != 0 ) { return $FOGADO[$Teacher]['nev'] . " " . tim($Time) . " id�pontja m�r foglalt, ide nem iratkozhat fel!"; }
	foreach ( $FOGADO as $tan ) {
		if ( $tan[$Time] == $USER['esz'] ) return "�nnek m�r foglalt a " . tim($Time) . " id�pontja (" . $tan['nev'] . ") - el�bb arr�l iratkozzon le!";
	}
	if ( $FOGADO[$USER['ofo']][$Time] == -2 ) return "�nnek sz�l�i �rtekezlete van ebben az id�pontban (" . tim($Time) . ")!";
	foreach ( array_keys($FOGADO[$Teacher]) as $k ) {
		if ( $FOGADO[$Teacher][$k] == $USER['esz'] ) { return $FOGADO[$Teacher]['nev'] . " " . tim($k) . " id�pontj�ra m�r feliratkozott - ha v�ltoztatni akar, el�bb azt t�r�lje!"; }
	}
	return NULL;
}

//
// checkboxok ellen�rz�se (leiratkoz�s)
//
if ( $VAR_tip == 'mod' ) {
	foreach ( $FOGADO as $tanar ) {
		$v = "VAR_c".$tanar['id'];
		foreach ( array_keys($tanar) as $Time ) {
			if ( ( $tanar[$Time] == $id ) && !isset($$v) ) {
				$q = "UPDATE Fogado SET diak=0 WHERE tanar=".$tanar['id']." AND ido=$Time";
				if ( pg_exec($q) ) {
					$FOGADO[$tanar['id']][$Time] = "0";
					$USER_LOG[] .= "RENDBEN: " . $FOGADO[$tanar['id']]['nev'] . ", " . tim($Time) . " - t�r�lve.";
					$QUERY_LOG[] .= "$q";
				}
				else { $QUERY_LOG[] .= "L�gy ker�lt a levesbe: $q!"; }
			}
		}
	}
}

//
// r�di�gombok ellen�rz�se (feliratkoz�s)
//
foreach (explode(' ', $VARIABLES) as $var) {
	if ( ereg ("^r([0-9]+)$", $var, $match) ) {
		$Teacher = $match[1];
		$VAR = "VAR_$var";
		$Time = $$VAR;
		if ( $validate = ValidateRadio ($Teacher, $Time) ) {
			$QUERY_LOG[] .= "$validate";
			$USER_LOG[] .= "$validate";
		}
		else { // rendben, lehet adatb�zisba rakni
			$q = "UPDATE Fogado SET diak=$id WHERE tanar=$Teacher AND ido=$Time";
			if ( pg_exec($q) ) {
				$FOGADO[$Teacher][$Time] = $id;
				$USER_LOG[] .= "RENDBEN: " . $FOGADO[$Teacher]['nev'] . ", " . tim($Time) . " - bejegyezve.";
				$QUERY_LOG[] .= "$q";
			}
			else { $QUERY_LOG[] .= "L�gy ker�lt a levesbe: $q!"; }
		}
	}
}

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

