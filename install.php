<?
require('fogado.inc');

/*
Ha oldalsz�m n�lk�l h�vjuk, akkor megn�zi, hogy van-e id�ben k�vetkez� fogad��ra:
ha van, akkor mindj�rt a m�sodik oldalra ugrik, egy�bk�nt az els� az alap�rtelmezett.
*/

$Datum = date('Y-m-d');

if (!isset($VAR_page)) {
	if( $result = pg_exec("SELECT * FROM Fogado_admin WHERE id = (SELECT MAX(id) FROM Fogado_admin)" )) {
		$FA = pg_fetch_array($result);
		if ( $FA['datum'] >= $Date ) {
			$VAR_page = 2;
			$Datum = $FA['datum'];
		}
		else $VAR_page = 1;
	} else {
		$VAR_page=1;
	}
}

Head("Fogad� admin - $VAR_page. oldal");

// default kezd-, v�g-�ra, -perc:
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

$TARTAM = "<select name=#NAME#><option value=1>5<option value=2 selected>10"
	. "<option value=3>15<option value=4>20</select>";
function tartam($name) {
	global $TARTAM;
	return preg_replace("/#NAME#/", "$name", $TARTAM);
}

switch ($VAR_page) {
	case 1:  // 1. ADMIN OLDAL

		print "<h3>Fogad��ra admin</h3>\n\n";
		print "<form><table class=tanar>\n<tr><td class=left>D�tum:<td><input name=datum type=text size=10 value=$Datum><br>\n";

		$B .= "\n<tr><td class=left>Fogad��ra: <td>"
			. ora("kora", $Kora) . perc("kperc", $Kperc) . "\n"
			. ora("vora", $Vora) . perc("vperc", $Vperc) . "\n";
		$B .= "\n<tr><td class=left>Sz�l�i: <td>"
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

		/* Ha nincs d�tum: nem tudunk mit csin�lni...
		ha van: ha m�r l�tezik az admin t�bl�ban, �s nincs m�g fogad� bejegyz�s, akkor mehet tov�bb
		        ha nem l�tezik, l�trehozzuk, mehet tov�bb.
		*/

		if ( !isset($VAR_datum) ) {
			Err ("Nincs d�tum megadva");
			return 1;
		}

		$result = pg_fetch_all(pg_exec("SELECT * FROM Fogado_admin WHERE datum='$VAR_datum'" ));
		if ( !$result || (count($result) == 0) ) { // nincs ilyen
print "megvan";
			$kezd = $VAR_kora*12+$VAR_kperc;
			$veg  = $VAR_vora*12+$VAR_vperc;
			if ( $VAR_datum && $kezd && $veg && $VAR_tartam ) {
				$q = "INSERT INTO Fogado_admin (datum, kezd, veg, tartam) VALUES"
					. " ('$VAR_datum', $kezd, $veg, $VAR_tartam)";
			} else {
				Err ("Valami nincs megadva");
				return 1;
			}

//			print $q;
			if ( $result = pg_exec($q) ) {
				print "RENDBEN: $q\n";
			} else {
				Err ("Nem siker�lt regisztr�lni a fogad��r�t");
				return 1;
			}

		} elseif ( count($result) == 1 ) { // van egy ilyen
			$res = pg_fetch_array(pg_exec("SELECT count(*) as num FROM Fogado WHERE fid=" . $result[0]['id'] ));
			if ( $res['num'] > 0 ) { Err ("E napon m�r vannak bejegyz�sek - valami nem j�"); return 1; }

		} else {
			Err ("HAJAJ! Nagy G�Z van... (t�bb egyforma d�tum?)");
			return 1;
		}

		if( $result = pg_exec("SELECT * FROM Fogado_admin WHERE datum='$VAR_datum'" )) {
			$FA = pg_fetch_array($result);
		}

		print "<table border=0><tr><td><a href=$DOCUMENT_NAME?page=1> &lt;&lt; </a>\n";

		$Kora = floor($FA['kezd']/12); $Kperc = $FA['kezd']-$Kora*12;
		$Vora = floor($FA['veg']/12);  $Vperc = $FA['veg']-$Vora*12;

		print "<td><h3>Fogad��ra: " . $FA['datum'] . "</h3></table>\n\n";

		if( $result = pg_exec("SELECT esz, enev, o FROM (SELECT Ember.*, Osztaly.oszt AS o FROM Ember"
				. " LEFT OUTER JOIN Osztaly USING (esz)) AS Tmp WHERE oszt='t' ORDER BY enev")) {
			$Tanar = pg_fetch_all($result);
		}

		print "<form>\n<table class=tanar>\n";
		print "<tr><th rowspan=2>Tan�r neve<th colspan=7>Fogad��ra<th colspan=4>Sz�l�i\n";
		print "<tr><th><th colspan=2>kezdet<th colspan=2>v�g<th colspan=2><th><th colspan=2>kezdet<th>v�g\n";
		foreach ($Tanar as $t) {
			$paros = 1-$paros;
			$id=$t['esz'];
			print "<tr" . ($paros?" class=paros":"") . "><td>" . $t['enev'] . "\n";
			print "  <td><input type=checkbox name=a$id checked>\n";
			print "  <td>" . ora("b$id", $Kora) . "\n" . perc("c$id", $Kperc) . "<td>\n";
			print "  <td>" . ora("d$id", $Vora) . "\n" . perc("e$id", $Vperc) . "<td>\n";
			print "  <td>" . tartam("f$id") . "<td>\n";
			if ( isset($t['o']) ) {
				print "  <td><input type=checkbox name=g$id checked>\n";
				print "  <td>" . ora("h$id", $VAR_skora) . "\n" . perc("i$id", $VAR_skperc) . "<td>\n";
				print "  <td>" . ora("j$id", $VAR_svora) . "\n" . perc("k$id", $VAR_svperc) . "\n";
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
		if (!isset($VAR_fid)) { Err ("Nincs fogad�-azonos�t�"); return 1; }

		$res = pg_fetch_array(pg_exec("SELECT count(*) as num FROM Fogado_admin WHERE id=$VAR_fid" ));
		if ( $res['num'] != 1 ) { Err ("Nincs ilyen nap regisztr�lva"); return 1; }

		$res = pg_fetch_array(pg_exec("SELECT count(*) as num FROM Fogado WHERE fid=$VAR_fid" ));
		if ( $res['num'] > 0 ) { Err ("E napon m�r vannak bejegyz�sek - valami nem j�"); return 1; }

		// A v�ltoz�kat rendezz�k haszn�lhat� t�mb�kbe
		//    $Jelen[id](id, kezd, veg, tartam)
		//    $Szuloi[id](id, kezd, veg)
		//
		foreach (explode(' ', $VARIABLES) as $var) {
			if ( ereg ("^a([0-9]+)$", $var, $match) ) {
				$id = $match[1];
				$Jelen[$id] = array('id' => $id,
					'kezd' => ${"VAR_b".$id}*12+${"VAR_c".$id},
					'veg' => ${"VAR_d".$id}*12+${"VAR_e".$id},
					'tartam' => ${"VAR_f".$id} );
			}
			if ( ereg ("^g([0-9]+)$", $var, $match) ) {
				$id = $match[1];
				$Szuloi[$id] = array('id' => $id,
					'kezd' => ${"VAR_h".$id}*12+${"VAR_i".$id},
					'veg' => ${"VAR_j".$id}*12+${"VAR_k".$id} );
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
			   $Tanar_copy[] = "$VAR_fid\t$id\t$key\t$val";
			}
		}
		if ( ! pg_copy_from ($db, "fogado", &$Tanar_copy) ) { print "nem siker"; }

		break;
}

Tail();
?>
