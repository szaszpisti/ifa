#!/usr/bin/php
<?

$userFile = $argv[1];

if (!file_exists($userFile)) {
print '
Az elsõ paraméterként megadott ($userFile) állományból generál insert sorokat
az adatbázishoz, Létrehoz egy $userFile.pw fájlt a kiosztandó jelszókkal és egy
$userFile.insert fájlt, amit fel lehet használni a gen-db.php programhoz.

A $userFile felépítése:
Tanar Neve;tid
===
oid;Osztaly Neve;tid
===
Diak Neve;did;oid

Példa:
# az üres vagy # kezdetû sorokat nem veszi figyelembe
Monoton Manó;117
===
d05a;2. A;117
===
Pumpa Pál;32;d05a

';
die("Nem létezik a fájl: " . $userFile . "\n");
}


// +----------------------------------------------------------------------+
// | Author:  Ell Gree <ellgree@gmx.net>                                  |
// | http://unix.freshmeat.net/projects/gen_password/                     |
// +----------------------------------------------------------------------+
function gen_password($p="", $l=8, $f=4) {
  $d=array('a'=>'ntrsldicmzp','b'=>'euloayribsj','c'=>'oheaktirulc',
       'd'=>'eiorasydlun','e'=>'nrdsaltevcm','f'=>'ioreafltuyc',
       'g'=>'aeohrilunsg','h'=>'eiaotruykms','i'=>'ntscmledorg',
       'j'=>'ueoairhjklm','k'=>'eiyonashlus','l'=>'eoiyaldsfut',
       'm'=>'eaoipsuybmn','n'=>'goeditscayl','o'=>'fnrzmwtovls',
       'p'=>'earolipuths','q'=>'uuuuaecdfok','r'=>'eoiastydgnm',
       's'=>'eothisakpuc','t'=>'hoeiarzsuly','u'=>'trsnlpgecim',
       'v'=>'eiaosnykrlu','w'=>'aiheonrsldw','x'=>'ptciaeuohnq',
       'y'=>'oesitabpmwc','z'=>'eaiozlryhmt');
  $a=range('a','z');
  $l%=50; $f%=11;
  $p=strtolower(ereg_replace("[^a-zA-Z]","",substr($p,0,$l-1))) or
  $p=$a[rand(0,sizeof($a)-1)];
  while(strlen($p)<$l) {
    $ff = $f;
    while(substr_count($p,substr($p,strlen($p)-1,1).
      ($k=substr($d[substr($p,strlen($p)-1,1)],rand(0,$ff%11),1))))
	if(++$ff>10) break;
    $p.=$k;
  }
  return $p;
}

$fUser = file($userFile);

$jelszo = gen_password();

// Az Admint is berakjuk de jelszót külön kell neki adni!
$INSERT = "INSERT INTO Diak (id, jelszo, dnev) "
	. "VALUES (0, '" . md5($jelszo) . "', 'Admin');\n\n";
$OUT = "Admin;$jelszo\n===\n";

$i = 0;
$n = sizeof($fUser);

// Tanárok felsorolása
while (!preg_match('/===/', $fUser[$i]) && $i <= $n) {
	$sor = trim($fUser[$i]);
	if (preg_match('/^$/', $sor) || preg_match('/^#/', $sor)) {
		$i++;
		continue;
	}
	$t = explode(';', $sor);
	$tanar[$t[1]] = $t[0];

	$jelszo = gen_password();

	$OUT .= $t[0] . ";$jelszo\n";
	$INSERT .= 'INSERT INTO Tanar (id, jelszo, tnev) VALUES ('
		. $t[1] . ", '" . md5($jelszo) . "', '" . $t[0] . "');\n";
	$i++;
}
$OUT .= $fUser[$i++];
$INSERT .= "\n";

// Osztályok felsorolása
while (!preg_match('/===/', $fUser[$i]) && $i <= $n) {
	$sor = trim($fUser[$i]);
	if (preg_match('/^$/', $sor) || preg_match('/^#/', $sor)) {
		$i++;
		continue;
	}
	$t = explode(';', $sor);
	// a 3. az osztályfõnök azonosítója
	$ofoid = $t[2];

	// Nagy hiba, ha nincs a tanár táblában
	if (!isset($tanar[$ofoid])) {
		die($t[0] . " osztály fõnöke ($ofoid) nem szerepel a tanárok közt!\n");
	}

	// $osztaly[oid] = array(
	//   string  =>  ', 'Pumpa Pál', 'd05a', '8. A', '117', 'Monoton Manó');
	//   onev    =>  d05a
	// )
	$osztaly[$t[0]] = array(
		'string' => "', '" . $t[0] . "', '" . $t[1] . "', '" . $ofoid . "', '" . $tanar[$ofoid] . "');\n",
		'onev'   => $t[1]
	);

	$i++;
}
$i++;


// Diákok felsorolása
while (!preg_match('/===/', $fUser[$i]) && $i <= $n) {
	$sor = trim($fUser[$i]);
	if (preg_match('/^$/', $sor) || preg_match('/^#/', $sor)) {
		$i++;
		continue;
	}
	$t = explode(';', $sor);

	// a 3. az osztály azonosítója
	$oid = $t[2];

	// Nagy hiba, ha nincs az osztály táblában
	if (!isset($osztaly[$oid])) {
		die($t[0] . " osztálya ($oid) nem szerepel az osztályok közt!\n");
	}

	$jelszo = gen_password();

	$INSERT .= 'INSERT INTO Diak (id, jelszo, dnev, oszt, onev, ofo, ofonev) VALUES ('
		. $t[1] . ", '" . md5($jelszo) . "', '" . $t[0] . $osztaly[$oid]['string'];

	$OUT .= $t[0] . ";" . $osztaly[$oid]['onev'] . ";" . $jelszo . "\n";
	$i++;
}
$OUT .= $fUser[$i++];

$fh = fopen ($userFile . ".insert" , 'w');
fwrite ($fh, $INSERT);
fclose ($fh);

$fh = fopen ($userFile . ".pw" , 'w');
fwrite ($fh, $OUT);
fclose ($fh);

?>
