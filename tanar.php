<?
require('fogado.inc');

include("tanar.class");
$TANAR = new Tanar();

switch ($_REQUEST['mod']) {
	# az egyes id�pontok m�dos�t�sa
	case 1:
		reset($_POST);
		pg_query("BEGIN TRANSACTION");
		while (list($key, $diak) = each($_POST)) {
			if ( ereg ("^r([0-9]+)$", $key, $match) ) {
				unset($q);
				$ido = $match[1];
				if (isset($TANAR->diak[$ido])) {
					if ($diak=="x") $q = "DELETE FROM Fogado WHERE fid=".fid." AND tanar=".$TANAR->id." AND ido=".$ido;
					elseif ($diak != $TANAR->diak[$ido])
						$q = "UPDATE Fogado SET diak=".$diak." WHERE fid=".fid." AND tanar=".$TANAR->id." AND ido=".$ido;
				}
				else {
					if ($diak != "x") $q = "INSERT INTO Fogado VALUES (".fid.", ".$TANAR->id.", ".$ido.", ".$diak.")";
				}
				if (isset($q)) {
					pg_query($q);
					ulog(0, $q);
				}
			}
		}
		pg_query("END TRANSACTION");
		break;

	# az intervallum b�v�t�se
	case 2:
		$UJ_min = TimeToFive($_REQUEST['kora'], $_REQUEST['kperc']);
		$UJ_max = TimeToFive($_REQUEST['vora'], $_REQUEST['vperc']);
		pg_query("BEGIN TRANSACTION");
		if ($TANAR->fogad) {
			for ($ido = $UJ_min; $ido < $TANAR->IDO_min; $ido++) {
				pg_query ("INSERT INTO Fogado VALUES (".fid.", ".$TANAR->id.", ".$ido.", 0)");
				$BOVIT .= " $ido";
			}
			for ($ido = $TANAR->IDO_max+1; $ido < $UJ_max; $ido++) {
				pg_query ("INSERT INTO Fogado VALUES (".fid.", ".$TANAR->id.", ".$ido.", 0)");
				$BOVIT .= " $ido";
			}
		}
		else { // m�g nem volt fogad��r�ja bejegyezve
			for ($ido = $UJ_min; $ido < $UJ_max; $ido++) {
				pg_query ("INSERT INTO Fogado VALUES (".fid.", ".$TANAR->id.", ".$ido.", 0)");
				$BOVIT .= " $ido";
			}
		}
		pg_query("END TRANSACTION");
		ulog(0, $TANAR->tnev." b�v�t�s: ".$BOVIT);
		break;
}

$TANAR = new Tanar(); # �jra beolvassuk az adatb�zisb�l

Head("Fogad��ra - " . $TANAR->tnev);
print "\n<h3>" . $TANAR->tnev .  " (" . $FA->datum . ")</h3>\n";

# A k�ls� t�bl�zat els� cell�j�ban az id�pont-lista
$TABLA = "<table border=0><tr><td>\n";

if ($TANAR->fogad) {
	$TABLA .= "<form method=post name=tabla>\n<table border=1>\n"
		. "<tr><th><th>A<th>B<th>C<th>D<th>E\n"
		. "    <td colspan=2 align=right><input type=hidden name=mod value=1>\n"
		. "       <input type=reset value='RESET'>\n"
		. "       <input type=submit value=' Mehet '>\n";
	for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
		$TABLA .= ($ido%2?"<tr bgcolor=#eaeaea>":"<tr>");
		$diak = $TANAR->diak[$ido];
		$TABLA .= "<td>" . FiveToString($ido);
		$TABLA .= "  <td class=foglalt><input type=radio name=r$ido value=x" . (!isset($diak)?" checked":"") . ">\n";
		$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=0" . ($diak=="0"?" checked":"") . ">\n";
		$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=-1" . ($diak=="-1"?" checked":"") . ">\n";
		$TABLA .= "  <td class=szuloi><input type=radio name=r$ido value=-2" . ($diak=="-2"?" checked":"") . ">\n";
		if ($diak>0) {
			$TABLA .= "  <td class=sajat><input type=radio name=r$ido value=$diak checked><td><a class=diak href=fogado.php?"
				. "id=" . $diak . ">" . $TANAR->dnev[$ido] . "</a>\n";
		} else {
			$TABLA .= "  <td colspan=2>&nbsp;\n";
		}
	}
	$TABLA .= "<tr><td colspan=7 align=right><input type=hidden name=mod value=1>\n"
		. "       <input type=reset value='RESET'>\n"
		. "       <input type=submit value=' Mehet '>\n"
		. "</form>\n"
		. "</table>\n"
# A k�ls� t�bl�zat m�sodik cell�ja
		. "<td>&nbsp;\n"
		. "<td valign=top>\n";
}

$TABLA .= "<br><b>Jelmagyar�zat:</b><ul>\n"
	. "   A: nincs itt<br>\n"
	. "   B: fogad� id�pont kezdete<br>\n"
	. "   C: - id�pont folytat�sa<br>\n"
	. "   D: sz�l�i �rtekezlet<br>\n"
	. "   E: m�r bejelentkezett di�k\n"
	. "</ul>\n"
	. "<script language=JavaScript><!--\n"
	. "function fivedel() {\n"
	. "  for (var i=0; i<document.tabla.length; i++) {\n"
	. "    o = document.tabla.elements[i]; // az �rlap elemeit veszi sorra\n"
	. "    if (o.value == '-1') {          // ha �ppen '-1'-es gombn�l tartunk\n"
	. "      ido = parseInt(o.name.substr(1,10));\n"
	. "      if (o.checked) eval ('document.tabla.' + o.name + '[1].checked = 1');\n"
	. "    }\n"
	. "  }\n"
	. "}\n"
	. "//--></script>\n"
	. "<form method=post>\n"
	. "  <input type=hidden name=mod value=2>\n";

if ($TANAR->fogad) {
	$TABLA .= "<p class=center>B�v�t�s: " . SelectOra("kora", $TANAR->Kezdo['ora']) . SelectPerc("kperc", $TANAR->Kezdo['perc']) . " - \n"
		. SelectOra("vora", $TANAR->Veg['ora']) . SelectPerc("vperc", $TANAR->Veg['perc']) . "\n"
		. "  <input type=submit value=' GO '></p><br><br>\n"
		. "</form>\n"
		. "  <p class=center>Ha az 5 percekben is fogadni akar:<br>\n"
		. "<input type=button value=' Hajr� sz�l�k! ' onClick='fivedel()'><br>\n"
		. "  (Ut�na m�g kell a ,,Mehet''!)</p>\n"
		. "</table>\n";
}
else {
	$Kezdo = FiveToTime($FA->kezd);
	$Veg   = FiveToTime($FA->veg);
	$TABLA .= "  Id�: " . SelectOra("kora", $Kezdo['ora']) . SelectPerc("kperc", $Kezdo['perc']) . " - \n"
		. SelectOra("vora", $Veg['ora']) . SelectPerc("vperc", $Veg['perc']) . "\n"
		. "  <input type=submit value=' GO '><br>\n"
		. "</form>\n"
		. "</table>\n";
}

print $TABLA;

pg_close ($db);
Tail();

?>

