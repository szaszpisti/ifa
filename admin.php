<?
require_once('login.php');
require_once('fogado.inc');

if (!$_SESSION['admin']) redirect('leiras.html');

if (!isset($_REQUEST['page'])) $_REQUEST['page'] = 0;

/*
Ha oldalsz�m n�lk�l h�vjuk, akkor megn�zi, hogy van-e id�ben k�vetkez� fogad��ra:
ha van, akkor mindj�rt a m�sodik oldalra ugrik, egy�bk�nt az els� az alap�rtelmezett.
*/


$Out = "\n<table width=100%><tr><td>\n"
   . "<b><font color=#777777>" . $_SESSION['nev'] . "</font></b>\n"
   . "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kil�p�s</a>\n</table>\n\n"
	. "<hr>\n\n";

if ($_REQUEST['page'] == 4) {
	$Out .= "Fogad��ra bejegyezve: " . $_REQUEST['datum'] . "\n\n";
	$_REQUEST['page'] = 0;
}

switch ($_REQUEST['page']) {
	case 0:
		$Out .= "<h3>Az aktu�lis (legut�bb bejegyzett) fogad��ra: " . $FA->datum . "</h3>\n<ul>\n"
			. "<li><a href=admin.php?page=1>�j id�pont l�trehoz�sa</a>\n"
			. "<li><a href=fogado-xls.pl>T�bl�zat let�lt�se</a>\n"
   		. "</ul>\n\n";
		break;

	case 1:  // 1. ADMIN OLDAL
		$MaiDatum = date('Y-m-d');

		$Out .= "<h3>�j id�pont l�trehoz�sa</h3>\n"
			. "<ul><form method=post><table class=tanar cellpadding=3>\n"
			. "<tr><td colspan=2>&nbsp;\n"

			. "<tr><td class=left colspan=2><b><i>Fogad��ra napja:</i></b>\n"
			. "<tr><td>\n"
			. "    <td class=right><input name=datum type=text size=10 value=\"$MaiDatum\">\n"
			. "<tr><td colspan=2>&nbsp;\n"

			. "<tr><td class=left colspan=2><b><i>Bejelentkez�si id�szak:</i></b>\n"
			. "<tr><td class=right> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; kezdete:\n"
			. "    <td><input name=valid_kezd type=text size=16 value=\"$MaiDatum 08:00\">\n"
			. "<tr><td class=right>v�ge:\n"
			. "    <td><input name=valid_veg type=text size=16 value=\"$MaiDatum 14:00\">\n"
			. "<tr><td colspan=2>&nbsp;\n"

			. "<tr><td class=left colspan=2><b><i>Alap�rtelmez�sek:</i></b>\n"
			. "<tr><td class=right>jelenl�t: <td>\n"
			. "    " . SelectIdo("kora", "kperc", $FogadoIdo[0]) . "\n"
			. "    " . SelectIdo("vora", "vperc", $FogadoIdo[1]) . "\n"
			. "<tr><td class=right>tartam: <td>\n"
			. "    " . SelectTartam('tartam') . " perc\n"
			. "<tr><td class=right>sz�l�i: <td>\n"
			. "    " . SelectIdo("skora", "skperc", $SzuloiIdo[0]) . "\n"
			. "    " . SelectIdo("svora", "svperc", $SzuloiIdo[1]) . "\n"
			. "<tr><td>\n"
			. "    <td class=right><input type=hidden name=page value=2>\n"
			. "        <input type=submit value=\" Mehet \">\n"
			. "</table>\n"
			. "</form></ul>\n";

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

		$result = pg_fetch_all(pg_query("SELECT * FROM Fogado_admin WHERE datum='" . $_REQUEST['datum'] . "'" ));

		if ( !$result || (count($result) == 0) ) { // nincs ilyen
			$FogadoIdo = array (
				$_REQUEST['kora'] + $_REQUEST['kperc'],
				$_REQUEST['vora'] + $_REQUEST['vperc']
			);

			if ( $_REQUEST['datum'] && $_REQUEST['tartam']
							&& $_REQUEST['valid_kezd'] && $_REQUEST['valid_veg'] ) {
				$q = "INSERT INTO Fogado_admin (datum, kezd, veg, tartam, valid_kezd, valid_veg) VALUES"
					. " ('" . $_REQUEST['datum'] . "', $FogadoIdo[0], $FogadoIdo[1], "
					. $_REQUEST['tartam'] . ", '"
					. $_REQUEST['valid_kezd'] . "', '"
					. $_REQUEST['valid_veg'] . "')";
			}
			else {
				hiba ("Valami nincs megadva");
				return 1;
			}

			if ( $result = pg_query($q) ) {
				Ulog(0, "RENDBEN: $q");
			}
			else {
				hiba ("Nem siker�lt regisztr�lni a fogad��r�t");
				return 1;
			}

		}
		elseif ( count($result) == 1 ) { // van egy ilyen
			$q = "SELECT count(*) as num FROM Fogado WHERE fid=" . $result[0]['id'];
			$res = pg_fetch_array(pg_query($q));
			if ( $res['num'] > 0 ) { hiba ("E napon m�r vannak bejegyz�sek"); return 1; }

		}
		else {
			hiba ("HAJAJ! Nagy G�Z van... (t�bb egyforma d�tum?)");
			return 1;
		}

		if( $result = pg_query("SELECT * FROM Fogado_admin WHERE datum='" . $_REQUEST['datum'] . "'" )) {
			$FA = pg_fetch_array($result);
		}

		$Out .= "<b>Fogad��ra: " . $FA['datum'] . "</b>\n\n";

		if ($result = pg_query("SELECT * FROM Tanar AS T LEFT OUTER JOIN"
				. "(SELECT ofo FROM Diak GROUP BY ofo) AS D ON (T.id=D.ofo) ORDER BY tnev")) {
			$Tanar = pg_fetch_all($result);
		}

		$Out .= "<form method=post>\n<table class=tanar>\n"
			. "<tr><th><th colspan=4>\n"
			. "<tr><th>Tan�r neve<th><th>Fogad��ra<th>tartam<th><th colspan=2>Sz�l�i<th>\n";
		foreach ($Tanar as $t) {
			$paratlan = 1-$paratlan;
			$id=$t['id'];
			$Out .= "\n<tr" . ($paratlan?" class=paratlan":"") . "><td>" . $t['tnev'] . "\n"
				. "  <td><input type=checkbox name=a$id checked>\n"
				. "  <td>" . SelectIdo("b$id", "c$id", $FogadoIdo[0]) . " &nbsp;\n"
				. "      " . SelectIdo("d$id", "e$id", $FogadoIdo[1]) . " &nbsp;\n"
				. "  <td align=center>" . SelectTartam("f$id") . "<td>\n";
			if ( isset($t['ofo']) ) {
				$Out .= "  <td><input type=checkbox name=g$id checked>\n"
					. "  <td>" . SelectIdo("h$id", "i$id", $_REQUEST['skora'] + $_REQUEST['skperc']) . " &nbsp;\n"
					. "      " . SelectIdo("j$id", "k$id", $_REQUEST['svora'] + $_REQUEST['svperc']) . " &nbsp;\n";
			}
			else {
				$Out .= "  <td colspan=4>\n";
			}
		}
		$Out .= "<tr><td colspan=12 class=right>\n"
			. "<input type=hidden name=fid value=" . $FA['id'] . ">\n"
			. "<input type=hidden name=page value=3>\n"
			. "<input type=hidden name=datum value=" . $_REQUEST['datum'] . ">\n"
			. "<input type=submit value=\" Mehet \">\n"
			. "</table>\n"
			. "</form>\n";

		break;


	// A bejegyz�sek alapj�n a fogad��ra t�bl�j�nak felt�lt�se
	case 3:

		// ha van bejegyezve m�r ilyen id az id�pontokn�l, akkor m�r j�rtunk itt -> hiba
		if (!isset($_REQUEST['fid'])) {
			hiba ("Nincs fogad�-azonos�t�");
			return 1;
		}

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado_admin WHERE id=" . $_REQUEST['fid'] ));
		if ( $res['num'] != 1 ) {
			hiba ("Nincs ilyen nap regisztr�lva");
			return 1;
		}

		$res = pg_fetch_array(pg_query("SELECT count(*) as num FROM Fogado WHERE fid=" . $_REQUEST['fid'] ));
		if ( $res['num'] > 0 ) {
			hiba ("E napon m�r vannak bejegyz�sek");
			return 1;
		}

		// A v�ltoz�kat rendezz�k haszn�lhat� t�mb�kbe
		//    $JelenVan[id] (id, kezd, veg, tartam)
		//    $Szuloi[id] (id, kezd, veg)

		reset($_REQUEST);
		while (list($k, $v) = each ($_REQUEST)) {
			if ( ereg ("^a([0-9]+)$", $k, $match) ) {
				$id = $match[1];
				$JelenVan[$id] = array('id' => $id,
					'kezd' => $_REQUEST["b" . $id] + $_REQUEST["c" . $id],
					'veg'  => $_REQUEST["d" . $id] + $_REQUEST["e" . $id],
					'tartam' => $_REQUEST["f" . $id] );
			}
			if ( ereg ("^g([0-9]+)$", $k, $match) ) {
				$id = $match[1];
				$Szuloi[$id] = array('id' => $id,
					'kezd' => $_REQUEST["h" . $id] + $_REQUEST["i" . $id],
					'veg'  => $_REQUEST["j" . $id] + $_REQUEST["k" . $id] );
			}
		}

		// Felt�ltj�k a Tanar t�mb�t, ez ilyen form�n fog majd az adatb�zisba ker�lni
		foreach ($JelenVan as $t) {
			if ( $t['kezd'] && $t['veg'] && $t['tartam'] ) {
				// el�sz�r az �sszes id�pontj�t nem foglalhat�v� (-1) tessz�k
				for ($i=$t['kezd']; $i<$t['veg']; $i++) {
					$Tanar[$t['id']][$i]=-1;
				}
				// majd bejel�lj�k a foglalhat�kat (0)
				for ($i=$t['kezd']; $i<$t['veg']; $i+=$t['tartam']) {
					$Tanar[$t['id']][$i]=0;
				}
			}
		}

		foreach ($Szuloi as $t) {
			if ( $t['kezd'] && $t['veg'] && isset($JelenVan[$t['id']]) ) {
				for ($i=$t['kezd']; $i<$t['veg']; $i++) $Tanar[$t['id']][$i]=-2;
			}
		}

		foreach ( array_keys($Tanar) as $id ) {
			reset ($Tanar[$id]);
			while (list ($key, $val) = each ($Tanar[$id])) {
			   $Tanar_copy[] = $_REQUEST['fid'] . "\t$id\t$key\t$val";
			}
		}
		if ( ! pg_copy_from ($db, "fogado", &$Tanar_copy) ) { $Out .= "nem siker�lt"; }

		header("Location: " . $_SERVER['PHP_SELF'] . "?page=4&datum=" . $_REQUEST['datum']);

		break;

	// Rossz param�terek
	default:
		hiba ("�rv�nytelen oldal: " . $_REQUEST['page']);
		return 1;
		break;
}

Head("Fogad� admin - " . $_REQUEST['page'] . ". oldal");
print $Out;
Tail();
?>
