<?
require_once('login.php');
require_once('fogado.inc.php');

if (!$_SESSION['admin']) redirect('leiras.html');

if (!isset($_REQUEST['page'])) $_REQUEST['page'] = 0;

/*
Ha oldalsz�m n�lk�l h�vjuk, akkor megn�zi, hogy van-e id�ben k�vetkez� fogad��ra:
ha van, akkor mindj�rt a m�sodik oldalra ugrik, egy�bk�nt az els� az alap�rtelmezett.
*/


$Out = "\n<table width=\"100%\"><tr><td>\n"
   . "<b><font color=\"#777777\">" . $_SESSION['nev'] . "</font></b>\n"
   . "<td align=right valign=top><a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kil�p�s</a>\n</table>\n\n"
	. "<hr>\n\n";

if ($_REQUEST['page'] == 4) {
	$Out .= "Fogad��ra bejegyezve: " . $_REQUEST['datum'] . "\n\n";
	$_REQUEST['page'] = 0;
}

switch ($_REQUEST['page']) {
	case 0:
		$Out .= "<h3>Az aktu�lis (legut�bb bejegyzett) fogad��ra: " . $FA->datum . "</h3>\n<ul>\n"
			. "<li><a href=\"admin.php?page=1\">�j id�pont l�trehoz�sa</a>\n"
			. "<li><a href=\"fogado-xls.pl\">T�bl�zat let�lt�se</a>\n"
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

		if ( !isset($_REQUEST['datum']) ) { hiba ("Nincs d�tum megadva"); return 1; }

		$result =& $db->query("SELECT * FROM Fogado_admin WHERE datum='" . $_REQUEST['datum'] . "'" );

		if ( $result->numRows() === 0 ) { // nincs m�g ilyen nap, l�tre lehet hozni
			$FogadoIdo = array (
				$_REQUEST['kora'] + $_REQUEST['kperc'],
				$_REQUEST['vora'] + $_REQUEST['vperc']
			);

			if (!$_REQUEST['tartam']) { hiba ("Tartam nincs megadva"); return 1; }
			if (!$_REQUEST['valid_kezd']) { hiba ("�rv�nyess�g kezdete nincs megadva"); return 1; }
			if (!$_REQUEST['valid_veg']) { hiba ("�rv�nyess�g v�ge nincs megadva"); return 1; }

			$q = "INSERT INTO Fogado_admin (datum, kezd, veg, tartam, valid_kezd, valid_veg) VALUES ('"
					. $_REQUEST['datum'] . "', $FogadoIdo[0], $FogadoIdo[1], "
					. $_REQUEST['tartam'] . ", '"
					. $_REQUEST['valid_kezd'] . "', '"
					. $_REQUEST['valid_veg'] . "')";

			$res =& $db->query($q);
			if (DB::isError($res)) {
				hiba ("Nem siker�lt regisztr�lni a fogad��r�t");
				die($res->getMessage());
			}
			else {
				Ulog(0, "RENDBEN: $q");
			}

		}
		elseif ( $result->numRows() === 1 ) {
			$result->fetchInto($row);
			$num =& $db->getOne("SELECT count(*) as num FROM Fogado WHERE fid=" . $row['id']);
			if ($num > 0 ) { hiba ("E napon m�r vannak bejegyz�sek"); return 1; }
		}
		else {
			hiba ("HAJAJ! Nagy G�Z van... (t�bb egyforma d�tum?)");
			return 1;
		}

		// t�l vagyunk az id�pontbejegyz�sen, �jb�l beolvassuk az aktu�lisat
		// $FA a fogad��ra bejegyz�s asszociat�v t�mbje

		$FA =& $db->getRow("SELECT * FROM Fogado_admin WHERE datum='" . $_REQUEST['datum'] . "'" );
		if (DB::isError($FA)) { die($FA->getMessage()); }

		$Out .= "<b>Fogad��ra: " . $FA['datum'] . "</b>\n\n";

		// Ki�rjuk soronk�nt a tan�rokat az egy�ni be�ll�t�sokhoz
      // eredm�ny: Tanar[id] = array (emil, tnev, ofo)
		$Tanar =& $db->getAssoc(
						  "SELECT * FROM Tanar AS T"
						. "    LEFT OUTER JOIN"
						. "  (SELECT ofo FROM Diak GROUP BY ofo) AS D"
						. "    ON (T.id=D.ofo) ORDER BY tnev",
						true, array(), DB_FETCHMODE_ASSOC);

		// Out-ba gy�jtj�k a kimenetet, kezdj�k a fejl�ccel
		$Out .= "<form method=post>\n<table class=tanar>\n"
			. "<tr><th><th colspan=4>\n"
			. "<tr><th>Tan�r neve<th><th>Fogad��ra<th>tartam<th><th colspan=2>Sz�l�i<th>\n";

		// A tan�r t�mb�n megy�nk v�gig egyes�vel
		foreach (array_keys($Tanar) as $id) {
			$t = $Tanar[$id];

			$paratlan = 1-$paratlan;   // a sz�nez�s miatt v�ltott sorosan haladunk

			$Out .= "\n<tr" . ($paratlan?" class=paratlan":"") . "><td>" . $t['tnev'] . "\n"
				. "  <td><input type=checkbox name=a$id checked>\n"
				. "  <td>" . SelectIdo("b$id", "c$id", $FogadoIdo[0]) . " &nbsp;\n"
				. "      " . SelectIdo("d$id", "e$id", $FogadoIdo[1]) . " &nbsp;\n"
				. "  <td align=center>" . SelectTartam("f$id") . "<td>\n";

			if ( $t['ofo'] > 0 ) {
				$Out .= "  <td><input type=checkbox name=g$id checked>\n"
					. "  <td>" . SelectIdo("h$id", "i$id", $_REQUEST['skora'] + $_REQUEST['skperc']) . " &nbsp;\n"
					. "      " . SelectIdo("j$id", "k$id", $_REQUEST['svora'] + $_REQUEST['svperc']) . " &nbsp;\n";
			}
			else {
				$Out .= "  <td colspan=4>\n";
			}
		}

		// L�bl�c
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

		// ha nem tudjuk, melyik fogad�-azonos�t�hoz kell bejegyz�seket csin�lni
		if (!isset($_REQUEST['fid'])) { hiba ("Nincs fogad�-azonos�t�"); return 1; }

		// csak akkor tudunk tov�bbl�pni, ha 1! bejegyz�s van az adott napon
		$num =& $db->getOne("SELECT count(*) as num FROM Fogado_admin WHERE id=" . $_REQUEST['fid'] );
		if (DB::isError($num)) { die($num->getMessage()); }
		if ( $num != 1 ) { hiba ("Nincs ilyen nap regisztr�lva"); return 1; }

		// ha ilyen id van m�r bejegyezve az id�pontokn�l, akkor m�r j�rtunk itt -> hiba
		$num =& $db->getOne("SELECT count(*) as num FROM Fogado WHERE fid=" . $_REQUEST['fid'] );
		if (DB::isError($num)) { die($num->getMessage()); }
		if ( $num > 0 ) { hiba ("E napon m�r vannak bejegyz�sek"); return 1; }


		// A kapott �rlap-v�ltoz�kat rendezz�k haszn�lhat� t�mb�kbe
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
			   $Tanar_copy[] = array($_REQUEST['fid'], $id, $key, $val);
			}
		}

		$sth = $db->prepare('INSERT INTO fogado VALUES (?, ?, ?, ?)');
		$res =& $db->executeMultiple($sth, $Tanar_copy);
		if (DB::isError($res)) {
			ulog (0, "SIKERTELEN ADATBEVITEL");
			die($res->getMessage());
		}
		else {
			ulog (0, "�j id�pont felvitele siker�lt." );
		}

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
