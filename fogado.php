<?
if (isset($VAR_id)) $USER_id = $VAR_id;
else $USER_id = 23;
$USER_oszt = 'd04c';

//print "<html><head></head>\n\n<body>\n";
// phpinfo();
// $OLDAL = 8; // hány oldalon szerepelhetnek hírek
// $OCIM = array ('Nyitólap', 'Piaristák', 'Iskola', 'Diákság', 'Üzleti képzés', 'Templom', 'Irodalom', 'Kapcsolat');
// $TIP = array ('mod' => ' Módosít ', 'uj' => ' Új hír ', 'fel' => ' Felvitel ');

print <<< EnD
<html>
<head>
  <title>Fogadóóra admin</title>
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

$db = pg_connect("dbname=iskola user=szaszi") or die("Nem mén a kapcsolat, hö!");

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
	global $IDO_min, $IDO_max, $USER_id, $TANAR;
	print "<tr><th>" . $tanar['enev'] . "\n";
	if( $result = pg_exec("SELECT diak FROM Fogado WHERE tanar=" . $tanar['tanar']
			. "AND ido BETWEEN '$IDO_min' AND '$IDO_max' ORDER BY ido")) {
		$TANAR = pg_fetch_all($result);
		// TANAR: [0]['diak']=25, [1]['diak']=-1, ...
		$rows = pg_numrows($result);
		$i=0;

		while ($i<$rows) {
			$j=$i;
			switch($TANAR[$i]['diak']) {
				case -2: // csak a saját szülőit kellene kiírni -- egyébként foglalt
					$res = td_ki($i, -2, $rows, 'szuloi');
					print $res['td'] . "&nbsp;";
				/*
					print "  <td class=szuloi";
					while ($TANAR[$j]['diak']==-2 && $j<=$rows) $j++;
					if ( $j>$i+1 ) print " colspan=" . ($j-$i);
					print ">&nbsp;";
				*/
					break;

				case NULL:
					$res = td_ki($i, NULL, $rows, 'foglalt');
					print $res['td'] . "&nbsp;";
					/*
					print "  <td class=foglalt";
					while ($TANAR[$j]['diak']==NULL && $j<=$rows) $j++;
					if ( $j>$i+1 ) print " colspan=" . ($j-$i);
					print ">&nbsp;"; */
					break;

				case 0:
					$res = td_ki($i, -1, $rows, 'szabad');
					print $res['td'] . "<input type=radio name=t" . $tanar['tanar'] . "r value=" . $i . ">";
					/*
					print "  <td class=szabad";
					$j++;
					while ($TANAR[$j]['diak']==-1 && $j<=$rows) $j++;
					if ( $j>$i+1 ) print " colspan=" . ($j-$i);
					print "><input type=radio name=t" . $tanar['tanar'] . "r value=" . $i . ">";
					*/
					break;

				default:
					if ( $TANAR[$i]['diak'] == $USER_id ) { // bejelentkezett azonosító
						$res = td_ki($i, -1, $rows, 'sajat');
						print $res['td'] . "<input type=checkbox name=t" . $tanar['tanar'] . "c checked>";
						/*
						print "  <td class=sajat";
						$j++;
						while ($TANAR[$j]['diak']==-1 && $j<=$rows) $j++;
						if ( $j>$i+1 ) print " colspan=" . ($j-$i);
						print "><input type=checkbox name=t" . $tanar['tanar'] . "c checked>";
						*/
					} else { // másik diák, olyan mintha nem lenne itt
						$res = td_ki($i, -1, $rows, 'foglalt');
						print $res['td'] . "&nbsp;";
						/*
						print "  <td class=foglalt";
						$j++;
						while ($TANAR[$j]['diak']==-1 && $j<=$rows) $j++;
						if ( $j>$i+1 ) print " colspan=" . ($j-$i);
						print ">&nbsp;";
						*/
					}
					break;
			} // switch
			$i=$res['j'];
			print "\n";
		} // while
		print "<td><input type=hidden name=id value=" . $USER_id . ">\n";
		print "<td><input type=button value=x onClick='torol(\"t" . $tanar['tanar'] . "r\")'>\n";

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

print "Időtartam: " . $IDO_min . " -- " . $IDO_max . "<br>\n";

// Vesszük a tanarakat sorban:
if( $result = pg_exec("SELECT tanar,enev FROM Fogado,Ember WHERE tip='t' AND tanar=esz GROUP BY tanar,enev ORDER BY enev")) {
	$FOGADO_orig = pg_fetch_all($result);
}

// Külön kéne nézni az egész és az öt perceseket
// SELECT count(*) FROM Fogado WHERE ido LIKE '__:_5:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;
// ha nem 0 -- azaz van 5 perces, akkor külön kell kezelni.

// SELECT to_char(ido, 'HH24:MI') FROM Fogado WHERE ido LIKE '__:_0:__' AND diak IS NOT NULL AND NOT diak=-1 ORDER BY ido;
//var_dump($IDO);
print "<form name=tabla><table border=1>\n";
print $A . $B;
reset ( $FOGADO_orig );
array_walk ( $FOGADO_orig, 'tanar_ki' );
print "</table>\n";
print "<input type=submit value=' Mehet '>\n";
print "</form>\n";

	/*
	var_dump( $sor[0] );
	print ($sor[0]['tanar']);
//	tanar_ki(34);
	$rows = pg_numrows($result);
	for($i=0; $i<$rows; $i++) {
		$sor = pg_fetch_array($result, $i);
		print $sor['tanar'] . ": " . $sor['enev'] . "<br>\n";
		tanar_ki($sor['tanar']);
	}
}
	*/

print "<h1>== VÉGE ==</h1>\n";
pg_close ($db);

print "</body>\n";
print "</html>\n";
return(0);

?>
function hir_ki($hir) {
	global $OLDAL, $OCIM, $TIP, $tip;
	print "<form>\n<input type=radio name=m value=hir onChange='Betolt(\"m=hir\")' checked> Hír\n";
	print "<input type=radio name=m value=hely onChange='Betolt(\"m=hely\")'> Hely\n</form>\n";
	if( $result = pg_exec("SELECT * FROM Hir ORDER BY id")) {
		$rows = pg_numrows($result);
			print ("<form>\n");
			print "<input type=submit name=tip value=\"".$TIP['uj']."\"><br><hr align=left width=30%>\n";
			if($rows){
				print "<select name=id onChange='Betolt(\"id=\"+this.value+\"&m=hir\")'>\n";
				for($i=0; $i<$rows; $i++) {
					$sor = pg_fetch_array($result, $i);
					print "<option value=".$sor['id'];
					if($i == $hir['id']-1) print " selected";
					print "> ".$sor['id']." ".$sor['cim']."\n";
				}
				if($tip == $TIP['uj']) print "<option selected>\n";
				print "</select>\n";
//					print "<input type=submit value=Go>\n"; // Nem kell, ha van JavaScript
			}
			print ("</form>\n\n");
	}

	echo "<form method=post>\n";
	if( $result = pg_exec("SELECT * FROM Hely WHERE id=".$hir['id'])) {
		$rows = pg_numrows($result);
		for($i=0; $i<$rows; $i++) {
			$sor = pg_fetch_array($result, $i);
			$hely[$sor['oldal']] = 'On';
		}
	}
	for( $i=0; $i<$OLDAL; $i++) {
		$ch = isset($hely[$i])?' checked':'';
		print "<input type=checkbox name=x$i$ch>".$OCIM[$i]."<br>\n";
	}

	echo "<td valign=top>\n";
	print "<table><tr><td align=right><b>Id:&nbsp;</b><td><b>".$hir['id']."</b>\n";
	print "<input name=id type=hidden value=".$hir['id'].">\n";
//	print "<tr><td align=right>URL:&nbsp;<td><input name=url type=text size=80 value=\"".$hir['url']."\">\n";
	print "<tr><td align=right>Cim:&nbsp;<td><input name=cim type=text size=80 value=\"".$hir['cim']."\">\n";
	print "<tr><td align=right>Lied:&nbsp;<td><input name=lied type=text size=80 value=\"".$hir['lied']."\">\n";
	print "<tr><td align=right>Szöveg:&nbsp;<td><textarea name=duma cols=80 rows=20>\n".$hir['duma']."</textarea>\n";
	print "<tr><td align=center colspan=2>\n";
	if($tip == $TIP['uj']){ print "<input type=submit name=tip value=\"".$TIP['fel']."\">\n"; }
	print "<input type=submit name=tip value=\"".$TIP['mod']."\">\n";
	print "<input type=reset value=\"Reset\">\n";
	print "</table></form>\n";
}

if(!isset($m)) $m='hir';

if( $m == 'hely' ) { // $m == 'hely'
	if(!isset($oldal)) $oldal=0;
	print "\n\n<form>\n<input type=radio name=m value=hir onChange='Betolt(\"m=hir\")'> Hír\n";
	print "<input type=radio name=m value=hely onChange='Betolt(\"m=hely\")' checked> Hely\n</form>\n";

	print "\n<form>\n";
	print "<select name=oldal onChange='Betolt(\"oldal=\"+this.value+\"&m=hely\")'>\n";
	for( $i=0; $i<$OLDAL; $i++ ) {
		print "<option value=".$i;
		if($i == $oldal) print " selected";
		print "> ".$OCIM[$i]."\n";
	}
	print "</select>\n";
	print "</form>\n";

	if($tip == $TIP['mod']) {
		$sor = pg_fetch_array(pg_exec("SELECT max(id) as id FROM hir"));
		for( $i=1; $i<=$sor['id']; $i++) {
			if(isset(${'x'.$i})) @pg_exec("INSERT INTO Hely (id, oldal) VALUES ($i, $oldal)");
			else @pg_exec("DELETE FROM Hely WHERE id='$i' AND oldal='$oldal'");
		}
	}

	echo "\n<form method=post>\n";
	$Q = "SELECT A.cim,A.id,B.id AS van FROM";
	$Q .= "  (SELECT id,cim FROM Hir ORDER BY id) AS A";
	$Q .= "  LEFT OUTER JOIN";
	$Q .= "  (SELECT id FROM Hely WHERE oldal=".$oldal.") AS B";
	$Q .= "  ON (A.id=B.id)";

	if( $result = pg_exec($Q)) {
		$rows = pg_numrows($result);
		for($i=0; $i<$rows; $i++) {
			$sor = pg_fetch_array($result, $i);
			print "<input type=checkbox name=x".($i+1);
			if( $sor['id'] == $sor['van'] ) print " checked";
			print ">".$sor['cim']."<br>\n";
		}
	}
	print "<input type=submit name=tip value=\"".$TIP['mod']."\"><br>\n";

	print ("</form>\n\n");
}

else { // $m == 'hir'

	echo "\n\n<table width=100%>\n<tr><td>\n"; //  width=20%>";

	if(!isset($id)) $tip=$TIP['uj'];
	switch ($tip) {
		case $TIP['uj']:
			$sor = pg_fetch_array(pg_exec("SELECT max(id) as id FROM hir"), 0);
			$hir = array ('id'=>$sor['id']+1, 'url'=>'', 'cim'=>'', 'lied'=>'', 'duma'=>'');
			hir_ki($hir);
			break;

		case $TIP['mod']:
			pg_exec("UPDATE Hir SET url='$url', cim='$cim', lied='$lied', duma='$duma' WHERE id=$id");
			$hir = array ('id'=>$id, 'url'=>$url, 'cim'=>$cim, 'lied'=>$lied, 'duma'=>$duma);
			for( $i=0; $i<$OLDAL; $i++) {
				if(isset(${'x'.$i})) @pg_exec("INSERT INTO Hely (id, oldal) VALUES ($id, $i)");
				else @pg_exec("DELETE FROM Hely WHERE id='$id' AND oldal='$i'");
			}
			hir_ki($hir);
			break;

		case $TIP['fel']:
			pg_exec("INSERT INTO Hir (url, cim, lied, duma) VALUES ('$url', '$cim', '$lied', '$duma')");
			$hir = array ('id'=>$id, 'url'=>$url, 'cim'=>$cim, 'lied'=>$lied, 'duma'=>$duma);
			for( $i=0; $i<$OLDAL; $i++) {
				if(isset(${'x'.$i})) @pg_exec("INSERT INTO Hely (id, oldal) VALUES ($id, $i)");
				else @pg_exec("DELETE FROM Hely WHERE id='$id' AND oldal='$i'");
			}
			hir_ki($hir);
			break;

		default:
			if( $result = pg_exec("SELECT * FROM Hir WHERE id=$id")) {
				$hir = pg_fetch_array($result);
			}
			hir_ki($hir);
			break;
	}

	print "</table>\n\n<br><p>\n<hr>\n";
	print "<table align=center width=760 border=2>\n\n";
	print "<tr><td bgimage=back.jpg colspan=3><br>\n";
	print "<tr><td class=bal width=120>\n";
	print "      <table>\n";
	print "      <tr><td class=bal>\n";
	print "          <p class=elso><a href=".$hir['url'].">".$hir['cim']."<img src=nyil.gif></a>\n";
	print "          <p>".$hir['lied']."\n";
	print "      <tr><td class=sep>\n\n";
	print "      </table>\n";
	print "  <td width=20>&nbsp;\n\n";
	print "  <td width=620>\n\n";
	print "<h3>".$hir['cim']."</h3>\n\n";
	print "<ul><font size=-1><i>" . $hir['lied'] . "</i></font></ul>";
	$hir['duma'] = preg_replace('/\r/', '', $hir['duma']);
	$hir['duma'] = preg_replace('/\n\n/', "\n\n<p>", $hir['duma']);
	$hir['duma'] = preg_replace('/`([^`]*)`/', '<i>$1</i>', $hir['duma']);
	$hir['duma'] = preg_replace('/\*([^\*]*)\*/', '<b>$1</b>', $hir['duma']);
	print      $hir['duma'];
	print "</table>\n\n";

} // else - $m == 'hir'

pg_close ($db);

print "</body>\n";
print "</html>\n";
return 0;
