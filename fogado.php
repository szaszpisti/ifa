<?
require('fogado.inc');

include("user.class");
$USER = new User();

Head("Fogadóóra - " . $USER->dnev);

$QUERY_LOG = array();
$USER_LOG = array();

print "\n<table width=100%><tr><td>\n";
print "<h3>" . $USER->dnev . " " . $USER->onev .  " (" . $FA->datum . ")<br>\n";
print "<font size=-1>(Osztályfõnök: " . $USER->ofonev . ")</h3>\n";
print "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kilépés</a>\n</table>\n";

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
	global $IDO_min, $IDO_max, $USER, $K;
	// TANAR: [0]['diak']=25, [1]['diak']=-1, ...

	$State = -3; // nem érvényes kezdeti értéket adunk neki
	$K[0] = array(array()); // páros idõket tesszük ebbe
	$K[1] = array(array()); // páratlanokat
	for ($i=$IDO_min; $i<$IDO_max; $i++) {
		if (!isset($tanar['paratlan']) && $i%2) { continue; }
		switch ($tanar[$i]) {
			case -2:
				if ( ($USER->ofo == $tanar['id']) || $USER->admin ) { $d = szuloi; }
				else { $d = foglalt; }
				break;
			case NULL:
				$d = foglalt; break;
			case -1:  // az elõzõ folytatása
				if ( $pred == szabad ) { $d = szabad2; }
				if ( $pred == sajat ) { $d = sajat2; }
				break;
			case 0:
				$d = szabad; break;
			case $USER->id:
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

// párosak:
	$tmp .= tr_string($K[0], $tanar['id'], $IDO_min);
	$tmp .= "  <td><input type=button value=x onClick='torol(\"r" . $tanar['id'] . "\")'>\n";

// páratlanok:
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>" . tr_string($K[1], $tanar['id'], $IDO_min+1);
	}

	return $tmp;

}

$Idoszak = pg_fetch_array(pg_query("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE fid=" . $FA->id . " AND diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['max']%2)+2;

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

// Az összes fogadó tanár nevét kigyûjtjük // FOGADO[id]=('id', 'nev')
if( $result = pg_query("SELECT tanar,tnev FROM Fogado,Tanar WHERE fid=" . $FA->id . " AND tanar=id GROUP BY tanar,tnev ORDER BY tnev")) {
	foreach ( pg_fetch_all($result) as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['tnev']);
	}
}

// mindegyikhez az összes idõ => elfoglaltságot (A FOGADO-hoz rakunk még mezõket)
// FOGADO[id]=('id', 'nev', 'paratlan', 'ido1', 'ido2', ... )
if( $result = pg_query("SELECT tanar, ido, diak FROM Fogado"
			. " WHERE fid=" . $FA->id . " AND ido BETWEEN '" . $IDO_min . "' AND '" . $IDO_max . "' ORDER BY ido")) {
	foreach ( pg_fetch_all($result) as $entry ) {
		// Ha egy páratlan sorszámú idõpontban lehet érték..., azt jelezzük
		if ( $entry['ido']%2 && $entry['diak']>=0 ) { $FOGADO[$entry['tanar']]['paratlan'] = 1; }
		$FOGADO[$entry['tanar']][$entry['ido']] = $entry['diak'];
	}
}

function ValidateRadio ( $Teacher, $Time ) {
// (ezeket jó lenne triggerként berakni a tábla-definícióba...)
	global $FOGADO, $USER;
	if ( $FOGADO[$Teacher][$Time] != 0 ) { return $FOGADO[$Teacher]['nev'] . " " . tim($Time) . " idõpontja már foglalt, ide nem iratkozhat fel!"; }
	foreach ( $FOGADO as $tan ) {
		if ( $tan[$Time] == $USER->id ) return "Önnek már foglalt a " . tim($Time) . " idõpontja (" . $tan['nev'] . ") - elõbb arról iratkozzon le!";
	}
	if ( $FOGADO[$USER->ofo][$Time] == -2 ) return "Önnek szülõi értekezlete van ebben az idõpontban (" . tim($Time) . ")!";
	foreach ( array_keys($FOGADO[$Teacher]) as $k ) {
		if ( $FOGADO[$Teacher][$k] == $USER->id ) { return $FOGADO[$Teacher]['nev'] . " " . tim($k) . " idõpontjára már feliratkozott - ha változtatni akar, elõbb azt törölje!"; }
	}
	return NULL;
}

//
// checkboxok ellenõrzése (leiratkozás)
//
if ( $_POST['tip'] == 'mod' ) {
	foreach ( $FOGADO as $tanar ) {
		$v = "c".$tanar['id'];
		foreach ( array_keys($tanar) as $Time ) {
			if ( ( $tanar[$Time] == $USER->id ) && !isset($_POST[$v]) ) {
				$q = "UPDATE Fogado SET diak=0 WHERE tanar=".$tanar['id']." AND ido=$Time";
				if ( pg_query($q) ) {
					$FOGADO[$tanar['id']][$Time] = "0";
					$USER_LOG[] .= "RENDBEN: " . $FOGADO[$tanar['id']]['nev'] . ", " . tim($Time) . " - törölve.";
					Ulog($USER->id, $q);
				}
				else { Ulog($USER->id, "Légy került a levesbe: $q!"); }
			}
		}
	}
}

//
// rádiógombok ellenõrzése (feliratkozás)
//
reset($_POST);
while (list($k, $v) = each($_POST)) {
	if ( ereg ("^r([0-9]+)$", $k, $match) ) {
		$Teacher = $match[1];
		$Time = $v;
		if ( $validate = ValidateRadio ($Teacher, $Time) ) {
			Ulog($USER->id, $validate);
			$USER_LOG[] .= "$validate";
		}
		else { // rendben, lehet adatbázisba rakni
			$q = "UPDATE Fogado SET diak=" . $USER->id . " WHERE tanar=$Teacher AND ido=$Time";
			if ( pg_query($q) ) {
				$FOGADO[$Teacher][$Time] = $USER->id;
				$USER_LOG[] .= "RENDBEN: " . $FOGADO[$Teacher]['nev'] . ", " . tim($Time) . " - bejegyezve.";
				Ulog($USER->id, $q);
			}
			else { Ulog($USER->id, "Légy került a levesbe: $q!"); }
		}
	}
}

print "\n<form name=tabla method=post><table border=1>";
print $A . $B;
foreach ( $FOGADO as $tanar ) {
	$ttabla .= tanar_ki($tanar);
}

print $ttabla;
print "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n";
print "  <input type=hidden name=tip value=mod>\n";
print "  <input type=submit value=' Mehet '>\n";
print "</table>\n\n";
print "</form>\n";

foreach ($USER_LOG as $log) print "<font size=-1><b>$log</b></font><br>\n";

pg_close ($db);
Tail();

?>

