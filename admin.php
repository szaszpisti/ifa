<?
require('fogado.inc');

include("user.class");
$USER = new User();

if (!$USER->admin) {
   $USER->kilep();
   exit();
}

$Datum = date('Y-m-d');

if (!isset($_REQUEST['page'])) $_REQUEST['page'] = 0;
Head("Fogadó admin - ".$_REQUEST['page'].". oldal");

/*
Ha oldalszám nélkül hívjuk, akkor megnézi, hogy van-e idõben következõ fogadóóra:
ha van, akkor mindjárt a második oldalra ugrik, egyébként az elsõ az alapértelmezett.
*/


// default kezd-, vég-óra, -perc:
$Kora=16;  $Kperc=0;  $Vora=18;  $Vperc=0;
$SKora=17; $SKperc=0; $SVora=17; $SVperc=30;

$ORA = "<select name=#NAME#>";
for ($i=8; $i<21; $i++) { $I = sprintf("%02d", $i); $ORA .= "<option value=$I>$I"; }
$ORA .= "</select>";
function ora($name, $o){
	global $ORA;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/value=$o>/", "value=$o selected>", $ORA);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

$PERC = "<select name=#NAME#>";
foreach (array('00', '10', '20', '30', '40', '50') as $i ) { $PERC .= "<option value=".floor($i/10).">$i"; }
$PERC .= "</select>";
function perc($name, $p){
	global $PERC;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/>$p/", " selected>$p", $PERC);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

function tartam($name) {
	return preg_replace("/#NAME#/", "$name", "<select name=#NAME#>"
		. "<option value=1>5<option value=2 selected>10"
		. "<option value=3>15<option value=4>20</select>");
}

$Cim = "\n<table width=100%><tr><td>\n"
   . "<b><font color=#777777># ADMIN #</font></b> - <a href=admin.php>Vissza</a>\n"
   . "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kilépés</a>\n</table>\n";

print $Cim;
switch ($_REQUEST['page']) {
	case 0:
		print "\n<ul>\n<li><a href=admin.php?page=1>Új idõpont létrehozása</a>\n"
			. "<li><a href=admin.php?page=2&datum=".$FA->datum.">A jelenlegi (".$FA->datum.") módosítása</a>\n"
   		. "</ul>\n";
		break;
	case 1:  // 1. ADMIN OLDAL

		print "<form><table class=tanar>\n<tr><td class=left>Dátum:<td><input name=datum type=text size=10 value=$Datum><br>\n";

		$B .= "\n<tr><td class=left>Fogadóóra: <td>"
			. ora("kora", $Kora) . perc("kperc", $Kperc) . "\n"
			. ora("vora", $Vora) . perc("vperc", $Vperc) . "\n";
		$B .= "\n<tr><td class=left>Szülõi: <td>"
			. ora("skora", $SKora) . perc("skperc", $SKperc) . "\n"
			. ora("svora", $SVora) . perc("svperc", $SVperc) . "\n";

		print $B . $E;

		print "<tr><td class=left>Tartam: <td>" . tartam('tartam') . " perc\n";
		print "<tr><td><td><input type=hidden name=page value=2>\n";
		print "<input type=submit value=\" Mehet \">\n";
		print "</table>\n";
		print "</form>\n";

		break;

	case 2:  // 2. ADMIN OLDAL

		/* Ha nincs dátum: nem tudunk mit csinálni...
		ha van: ha már létezik az admin táblában, és nincs még fogadó bejegyzés, akkor mehet tovább
		        ha nem létezik, létrehozzuk, mehet tovább.
		*/

		if ( !isset($_REQUEST['datum']) ) {
			hiba ("Nincs dátum megadva");
			return 1;
		}

		$result = pg_fetch_all(pg_query("SELECT * FROM Fogado_admin WHERE datum='".$_REQUEST['datum']."'" ));
		if ( !$result || (count($result) == 0) ) { // nincs ilyen
			$kezd = $_REQUEST['kora']*12+$_REQUEST['kperc'];
			$veg  = $_REQUEST['vora']*12+$_REQUEST['vperc'];
			if ( $_REQUEST['datum'] && $kezd && $veg && $_REQUEST['tartam'] ) {
				$q = "INSERT INTO Fogado_admin (datum, kezd, veg, tartam) VALUES"
					. " ('".$_REQUEST['datum']."', $kezd, $veg, ".$_REQUEST['tartam'].")";
			} else {
				hiba ("Valami nincs megadva");
				return 1;
			}

//			print $q;
			if ( $result = pg_query($q) ) {
				Ulog("RENDBEN: $q");
			} else {
				hiba ("Nem sikerült regisztrálni a fogadóórát");
				return 1;
			}

		} elseif ( count($result) == 1 ) { // van egy ilyen
			$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado WHERE fid=" . $result[0]['id'] ));
			if ( $res['num'] > 0 ) { hiba ("E napon már vannak bejegyzések - valami nem jó"); return 1; }

		} else {
			hiba ("HAJAJ! Nagy GÁZ van... (több egyforma dátum?)");
			return 1;
		}

		if( $result = pg_query("SELECT * FROM Fogado_admin WHERE datum='".$_REQUEST['datum']."'" )) {
			$FA = pg_fetch_array($result);
		}

#		print "<table border=0><tr><td><a href=$DOCUMENT_NAME?page=1> &lt;&lt; </a>\n";

		$Kora = floor($FA['kezd']/12); $Kperc = $FA['kezd']-$Kora*12;
		$Vora = floor($FA['veg']/12);  $Vperc = $FA['veg']-$Vora*12;

#		print "<td><h3>Fogadóóra: " . $FA['datum'] . "</h3></table>\n\n";
		print "<b>Fogadóóra: " . $FA['datum'] . "</b>\n\n";

		if ($result = pg_query("SELECT * FROM Tanar AS T LEFT OUTER JOIN"
				. "(SELECT ofo FROM Diak GROUP BY ofo) AS D ON (T.id=D.ofo)")) {
			$Tanar = pg_fetch_all($result);
		}

		print "<form>\n<table class=tanar>\n";
		print "<tr><th rowspan=2>Tanár neve<th colspan=7>Fogadóóra<th colspan=4>Szülõi\n";
		print "<tr><th><th colspan=2>kezdet<th colspan=2>vég<th colspan=2><th><th colspan=2>kezdet<th>vég\n";
		foreach ($Tanar as $t) {
			$paros = 1-$paros;
			$id=$t['id'];
			print "<tr" . ($paros?" class=paros":"") . "><td>" . $t['tnev'] . "\n";
			print "  <td><input type=checkbox name=a$id checked>\n";
			print "  <td>" . ora("b$id", $Kora) . "\n" . perc("c$id", $Kperc) . "<td>\n";
			print "  <td>" . ora("d$id", $Vora) . "\n" . perc("e$id", $Vperc) . "<td>\n";
			print "  <td>" . tartam("f$id") . "<td>\n";
			if ( isset($t['ofo']) ) {
				print "  <td><input type=checkbox name=g$id checked>\n";
				print "  <td>" . ora("h$id", $_REQUEST['skora']) . "\n" . perc("i$id", $_REQUEST['skperc']) . "<td>\n";
				print "  <td>" . ora("j$id", $_REQUEST['svora']) . "\n" . perc("k$id", $_REQUEST['svperc']) . "\n";
			} else {
				print "  <td colspan=4>\n";
			}
		}
		print "</table>\n";
		print "<input type=hidden name=fid value=".$FA['id'].">\n";
		print "<input type=hidden name=page value=3>\n";
		print "<input type=submit value=\" Mehet \">\n";
		print "</form>\n";

		break;

		print "<input type=hidden name=page value=2>\n";

	default:
//		print "<h3>Nincs több oldal... mit tegyek?!</h3>\n";

		// ha van bejegyezve már ilyen id az idõpontoknál, akkor már jártunk itt -> hiba
		if (!isset($_REQUEST['fid'])) { hiba ("Nincs fogadó-azonosító"); return 1; }

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado_admin WHERE id=".$_REQUEST['fid'] ));
		if ( $res['num'] != 1 ) { hiba ("Nincs ilyen nap regisztrálva"); return 1; }

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado WHERE fid=".$_REQUEST['fid'] ));
		if ( $res['num'] > 0 ) { hiba ("E napon már vannak bejegyzések - valami nem jó"); return 1; }

		// A változókat rendezzük használható tömbökbe
		//    $Jelen[id](id, kezd, veg, tartam)
		//    $Szuloi[id](id, kezd, veg)
		//
		reset($_REQUEST);
		while (list($k, $v) = each ($_REQUEST)) { //explode(' ', $VARIABLES) as $var) {
			if ( ereg ("^a([0-9]+)$", $k, $match) ) {
				$id = $match[1];
				$Jelen[$id] = array('id' => $id,
					'kezd' => $_REQUEST["b".$id]*12+$_REQUEST["c".$id],
					'veg' => $_REQUEST["d".$id]*12+$_REQUEST["e".$id],
					'tartam' => $_REQUEST["f".$id] );
			}
			if ( ereg ("^g([0-9]+)$", $k, $match) ) {
				$id = $match[1];
				$Szuloi[$id] = array('id' => $id,
					'kezd' => $_REQUEST["h".$id]*12+$_REQUEST["i".$id],
					'veg' => $_REQUEST["j".$id]*12+$_REQUEST["k".$id] );
			}
		}

		// Feltöltjük a Tanar tömböt
		foreach ($Jelen as $t) {
			if ( $t['kezd'] && $t['veg'] && $t['tartam'] ) {
				for ($i=$t['kezd']; $i<$t['veg']; $i++) $Tanar[$t['id']][$i]=-1;
				for ($i=$t['kezd']; $i<$t['veg']; $i+=$t['tartam']) $Tanar[$t['id']][$i]=0;
			}
		}

		foreach ($Szuloi as $t) {
			if ( $t['kezd'] && $t['veg'] && isset($Jelen[$t['id']]) ) {
				for ($i=$t['kezd']; $i<$t['veg']; $i++) $Tanar[$t['id']][$i]=-2;
			}
		}

		foreach ( array_keys($Tanar) as $id ) {
			reset ($Tanar[$id]);
			while (list ($key, $val) = each ($Tanar[$id])) {
			   $Tanar_copy[] = $_REQUEST['fid']."\t$id\t$key\t$val";
			}
		}
		if ( ! pg_copy_from ($db, "fogado", &$Tanar_copy) ) { print "nem siker"; }

		break;
}

Tail();
?>
