<?
require('fogado.inc');

include("tanar.class");
$TANAR = new Tanar();

reset($_POST);
switch ($_REQUEST['mod']) {
	case 1:
		while (list($k, $v) = each($_POST)) {
			pg_query("BEGIN TRANSACTION");
			if ($v == "x") $v = "";
			if ( ereg ("^r([0-9]+)$", $k, $match) ) {
				$ido = $match[1];
				$diak = $TANAR->diak[$ido];
				if ($diak != $v) {
					if ($v=="") $v="NULL";
					$q = "UPDATE Fogado SET diak=".$v." WHERE fid=".$FA->id." AND tanar=".$TANAR->id." AND ido=".$ido;
					pg_query($q);
					ulog(0, $q);
				}
			}
			pg_query("END TRANSACTION");
		}
		break;
	case 2:
		for ($ido = $_REQUEST['kora']*12+$_REQUEST['kperc']*2; $ido < $TANAR->IDO_min; $ido++) {
			pg_query ("INSERT INTO Fogado VALUES (".$FA->id.", ".$TANAR->id.", ".$ido.", 0)");
		}
		for ($ido = $TANAR->IDO_max+1; $ido < $_REQUEST['vora']*12+$_REQUEST['vperc']*2; $ido++) {
			pg_query ("INSERT INTO Fogado VALUES (".$FA->id.", ".$TANAR->id.", ".$ido.", 0)");
		}
		break;
}

$TANAR = new Tanar(); # újra beolvassuk az adatbázisból

Head("Fogadóóra - " . $TANAR->tnev);
print "\n<h3>" . $TANAR->tnev .  " (" . $FA->datum . ")</h3>\n";

# var_dump ($TANAR);
$TABLA = "<table border=0><tr><td>\n" # A külsõ táblázat a jelmagyarázatnak
	. "<form method=post name=tabla>\n<table border=1>\n"
	. "<tr><th><th>A<th>B<th>C<th>D<th>E<td align=right><input type=submit value=' Mehet '>\n";
# Ez akkor kell, ha az ötperceseket opcionálisra szeretném
# for ($Time=$TANAR->IDO_min-($TANAR->IDO_min%2); $Time<=$TANAR->IDO_max; $Time+=2-$TANAR->ODD) {
for ($Time=$TANAR->IDO_min; $Time<=$TANAR->IDO_max; $Time++) {
	$TABLA .= ($Time%2?"<tr bgcolor=#eaeaea>":"<tr>");
	$did = $TANAR->diak[$Time];
	$TABLA .= "<td>" . sprintf("%02d:%02d\n", floor($Time/12), ($Time%12)*5);
	$TABLA .= "  <td class=foglalt><input type=radio name=r$Time value=x" . ($did===NULL?" checked":"") . ">\n";
	$TABLA .= "  <td class=szabad><input type=radio name=r$Time value=0" . ($did=="0"?" checked":"") . ">\n";
	$TABLA .= "  <td class=szabad><input type=radio name=r$Time value=-1" . ($did=="-1"?" checked":"") . ">\n";
	$TABLA .= "  <td class=szuloi><input type=radio name=r$Time value=-2" . ($did=="-2"?" checked":"") . ">\n";
	if ($did>0) {
		$TABLA .= "  <td class=sajat><input type=radio name=r$Time value=$did checked><td><a class=diak href=fogado.php?"
			. "id=" . $did . ">" . $TANAR->dnev[$Time] . "</a>\n";
	} else {
		$TABLA .= "  <td colspan=2>&nbsp;\n";
	}
}

$TABLA .= "<tr><td colspan=7 align=right class=right>\n"
	. "  <input type=hidden name=mod value=1>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. "</table>\n"
	. "</form>\n"
	. "<td>&nbsp;<td valign=top><br><b>Jelmagyarázat:</b><ul>\n"
	. "   A: nincs itt<br>\n"
	. "   B: fogadó idõpont kezdete<br>\n"
	. "   C: - idõpont folytatása<br>\n"
	. "   D: szülõi értekezlet<br>\n"
	. "   E: már bejelentkezett diák\n"
	. "</ul>\n"
	. "<script language=JavaScript><!--\n"
	. "function fivedel() {\n"
	. "  for (var i=0; i<document.tabla.length; i++) {\n"
	. "    o = document.tabla.elements[i]; // az ûrlap elemeit veszi sorra\n"
	. "    if (o.value == '-1') {          // ha éppen '-1'-es gombnál tartunk\n"
	. "      ido = parseInt(o.name.substr(1,10));\n"
	. "      if (o.checked) eval ('document.tabla.' + o.name + '[1].checked = 1');\n"
	. "    }\n"
	. "  }\n"
	. "}\n"
	. "//--></script>\n"
	. "<form method=post>\n"
	. "  <input type=hidden name=mod value=2>\n"
	. "  Idõ: " . ora("kora", $TANAR->Kezdo['ora']) . perc("kperc", $TANAR->Kezdo['perc']) . " - \n"
	. ora("vora", $TANAR->Veg['ora']) . perc("vperc", $TANAR->Veg['perc']) . "\n"
	. "  <input type=submit value=' GO '><br>\n"
	. "</form>\n"
	. "  NO 5 perc: <input type=button value=' Hajrá szülõk! ' onClick='fivedel()'>\n"
	. "</table>\n";

print $TABLA;

pg_close ($db);
Tail();

?>

