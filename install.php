<?
require('fogado.inc');
head('Fogadó admin');
$B_ora=16; $B_perc=0; $E_ora=18; $E_perc=30;

$ORA = "<select name=#NAME#>";
for ($i=8; $i<20; $i++) { $I = sprintf("%02d", $i); $ORA .= "<option value=$I>$I"; }
$ORA .= "</select>";
function ora($name, $o){
	global $ORA;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/value=$o>/", "value=$o selected>", $ORA);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

$PERC = "<select name=#NAME#>";
foreach (array('00', '10', '20', '30', '40', '50') as $i ) { $PERC .= "<option value=$i>$i"; }
$PERC .= "</select>";
function perc($name, $p){
	global $PERC;
	$o = sprintf("%02d", $o);
	$tmp = preg_replace("/value=$p>/", "value=$p selected>", $PERC);
	return preg_replace("/#NAME#/", "$name", $tmp);
}

$TARTAM = "<select name=#NAME#><option value=5>5<option value=10 selected>10"
	. "<option value=15>15<option value=20>20</select>";
function tartam($name) {
	global $TARTAM;
	return preg_replace("/#NAME#/", "$name", $TARTAM);
}

print "<h3>Fogadóórák: </h3>\n\n";

print "<form>\n<input name=datum type=text size=10 value=";
print date('Y-m-d') . "><br>\n";

$B .= "<table>\n<tr><td class=left>Kezdete: <td>" . ora("e_ora", $B_ora) . "\n"
	. "<td>" . perc("b_perc", $B_perc) . "<br>\n\n";
$E .= "<tr><td class=left>Vége: <td>" . ora("e_ora", $E_ora)
	. "<td>" . perc("e_perc", $E_perc) . "<br>\n\n";

print $B . $E;

print "<tr><td class=left>Tartam: <td>" . tartam('tartam') . "<br><td>\n\n";
print "</table>\n";
print "<input type=submit value=\" Mehet \">\n";
print "</form>\n";


// 2. ADMIN OLDAL

if( $result = pg_exec("SELECT esz, enev, o FROM (SELECT Ember.*, Osztaly.oszt AS o FROM Ember"
		. " LEFT OUTER JOIN Osztaly USING (esz)) AS Tmp WHERE oszt='t' ORDER BY enev")) {
	$Tanar = pg_fetch_all($result);
}

print "<table border=1>\n";
print "<tr><th rowspan=2>Tanár neve<th colspan=4>Fogadóóra<th colspan=3>Szülõi<th>\n";
print "<tr><th>van<th>kezdet<th>vég<th>perc<th>van<th>kezdet<th>vég\n";
foreach ($Tanar as $t) {
	$id=$t['esz'];
	print "<tr><td class=left>&nbsp;" . $t['enev'] . "\n";
	print "  <td><input type=checkbox name=a$id checked>\n";
	print "  <td>" . ora("b$id", 8) . "\n" . perc("c$id", 0) . "\n";
	print "  <td>" . ora("d$id", 18) . "\n" . perc("e$id", 30) . "\n";
	print "  <td>" . tartam("f$id") . "\n";
	if ( isset($t['o']) ) {
		print "  <td><input type=checkbox name=g$id checked>\n";
		print "  <td>" . ora("h$id", 8) . "\n" . perc("i$id", 0) . "\n";
		print "  <td>" . ora("j$id", 18) . "\n" . perc("k$id", 30) . "\n";
	}
}
print "</table>\n";

// var_dump($Tanar);

/*
?>
<form name=tabla><table border=1>
<tr><td rowspan=2><th colspan=6>15<th colspan=6>16
<tr>    <td>00<td>10<td>20<td>30<td>40<td>50<td>00<td>10<td>20<td>30<td>40<td>50

<tr><td>Alma Aladár
	<td class=tele>&nbsp;
	<td class=tele>&nbsp;
	<td class=jo><input type=checkbox name=almac checked>
	<td>&nbsp;
	<td><input type=radio name=almar value=5 checked>
	<td><input type=radio name=almar value=6>
	<td class=tele>&nbsp;
	<td class=tele>&nbsp;
	<td>&nbsp;
	<td>&nbsp;
	<td><input type=radio name=almar value=11 checked>
	<td><input type=radio name=almar value=12>
	<td><input type=button value=x onClick='torol("almar")'>

<tr><td>Béta Boróka
	<td><input type=radio name=betar value=1>
	<td class=tele>&nbsp;
	<td><input type=radio name=betar value=3>
	<td>&nbsp;
	<td>&nbsp;
	<td><input type=radio name=betar value=6>
	<td><input type=radio name=betar value=7>
	<td class=tele>&nbsp;
	<td><input type=radio name=betar value=9>
	<td>&nbsp;
	<td>&nbsp;
	<td><input type=radio name=betar value=12>
	<td><input type=button value=x onClick='torol("betar")'>

<tr><td>Cérna Géza
	<td class=tele>&nbsp;
	<td class=tele>&nbsp;
	<td class=tele>&nbsp;
	<td>&nbsp;
	<td><input type=radio name=cernar value=5 checked>
	<td><input type=radio name=cernar value=6>
	<td class=tele>&nbsp;
	<td class=tele>&nbsp;
	<td class=jo><input type=checkbox name=cernac checked>
	<td>&nbsp;
	<td><input type=radio name=cernar value=11 checked>
	<td><input type=radio name=cernar value=12>
	<td><input type=button value=x onClick='torol("cernar")'>

</table>
<input type=submit value=" Mehet ">
</form>
*/

?>
<p><hr><address><a href="mailto:dugo@szepi.hu">dugo@szepi.hu</a></address>
</body>
</html>
