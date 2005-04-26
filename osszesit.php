<?
require_once('login.php');
require_once('fogado.inc.php');
require_once('diak.class.php');

$USER = new Diak($_SESSION['id']);

Head("Fogadóóra - " . $USER->dnev);

print "\n<h3>Fogadóóra: " . $FA->datum . "<br>\n"
	. $USER->dnev . " " . $USER->onev . "<br>\n"
	. "<font size=-1>(Osztályfõnök: " . $USER->ofonev . ")</h3>\n";

$szuloi =& $db->getRow(
			  "SELECT MIN(ido) AS eleje, MAX(ido) AS vege"
			. "  FROM Fogado"
			. "    WHERE fid=" . fid
			. "      AND tanar=" . $USER->ofo
			. "      AND diak=-2",
			array(), DB_FETCHMODE_ASSOC);

if (DB::isError($data)) {
	die($data->getMessage());
}

if ($szuloi['eleje']) {
	$SzuloiSor = "<br><b>" . FiveToString($szuloi['eleje'])
		. "-" . FiveToString($szuloi['vege']+1)
		. " -- szülõi értekezlet</b>\n";
	$SzuloiEleje = $szuloi['eleje'];
}

$res =& $db->query(
			  "SELECT ido, tnev"
			. "  FROM Fogado, Tanar"
			. "    WHERE fid=" . fid
			. "      AND Tanar.id=tanar"
			. "      AND diak=" . $USER->id
			. "        ORDER BY ido");

while ($res->fetchInto($row)) {
	if ($SzuloiEleje < $row['ido']) {
		$Output .= $SzuloiSor;
		$SzuloiSor = "";
	}
	$Output .= "<br>" . FiveToString($row['ido']) . " -- " . $row['tnev'] . "\n";
}
$Output .= $SzuloiSor;

print $Output;

Tail();

?>

