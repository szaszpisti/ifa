<?
require('fogado.inc');

include("tanar.class");
$TANAR = new Tanar();

$QUERY_LOG = array();
$USER_LOG = array();

reset($_POST);
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
$TANAR = new Tanar();

Head("Fogadóóra - " . $TANAR->tnev);
print "\n<h3>" . $TANAR->tnev .  " (" . $FA->datum . ")</h3>\n";

# var_dump ($TANAR);
$TABLA = "<table border=0><tr><td>\n" # A külsõ táblázat a jelmagyarázatnak
	. "<form method=post>\n<table border=1>\n"
	. "<tr><th><th>A<th>B<th>C<th>D<th>E\n";
# Ez akkor kell, ha az ötperceseket opcionálisra szeretném
# for ($Time=$TANAR->IDO_min-($TANAR->IDO_min%2); $Time<=$TANAR->IDO_max; $Time+=2-$TANAR->ODD) {
for ($Time=$TANAR->IDO_min; $Time<=$TANAR->IDO_max; $Time++) {
	$did = $TANAR->diak[$Time];
	$TABLA .= "<tr><td>" . sprintf("%02d:%02d\n", floor($Time/12), ($Time%12)*5);
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
	. "  <input type=hidden name=tip value=mod>\n"
	. "  <input type=submit value=' Mehet '>\n"
	. "</table>\n"
	. "</form>\n"
	. "<td>&nbsp;<td valign=top><br><b>Jelmagyarázat:</b><ul>\n"
	. "   A: nincs itt<br>\n"
	. "   B: fogadó idõpont kezdete<br>\n"
	. "   C: - idõpont folytatása<br>\n"
	. "   D: szülõi értekezlet<br>\n"
	. "   E: már bejelentkezett diák\n"
	. "</ul></table>";

print $TABLA;

if (ADMIN) {
	foreach ($QUERY_LOG as $log) print "<b>$log</b><br>\n";
} else {
	foreach ($USER_LOG as $log) print "<b>$log</b><br>\n";
}

pg_close ($db);
Tail();

?>

