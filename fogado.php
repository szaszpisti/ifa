<?
require('fogado.inc');

include("user.class");
$USER = new User();

Head("Fogad��ra - " . $USER->dnev);

$USER_LOG = array();

$Fejlec = "\n<table width=100%><tr><td>\n"
	. "<h3>" . $USER->dnev . " " . $USER->onev .  " (" . $FA->datum . ")<br>\n"
	. "<font size=-1>(Oszt�lyf�n�k: " . $USER->ofonev . ")</h3>\n"
	. "<td align=right valign=top>\n"
	. "  <a href=osszesit.php?id=".$USER->id."> �sszes�t�s </a> | \n"
	. "  <a href=leiras.html> Le�r�s </a> | \n"
	. "  <a href='" . $_SERVER['PHP_SELF'] . "?kilep='> Kil�p�s </a>\n</table>\n";

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
	global $IDO_min, $IDO_max, $USER, $K;
	// TANAR: [0]['diak']=25, [1]['diak']=-1, ...

	$State = -3; // nem �rv�nyes kezdeti �rt�ket adunk neki
	$K[0] = array(array()); // p�ros id�ket tessz�k ebbe
	$K[1] = array(array()); // p�ratlanokat
	for ($i=$IDO_min; $i<$IDO_max; $i++) {
		if (!isset($tanar['paratlan']) && $i%2) { continue; }
		switch ($tanar[$i]) {
			case -2:
				if ( ($USER->ofo == $tanar['id']) || $USER->admin ) { $d = szuloi; }
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

	$tmp = "\n<tr><th align=left".(isset($tanar['paratlan'])?" rowspan=2 valign=top":"").">&nbsp;"
		. ($USER->admin?"<a href=tanar.php?id=".$tanar['id'].">".$tanar['nev']."</a>":$tanar['nev']) . "\n";

// p�rosak:
	$tmp .= tr_string($K[0], $tanar['id'], $IDO_min);
	$tmp .= "  <td><input type=button value=x onClick='torol(\"r".$tanar['id']."\")'>\n";

// p�ratlanok:
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>".tr_string($K[1], $tanar['id'], $IDO_min+1);
	}

	return $tmp;

}

$Idoszak = pg_fetch_array(pg_query("SELECT min(ido) AS min, max(ido) AS max FROM Fogado WHERE fid=".$FA->id." AND diak IS NOT NULL"));
$IDO_min = $Idoszak['min']-($Idoszak['min']%2);
$IDO_max = $Idoszak['max']-($Idoszak['max']%2)+2;

// A fejl�c sorok ki�rat�s�hoz
for ($ido=$IDO_min; $ido<$IDO_max; $ido+=2) {
	$ora = floor($ido/12);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], ($ido % 12)/2);
}

$A = "\n<tr bgcolor=lightblue><td rowspan=2>";
$B = "\n<tr bgcolor=lightblue>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=".count ($IDO[$ora]).">".$ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>".$perc."0";
}
$TablazatIdosor = $A.$B;

// Az �sszes fogad� tan�r nev�t kigy�jtj�k // FOGADO[id]=('id', 'nev')
if( $result = pg_query("SELECT tanar,tnev FROM Fogado,Tanar WHERE fid=" . $FA->id . " AND tanar=id GROUP BY tanar,tnev ORDER BY tnev")) {
	foreach ( pg_fetch_all($result) as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['tnev']);
	}
}

// mindegyikhez az �sszes id� => elfoglalts�got (A FOGADO-hoz rakunk m�g mez�ket)
// FOGADO[id]=('id', 'nev', 'paratlan', 'ido1', 'ido2', ... )
if( $result = pg_query("SELECT tanar, ido, diak FROM Fogado"
			. " WHERE fid=" . $FA->id . " AND ido BETWEEN '" . $IDO_min . "' AND '" . $IDO_max . "' ORDER BY ido")) {
	foreach ( pg_fetch_all($result) as $entry ) {
		// Ha egy p�ratlan sorsz�m� id�pontban lehet �rt�k..., azt jelezz�k
		if ( $entry['ido']%2 && $entry['diak']>=0 && ($entry['diak'] != "") ) {
			$FOGADO[$entry['tanar']]['paratlan'] = 1;
		}
		$FOGADO[$entry['tanar']][$entry['ido']] = $entry['diak'];
	}
}

// visszat�r�s: array (bool b, string s)
// b: true ha v�gre kell hajtani a v�ltoztat�st
// s: a logba �rand� �zenet, ha �res, akkor nem kell �rni
function ValidateRadio ( $Teacher, $Time ) {
// (ezeket j� lenne triggerk�nt berakni a t�bla-defin�ci�ba...)
	global $FOGADO, $USER;
	$ret = array (valid => true, value => NULL);
	if ( $FOGADO[$Teacher][$Time] != 0 ) {
		return array(false, $FOGADO[$Teacher]['nev'] . " " . FiveToString($Time) . " id�pontja m�r foglalt, ide nem iratkozhat fel!");
	}
	foreach ( $FOGADO as $tan ) {
		if ( $tan[$Time] == $USER->id ) {
			return array(false, "�nnek m�r foglalt a " . FiveToString($Time) . " id�pontja (" . $tan['nev'] . ") - el�bb arr�l iratkozzon le!");
		}
	}
	foreach ( array_keys($FOGADO[$Teacher]) as $k ) {
		if ( $FOGADO[$Teacher][$k] == $USER->id ) {
			return array(false, $FOGADO[$Teacher]['nev'] . " " . FiveToString($k) . " id�pontj�ra m�r feliratkozott - ha v�ltoztatni akar, el�bb azt t�r�lje!");
		}
	}
	if ( $FOGADO[$USER->ofo][$Time] == -2 ) {
		return array(true, "�nnek sz�l�i �rtekezlete van ebben az id�pontban (" . FiveToString($Time) . ")!");
	}
	return array(true, NULL);
}

//
// checkboxok ellen�rz�se (leiratkoz�s)
//
if ( $_POST['tip'] == 'mod' ) {
	foreach ( $FOGADO as $tanar ) {
		$v = "c".$tanar['id'];
		foreach ( array_keys($tanar) as $Time ) {
			if ( ( $tanar[$Time] == $USER->id ) && !isset($_POST[$v]) ) {
				$q = "UPDATE Fogado SET diak=0 WHERE tanar=".$tanar['id']." AND ido=$Time";
				if ( pg_query($q) ) {
					$FOGADO[$tanar['id']][$Time] = "0";
					$USER_LOG[] = "RENDBEN: " . $FOGADO[$tanar['id']]['nev'] . ", " . FiveToString($Time) . " - t�r�lve.";
					Ulog($USER->id, $q);
				}
				else { Ulog($USER->id, "L�gy ker�lt a levesbe: $q!"); }
			}
		}
	}
}

//
// r�di�gombok ellen�rz�se (feliratkoz�s)
//
reset($_POST);
while (list($k, $v) = each($_POST)) {
	if ( ereg ("^r([0-9]+)$", $k, $match) ) {
		$Teacher = $match[1];
		$Time = $v;
		$validate = ValidateRadio ($Teacher, $Time);
		if ( $validate[1] ) {
			Ulog($USER->id, $validate[1]);
			$USER_LOG[] = $validate[1];
		}
		if ( $validate[0] ) { // rendben, lehet adatb�zisba rakni
			$q = "UPDATE Fogado SET diak=" . $USER->id . " WHERE tanar=$Teacher AND ido=$Time";
			if ( pg_query($q) ) {
				$FOGADO[$Teacher][$Time] = $USER->id;
				$USER_LOG[] = "RENDBEN: " . $FOGADO[$Teacher]['nev'] . ", " . FiveToString($Time) . " - bejegyezve.";
				Ulog($USER->id, $q);
			}
			else { Ulog($USER->id, "L�gy ker�lt a levesbe: $q!"); }
		}
	}
}

# 10 vagy valah�ny soronk�nt kirakjuk a fejl�cet, hogy lehessen k�vetni
$szamlalo = 0;
# $TablaOutput .= $TablazatIdosor;
foreach ( $FOGADO as $tanar ) {
	if (($szamlalo%8) == 0) $TablaOutput .= $TablazatIdosor;
	$TablaOutput .= tanar_ki($tanar);
	$szamlalo++;
}


// Itt j�n az �sszes ki�r�s

print $Fejlec;

if ($USER_LOG) {
	print "<hr>\n";
	print "<table border=0 width=100%><tr><td bgcolor=#e8e8e8>\n";
	foreach ($USER_LOG as $log) print "<font size=-1><b>$log</b></font><br>\n";
	print "</table>\n";
}

print "\n<form name=tabla method=post><table border=1>"
	. "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. $TablaOutput
	. "<tr><td colspan=" . (($IDO_max-$IDO_min)/2+2) . " align=right class=right>\n"
	. "  <input type=hidden name=tip value=mod>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. "</table>\n\n"
	. "</form>\n";

Tail();
pg_close ($db);

?>

