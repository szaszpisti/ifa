<?
/*
 *   Ez a f�jl az IFA (Iskolai Fogad��ra Adminisztr�ci�) csomag r�sze,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Sz�sz Imre.
 *
 *   Ez egy szabad szoftver; terjeszthet� illetve m�dos�that� a GNU
 *   �ltal�nos K�zread�si Felt�telek dokumentum�ban le�rtak -- 2. vagy
 *   k�s�bbi verzi� -- szerint, melyet a Szabad Szoftver Alap�tv�ny ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

require_once('login.php');
require_once('fogado.inc.php');
require_once('tanar.class.php');

$TANAR = new Tanar($_REQUEST['id']);

switch ($_REQUEST['mod']) {
	# az egyes id�pontok m�dos�t�sa
	case 1:
		reset($_POST);
		while (list($key, $diak) = each($_POST)) {
			if ( ereg ("^r([0-9]+)$", $key, $match) ) {
				unset($q);
				$ido = $match[1];
				if (isset($TANAR->fogado_ido[$ido][diak])) {
					if ($diak=="x") $q = "DELETE FROM Fogado WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
					elseif ($diak != $TANAR->fogado_ido[$ido][diak])
						$q = "UPDATE Fogado SET diak=" . $diak . " WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
				}
				else {
					if ($diak != "x") $q = "INSERT INTO Fogado VALUES (" . fid . ", " . $TANAR->id . ", " . $ido . ", " . $diak . ")";
				}
				if (isset($q)) {
					$db->query($q);
					ulog(0, $q);
				}
			}
		}
		break;

	# az intervallum b�v�t�se
	case 2:
		$UJ_min = $_REQUEST['kora'] +  $_REQUEST['kperc'];
		$UJ_max = $_REQUEST['vora'] +  $_REQUEST['vperc'];
		$tartam = $_REQUEST['tartam'];
		unset ($INSERT);

		while ($UJ_min%$tartam) $UJ_min++;

		/* Ha m�r van bejegyzett id�pontja, akkor a b�v�t�s az ez el�tti
		   �s az ez ut�ni id�kre vonatkozik */

		if ($TANAR->fogad) {
			for ($ido = $UJ_min; $ido < $TANAR->IDO_min; $ido++ ) {
				$INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
			}
			for ($ido = $TANAR->IDO_max+1; $ido < $UJ_max; $ido++) {
				$INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
			}
		}
		else { // m�g nem volt fogad��r�ja bejegyezve
			for ($ido = $UJ_min; $ido < $UJ_max; $ido++) {
				$INSERT[] = array(fid, $TANAR->id, $ido, ($ido%$tartam?-1:0));
			}
		}

		$sth = $db->prepare('INSERT INTO fogado VALUES (?, ?, ?, ?)');
		$res =& $db->executeMultiple($sth, $INSERT);

		if (DB::isError($res)) {
			ulog (0, "SIKERTELEN B�V�T�S: " . $TANAR->tnev . "($UJ_min -> $UJ_max)" );
			die($res->getMessage());
		}
		else {
			ulog (0, $TANAR->tnev . " b�v�t�s: $UJ_min -> $UJ_max ($tartam)" );
		}

		break;
}

$TANAR = new Tanar($_REQUEST['id']); # �jra beolvassuk az adatb�zisb�l

Head("Fogad��ra - " . $TANAR->tnev);

echo "\n<table width=\"100%\"><tr>\n"
	. "<td><h3>" . $TANAR->tnev .  " (" . $FA->datum . ")</h3>\n"
	. "<td align=right><a href='" . $_SERVER['PHP_SELF'] . "?id=" . $TANAR->id . "&amp;kilep='> Kil�p�s </a>\n</table>\n";

# A k�ls� t�bl�zat els� cell�j�ban az id�pont-lista
$TABLA = "<table border=0><tr><td>\n";

if (ADMIN) {
	if ($TANAR->fogad) {
		$TABLA .= "<form action=\"\" method=post name=tabla>\n<table border=1>\n"
			. "<tr><th><th>A<th>B<th>C<th>D<th>E\n"
			. "    <td colspan=2 align=right><input type=hidden name=mod value=1>\n"
			. "       <input type=reset value='RESET'>\n"
			. "       <input type=submit value=' Mehet '>\n";
		for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
			$TABLA .= ($ido%2?"<tr class=paratlan>":"<tr>");
			$diak = $TANAR->fogado_ido[$ido][diak];
			$TABLA .= "<td>" . FiveToString($ido);
			$TABLA .= "  <td class=foglalt><input type=radio name=r$ido value=x" . (!isset($diak)?" checked":"") . ">\n";
			$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=0" . ($diak=="0"?" checked":"") . ">\n";
			$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=-1" . ($diak=="-1"?" checked":"") . ">\n";
			$TABLA .= "  <td class=szuloi><input type=radio name=r$ido value=-2" . ($diak=="-2"?" checked":"") . ">\n";
			if ($diak>0) {
				$TABLA .= "  <td class=sajat><input type=radio name=r$ido value=$diak checked><td><a class=diak href=\"fogado.php?"
					. "tip=diak&amp;id=" . $diak . "\">" . $TANAR->fogado_ido[$ido][dnev] . "</a>\n";
			} else {
				$TABLA .= "  <td colspan=2>&nbsp;\n";
			}
		}
		$TABLA .= "<tr><td colspan=7 align=right><input type=hidden name=mod value=1>\n"
			. "       <input type=reset value='RESET'>\n"
			. "       <input type=submit value=' Mehet '>\n"
			. "</table>\n"
			. "</form>\n"
	# A k�ls� t�bl�zat m�sodik cell�ja
			. "<td>&nbsp;\n"
			. "<td valign=top>\n";
	}

	$TABLA .= "<br><b>Jelmagyar�zat:</b><ul>\n"
		. "   <li>A: nincs itt<br>\n"
		. "   <li>B: fogad� id�pont kezdete<br>\n"
		. "   <li>C: - id�pont folytat�sa<br>\n"
		. "   <li>D: sz�l�i �rtekezlet<br>\n"
		. "   <li>E: m�r bejelentkezett di�k\n"
		. "</ul>\n"
		. "<script language=JavaScript type=\"text/javascript\"><!--\n"
		. "function fivedel() {\n"
		. "  for (var i=0; i<document.tabla.length; i++) {\n"
		. "    o = document.tabla.elements[i]; // az �rlap elemeit veszi sorra\n"
		. "    if (o.value == '-1') {          // ha �ppen '-1'-es gombn�l tartunk\n"
		. "      ido = parseInt(o.name.substr(1,10));\n"
		. "      if (o.checked) eval ('document.tabla.' + o.name + '[1].checked = 1');\n"
		. "    }\n"
		. "  }\n"
		. "}\n"
		. "function nincs() {\n"
		. "  for (var i=0; i<document.tabla.length; i++) {\n"
		. "    o = document.tabla.elements[i]; // az �rlap elemeit veszi sorra\n"
		. "    if (o.value == 'x') o.checked = true;\n"
		. "  }\n"
		. "}\n"
		. "//--></script>\n"
		. "<form action=\"\" method=post>\n"
		. "  <input type=hidden name=mod value=2>\n";

	if ($TANAR->fogad) {
		$TABLA .= "<p>B�v�t�s: "
			. SelectIdo("kora", "kperc", $TANAR->IDO_min) . " - \n"
			. SelectIdo("vora", "vperc", $TANAR->IDO_max) . "\n &nbsp; &nbsp;"
			. SelectTartam('tartam') . "\n"
			. "  <input type=submit value=' Uccu! '></p><br><br>\n"
			. "</form>\n"
			. "<p class=elso><i>Gombok gyors �ll�t�sa:</i>\n<ul>\n"
			. "  <li>Ha m�gsem fog fogadni (�sszes -> A):\n"
			. "      <br> &nbsp; &nbsp; <input type=button value=' Megjel�l ' onClick='nincs()'>\n"
			. "  <li>Ha az 5 percekben is fogadni akar (�sszes: C -> B):\n"
			. "      <br> &nbsp; &nbsp; <input type=button value=' Megjel�l ' onClick='fivedel()'>\n"
			. "  <br>(Ezek ut�n m�g kell a ,,Mehet'' gomb!)\n</ul>\n\n"
			. "</table>\n";
	}
	else {
		$TABLA .= "  Fogad: "
			. SelectIdo("kora", "kperc", $FA->IDO_min) . " - \n"
			. SelectIdo("vora", "vperc", $FA->IDO_max) . "\n &nbsp; &nbsp;"
			. SelectTartam('tartam') . "\n"
			. "  <input type=submit value=' Uccu! '><br>\n"
			. "</form>\n"
			. "</table>\n";
	}

} else {
$elso = floor((($TANAR->IDO_min)+1)/2)*2;
#print_r($TANAR);
	$elozo = 0;
#	for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido+=(2-$TANAR->ODD)) {
	for ($ido = $elso; $ido<$TANAR->IDO_max; $ido+=(2-$TANAR->ODD)) {
		$ora = floor($ido/12);
		if ($ora != $elozo) { $elozo = $ora; $TABLA .= "<tr><td colspan=3><hr>\n"; }
		$TABLA .= ($ido%2?"<tr class=paratlan>":"<tr>");
		$diak = $TANAR->fogado_ido[$ido][diak];
		$TABLA .= "<td" . ($diak=="-2"?" class=szuloi":"") . ">" . FiveToString($ido)
			. "<td> -- <td>" . ($diak>0?$TANAR->fogado_ido[$ido][dnev]:"&nbsp;") . "\n";
	}
	$TABLA .= "</table>\n";

}

print $TABLA;

Tail();

?>

