<?
$db = pg_connect("dbname=iskola user=szaszi") or die("Nem mén a kapcsolat, hö!");

if (isset($VAR_id)) {
	if ( $result = pg_exec("SELECT O.esz AS ofo, O.enev AS ofonev, E.*"
			. " FROM Osztaly_view AS O, Ember AS E"
			. " WHERE O.oszt=E.oszt AND E.tip='d' AND E.esz=" . $VAR_id)) {
		$USER = pg_fetch_array($result);
	}
} else {
	$USER['id'] = 300;
	$USER['enev'] = 'Eleki Gergely';
}
$USER_nev = $USER['enev'];

print <<< EnD
<html>
<head>
  <title>Fogadóóra - $USER_nev </title>
  <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
  <link rel="stylesheet" href="default.css" type="text/css">
  <style type="text/css">

    td { text-align: center; }
    td.sajat { background-color: red; }
    td.foglalt { background-color: #D0E0E0; }
    td.szuloi { background-color: yellow; }
  </style>
  <script language=JavaScript><!--
    function torol(sor) {
    eval('var s = document.tabla.'+sor);
    for (var i=0; i<s.length; i++)
      s[i].checked=0;
    }
  //--></script>
</head>

<body>
EnD;
print "\n<h3>" . $USER['enev'] . "<br>\n";
print "<font size=-1>(Osztályfőnök: " . $USER['ofonev'] . ")</h3>\n";

function td_ki($i, $VAL, $rows, $class) {
	global $TANAR;
	$j=$i+1;
	while ($TANAR[$j]['diak']==$VAL && $j<=$rows) $j++;
	$td = "  <td class=" . $class;
	if ( $j>$i+1 ) $td .= " colspan=" . ($j-$i);
	$td .= ">";
	return array('j' => $j, 'td' => $td);
}

function tanar_ki($tanar) {
	global $IDO_min, $IDO_max, $USER, $TANAR;
	print "\n<tr><th>&nbsp;" . $tanar['enev'] . " \n";
	if( $result = pg_exec("SELECT diak FROM Fogado WHERE tanar=" . $tanar['tanar']
			. "AND ido BETWEEN '$IDO_min' AND '$IDO_max' ORDER BY ido")) {
		$TANAR = pg_fetch_all($result);
		// TANAR: [0]['diak']=25, [1]['diak']=-1, ...
		$rows = pg_numrows($result);

		$i=0;
		while ($i<$rows) {
			switch($TANAR[$i]['diak']) {
				case -2: // csak a saját szülőit kell kiírni -- egyébként foglalt
					$res = td_ki($i, -2, $rows, ($USER['ofo'] == $tanar['tanar'])?'szuloi':'foglalt');
					print $res['td'] . "&nbsp;";
					break;

				case NULL:
					$res = td_ki($i, NULL, $rows, 'foglalt');
					print $res['td'] . "&nbsp;";
					break;

				case 0:
					$res = td_ki($i, -1, $rows, 'szabad');
					print $res['td'] . "<input type=radio name=t" . $tanar['tanar'] . "r value=" . $i . ">";
					break;

				default:
					if ( $TANAR[$i]['diak'] == $USER['esz'] ) { // bejelentkezett azonosító
						$res = td_ki($i, -1, $rows, 'sajat');
						print $res['td'] . "<input type=checkbox name=t" . $tanar['tanar'] . "c checked>";
					} else { // másik diák, olyan mintha nem lenne itt
						$res = td_ki($i, -1, $rows, 'foglalt');
						print $res['td'] . "&nbsp;";
					}
					break;

			}
			$i=$res['j'];
			print "\n";
		}
		print "  <td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";

	}
}

// A minimális és maximális kiírandó idő kiválasztása:
$sor = pg_fetch_array(pg_exec("SELECT min(ido) FROM Fogado WHERE diak IS NOT NULL"));
$IDO_min = $sor[0];
$IDO_min_array = explode (':', $IDO_min);
$sor = pg_fetch_array(pg_exec("SELECT max(ido) FROM Fogado WHERE diak IS NOT NULL"));
$IDO_max = $sor[0];
$IDO_max_array = explode (':', $IDO_max);

$IDO_min_d = 6*$IDO_min_array[0]+floor($IDO_min_array[1]/6);
$IDO_max_d = 6*$IDO_max_array[0]+floor($IDO_max_array[1]/6);

for ($ido=$IDO_min_d; $ido<=$IDO_max_d; $ido++) {
	$ora = floor($ido/6);
	if (!isset($IDO[$ora]))
		$IDO[$ora] = array();
	array_push ($IDO[$ora], $ido % 6);
}

$A = "\n<tr><td rowspan=2>";
$B = "\n<tr>";
foreach (array_keys($IDO) as $ora) {
	$A .= "<th colspan=" . count ($IDO[$ora]) . ">" . $ora;
	foreach (array_values($IDO[$ora]) as $perc )
		$B .= "<td>" . $perc . "0";
}

// print "Időtartam: " . $IDO_min . " -- " . $IDO_max . "<br>\n";

// Vesszük a tanarakat sorban:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$FOGADO_orig = pg_fetch_all($result);
}

// Külön kéne nézni az egész és az öt perceseket
// SELECT count(*) FROM Fogado WHERE ido LIKE '__:_5:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;
// ha nem 0 -- azaz van 5 perces, akkor külön kell kezelni.
// SELECT to_char(ido, 'HH24:MI') FROM Fogado WHERE ido LIKE '__:_0:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;

print "\n<form name=tabla><table border=1>";
print $A . $B;
reset ( $FOGADO_orig );
array_walk ( $FOGADO_orig, 'tanar_ki' );
print "</table>\n\n";
print "<input type=hidden name=id value=" . $USER['esz'] . ">\n";
print "<input type=submit name=submit value=' Mehet '>\n";
print "</form>\n";


pg_close ($db);
print "\n</body>\n";
print "</html>\n";
return(0);

?>

