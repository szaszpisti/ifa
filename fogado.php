<?
require_once('login.php');
require_once('fogado.inc');
require_once('diak.class');

/**
* Egy diák számára kiírja a fogadóóra táblázatot az összes tanárral
*/

$user = new Diak($_SESSION['id']);

Head("Fogadóóra - " . $user->dnev);

$USER_LOG = array();

$Fejlec = 
	  "  <script language=JavaScript><!--\n"
	. "    function torol(sor) {\n"
	. "    eval('var s = document.tabla.'+sor);\n"
	. "    for (var i=0; i<s.length; i++)\n"
	. "      s[i].checked=0;\n"
	. "    }\n"
	. "  //--></script>\n\n"
	. "<table width=100%><tr><td>\n"
	. "<h3>" . $user->dnev . " " . $user->onev .  " (" . $FA->datum . ")<br>\n"
	. "<font size=-1>(Osztályfõnök: " . $user->ofonev . ")</h3>\n"
	. "<td align=right valign=top>\n"
	. "  <a href=osszesit.php?tip=diak&id=" . $user->id . "> Összesítés </a> | \n"
	. "  <a href=leiras.html> Leírás </a> | \n"
	. "  <a href='" . $_SERVER['PHP_SELF'] . "?kilep='> Kilépés </a>\n</table>\n";

// egy tanár-sor a táblázatban
function table_row($K, $tid, $t) {
	for ($i=1; $i<count($K); $i++) { // 1-tõl kell kezdeni, mert a K inicializálásakor került bele egy fölös elem
		$span = (count($K[$i])>1)?" colspan=" . count($K[$i]):"";
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
	global $FA, $user, $K;
	// TANAR: [0]['diak']=25, [1]['diak']=-1, ...

	$State = -3; // nem érvényes kezdeti értéket adunk neki
	$K[0] = array(array()); // páros idõket tesszük ebbe
	$K[1] = array(array()); // páratlanokat
	for ($i=$FA->IDO_min; $i<$FA->IDO_max; $i++) {
		if (!isset($tanar['paratlan']) && $i%2) { continue; }
		switch ($tanar[$i]) {
			case -2:
				if ( ($user->ofo == $tanar['id']) || ADMIN ) { $d = szuloi; }
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
			case $user->id:
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

	$tmp = "\n<tr><th align=left nowrap" . (isset($tanar['paratlan'])?" rowspan=2 valign=top":"") . ">&nbsp;"
		. (ADMIN?"<a href=tanar.php?tip=tanar&id=" . $tanar['id'] . ">" . $tanar['nev'] . "</a>":$tanar['nev']) . "\n";

// párosak:
	$tmp .= table_row($K[0], $tanar['id'], $FA->IDO_min);
	$tmp .= "  <td><input type=button value=x onClick='torol(\"r" . $tanar['id'] . "\")'>\n";

// páratlanok:
	if (isset($tanar['paratlan'])) {
		$tmp .= "<tr>" . table_row($K[1], $tanar['id'], $FA->IDO_min+1);
	}

	return $tmp;

}

/*
A fejléc sorok kiíratásához
IDO: ebben lesznek a kiírandó idõpontok
IDO[16] = (30, 40, 50)
IDO[17] = (00, 10, 20, ...)
*/
for ($ido=$FA->IDO_min; $ido<$FA->IDO_max; $ido+=2) {
	$ora = floor($ido/12);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], ($ido % 12)/2);
}

$A = "\n<tr bgcolor=lightblue><td rowspan=2>";
$B = "\n<tr bgcolor=lightblue>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>" . $perc . "0";
}
$TablazatIdosor = $A . $B;

// Az összes fogadó tanár nevét kigyûjtjük // FOGADO[id]=('id', 'nev')
if( $result = pg_query("SELECT tanar,tnev FROM Fogado,Tanar WHERE fid=" . fid . " AND tanar=id GROUP BY tanar,tnev ORDER BY tnev")) {
	foreach ( pg_fetch_all($result) as $tanar ) {
		$FOGADO[$tanar['tanar']] = array('id' => $tanar['tanar'], 'nev' => $tanar['tnev']);
	}
}

// mindegyikhez az összes idõ => elfoglaltságot (A FOGADO-hoz rakunk még mezõket)
// FOGADO[id]=('id', 'nev', 'paratlan', 'ido1', 'ido2', ... )
if( $result = pg_query("SELECT tanar, ido, diak FROM Fogado WHERE fid=" . fid . " ORDER BY ido")) {
	foreach ( pg_fetch_all($result) as $sor ) {
		// Ha egy páratlan sorszámú idõpontban lehet érték..., azt jelezzük
		if ( $sor['ido']%2 && $sor['diak']>=0 && ($sor['diak'] != "") ) $FOGADO[$sor['tanar']]['paratlan'] = 1;
		$FOGADO[$sor['tanar']][$sor['ido']] = $sor['diak'];
	}
}

// visszatérés: array (bool b, string s)
// b: true ha végre kell hajtani a változtatást
// s: a logba írandó üzenet, ha üres, akkor nem kell írni
function ValidateRadio ( $Teacher, $Time ) {
// (ezeket jó lenne triggerként berakni a tábla-definícióba...)
	global $FOGADO, $user;
	$ret = array (valid => true, value => NULL);
	if ( $FOGADO[$Teacher][$Time] != 0 ) {
		return array(false, $FOGADO[$Teacher]['nev'] . " " . FiveToString($Time) . " idõpontja már foglalt, ide nem iratkozhat fel!");
	}
	foreach ( $FOGADO as $tan ) {
		if ( $tan[$Time] == $user->id ) {
			return array(false, "Önnek már foglalt a " . FiveToString($Time) . " idõpontja (" . $tan['nev'] . ") - elõbb arról iratkozzon le!");
		}
	}
	foreach ( array_keys($FOGADO[$Teacher]) as $k ) {
		if ( $FOGADO[$Teacher][$k] == $user->id ) {
			return array(false, $FOGADO[$Teacher]['nev'] . " " . FiveToString($k) . " idõpontjára már feliratkozott - ha változtatni akar, elõbb azt törölje!");
		}
	}
	if ( $FOGADO[$user->ofo][$Time] == -2 ) {
		return array(true, "Önnek szülõi értekezlete van ebben az idõpontban (" . FiveToString($Time) . ")!");
	}
	return array(true, NULL);
}

// Az Ulog-ot meg köllene csinálni, hogy az adminnál 0 legyen az id
// és figyelmeztetéseket ne logolja
//
// checkboxok ellenõrzése (leiratkozás)
//
if ( $_POST['page'] == 'mod' ) {
	foreach ( $FOGADO as $tanar ) {
		$v = "c" . $tanar['id'];
		foreach ( array_keys($tanar) as $Time ) {
			if ( ( $tanar[$Time] == $user->id ) && !isset($_POST[$v]) ) {
				$q = "UPDATE Fogado SET diak=0 WHERE tanar=" . $tanar['id'] . " AND ido=$Time";
				if ( pg_query($q) ) {
					$FOGADO[$tanar['id']][$Time] = "0";
					$USER_LOG[] = "RENDBEN: " . $FOGADO[$tanar['id']]['nev'] . ", " . FiveToString($Time) . " - törölve.";
					Ulog($user->id, $q);
				}
				else { Ulog($user->id, "Légy került a levesbe: $q!"); }
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
		$validate = ValidateRadio ($Teacher, $Time);
		if ( $validate[1] ) {
			Ulog($user->id, $validate[1]);
			$USER_LOG[] = $validate[1];
		}
		if ( $validate[0] ) { // rendben, lehet adatbázisba rakni
			$q = "UPDATE Fogado SET diak=" . $user->id . " WHERE tanar=$Teacher AND ido=$Time";
			if ( pg_query($q) ) {
				$FOGADO[$Teacher][$Time] = $user->id;
				$USER_LOG[] = "RENDBEN: " . $FOGADO[$Teacher]['nev'] . ", " . FiveToString($Time) . " - bejegyezve.";
				Ulog($user->id, $q);
			}
			else { Ulog($user->id, "Légy került a levesbe: $q!"); }
		}
	}
}

# 10 vagy valahány soronként kirakjuk a fejlécet, hogy lehessen követni
$szamlalo = 0;
# $TablaOutput .= $TablazatIdosor;
foreach ( $FOGADO as $tanar ) {
	if (($szamlalo%8) == 0) $TablaOutput .= $TablazatIdosor;
	$TablaOutput .= tanar_ki($tanar);
	$szamlalo++;
}


// Itt jön az összes kiírás

print $Fejlec;

if ($USER_LOG) {
	print "<hr>\n";
	print "<table border=0 width=100%><tr><td bgcolor=#e8e8e8>\n";
	foreach ($USER_LOG as $log) print "<font size=-1><b>$log</b></font><br>\n";
	print "</table>\n";
}

print "\n<form name=tabla method=post><table border=1>"
	. "<tr><td colspan=" . (($FA->IDO_max-$FA->IDO_min)/2+2) . " align=right class=right>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. $TablaOutput
	. "<tr><td colspan=" . (($FA->IDO_max-$FA->IDO_min)/2+2) . " align=right class=right>\n"
	. "  <input type=hidden name=page value=mod>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. "</table>\n\n"
	. "</form>\n";

Tail();
pg_close ($db);

?>

