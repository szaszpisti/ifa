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
Head("Fogad� admin - ".$_REQUEST['page'].". oldal");

/*
Ha oldalsz�m n�lk�l h�vjuk, akkor megn�zi, hogy van-e id�ben k�vetkez� fogad��ra:
ha van, akkor mindj�rt a m�sodik oldalra ugrik, egy�bk�nt az els� az alap�rtelmezett.
*/


$Cim = "\n<table width=100%><tr><td>\n"
   . "<b><font color=#777777># ADMIN #</font></b> - <a href=admin.php>Vissza</a>\n"
   . "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kil�p�s</a>\n</table>\n";

print $Cim;
switch ($_REQUEST['page']) {
	case 0:
		print "\n<ul>\n<li><a href=admin.php?page=1>�j id�pont l�trehoz�sa</a>\n"
			. "<li><a href=admin.php?page=2&datum=".$FA->datum.">A jelenlegi (".$FA->datum.") m�dos�t�sa</a>\n"
   		. "</ul>\n";
		break;
	case 1:  // 1. ADMIN OLDAL

		print "<form><table class=tanar>\n<tr><td class=left>D�tum:<td><input name=datum type=text size=10 value=$Datum><br>\n";

		$B .= "\n<tr><td class=left>Fogad��ra: <td>"
			. ora("kora", $KezdoOra) . perc("kperc", $KezdoPerc) . "\n"
			. ora("vora", $VegOra) . perc("vperc", $VegPerc) . "\n";
		$B .= "\n<tr><td class=left>Sz�l�i: <td>"
			. ora("skora", $SzuloiKezdoOra) . perc("skperc", $SzuloiKezdoPerc) . "\n"
			. ora("svora", $SzuloiVegOra) . perc("svperc", $SzuloiVegPerc) . "\n";

		print $B . $E;

		print "<tr><td class=left>Tartam: <td>" . tartam('tartam') . " perc\n";
		print "<tr><td><td><input type=hidden name=page value=2>\n";
		print "<input type=submit value=\" Mehet \">\n";
		print "</table>\n";
		print "</form>\n";

		break;

	case 2:  // 2. ADMIN OLDAL

		/* Ha nincs d�tum: nem tudunk mit csin�lni...
		ha van: ha m�r l�tezik az admin t�bl�ban, �s nincs m�g fogad� bejegyz�s, akkor mehet tov�bb
		        ha nem l�tezik, l�trehozzuk, mehet tov�bb.
		*/

		if ( !isset($_REQUEST['datum']) ) {
			hiba ("Nincs d�tum megadva");
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
				hiba ("Nem siker�lt regisztr�lni a fogad��r�t");
				return 1;
			}

		} elseif ( count($result) == 1 ) { // van egy ilyen
			$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado WHERE fid=" . $result[0]['id'] ));
			if ( $res['num'] > 0 ) { hiba ("E napon m�r vannak bejegyz�sek - valami nem j�"); return 1; }

		} else {
			hiba ("HAJAJ! Nagy G�Z van... (t�bb egyforma d�tum?)");
			return 1;
		}

		if( $result = pg_query("SELECT * FROM Fogado_admin WHERE datum='".$_REQUEST['datum']."'" )) {
			$FA = pg_fetch_array($result);
		}

#		print "<table border=0><tr><td><a href=$DOCUMENT_NAME?page=1> &lt;&lt; </a>\n";

		$KezdoOra = floor($FA['kezd']/12); $KezdoPerc = $FA['kezd']-$KezdoOra*12;
		$VegOra = floor($FA['veg']/12);  $VegPerc = $FA['veg']-$VegOra*12;

#		print "<td><h3>Fogad��ra: " . $FA['datum'] . "</h3></table>\n\n";
		print "<b>Fogad��ra: " . $FA['datum'] . "</b>\n\n";

		if ($result = pg_query("SELECT * FROM Tanar AS T LEFT OUTER JOIN"
				. "(SELECT ofo FROM Diak GROUP BY ofo) AS D ON (T.id=D.ofo)")) {
			$Tanar = pg_fetch_all($result);
		}

		print "<form>\n<table class=tanar>\n";
		print "<tr><th rowspan=2>Tan�r neve<th colspan=7>Fogad��ra<th colspan=4>Sz�l�i\n";
		print "<tr><th><th colspan=2>kezdet<th colspan=2>v�g<th colspan=2><th><th colspan=2>kezdet<th>v�g\n";
		foreach ($Tanar as $t) {
			$paros = 1-$paros;
			$id=$t['id'];
			print "<tr" . ($paros?" class=paros":"") . "><td>" . $t['tnev'] . "\n";
			print "  <td><input type=checkbox name=a$id checked>\n";
			print "  <td>" . ora("b$id", $KezdoOra) . "\n" . perc("c$id", $KezdoPerc) . "<td>\n";
			print "  <td>" . ora("d$id", $VegOra) . "\n" . perc("e$id", $VegPerc) . "<td>\n";
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
//		print "<h3>Nincs t�bb oldal... mit tegyek?!</h3>\n";

		// ha van bejegyezve m�r ilyen id az id�pontokn�l, akkor m�r j�rtunk itt -> hiba
		if (!isset($_REQUEST['fid'])) { hiba ("Nincs fogad�-azonos�t�"); return 1; }

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado_admin WHERE id=".$_REQUEST['fid'] ));
		if ( $res['num'] != 1 ) { hiba ("Nincs ilyen nap regisztr�lva"); return 1; }

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado WHERE fid=".$_REQUEST['fid'] ));
		if ( $res['num'] > 0 ) { hiba ("E napon m�r vannak bejegyz�sek - valami nem j�"); return 1; }

		// A v�ltoz�kat rendezz�k haszn�lhat� t�mb�kbe
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

		// Felt�ltj�k a Tanar t�mb�t
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
