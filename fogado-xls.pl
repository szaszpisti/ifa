#!/usr/bin/perl
#-w

#use strict;
use DBI;
use Spreadsheet::WriteExcel;

$DEBUG=0;

my $db_fogado = DBI->connect("DBI:Pg:dbname=fogado");

my $FA = $db_fogado->prepare("SELECT * FROM Fogado_admin WHERE id=(SELECT MAX(id) FROM Fogado_admin)");
$FA->execute();
$t = $FA->fetchrow_hashref;
my $fid = $t->{id} or die "Nincs fid!\n";

($fdat = $t->{datum}) =~ s/-/./g;
my $filename ="fogado-$fdat.xls";

if(!$DEBUG) {
	print "Content-type: application/vnd.ms-excel\n";
	print "Content-Disposition: attachment; filename=$filename\n\n";
}


my $tanarlista = $db_fogado->prepare("SELECT tanar, tnev FROM Fogado, Tanar WHERE fid=".$fid." AND Fogado.tanar=Tanar.id GROUP BY tanar, tnev ORDER BY tnev");

# A tanárokat névsorba rendezzük
$tanarlista->execute;
$darab=0;
while (my $sor = $tanarlista->fetchrow_hashref) {
	$darab++;
	$nevsor[$darab] = $sor->{tanar};
	$tanar[$sor->{tanar}] = $sor->{tnev};
}

my $minmax_ossz = $db_fogado->prepare("SELECT min(ido), max(ido) FROM Fogado WHERE fid=".$fid);
$minmax_ossz->execute();
$t = $minmax_ossz->fetchrow_hashref;
$IDO_min = 2*int($t->{min}/2);
$IDO_max = 2*int($t->{max}/2);

# minden tanárhoz ittlétének kezdete és vége
my $minmax = $db_fogado->prepare("SELECT tanar, MIN(ido), MAX(ido) FROM Fogado WHERE fid=".$fid." GROUP BY tanar");
$minmax->execute();
while ($t = $minmax->fetchrow_hashref) {
	$min[$t->{tanar}] = $t->{min};
	$max[$t->{tanar}] = $t->{max};
}

# nagy táblázat, minden bejegyzés benne van
$mind = $db_fogado->prepare("SELECT tanar, ido, dnev, onev FROM Fogado AS F"
	. " LEFT OUTER JOIN"
	. "   ( SELECT * FROM Diak UNION"
	. "     SELECT -2,NULL,'Szülõi',NULL,NULL,NULL,NULL ) AS D"
	. "   ON (F.diak=D.id)"
	. " WHERE F.fid=".$fid." AND F.diak>0 OR F.diak=-2 ORDER BY ido");
$mind->execute();
while ($t = $mind->fetchrow_hashref) {
	($onev = $t->{onev}) =~ s/ //g;
	$tabla[$t->{tanar}][$t->{ido}] = $t->{dnev}." ".$onev;
	if ( ($t->{ido}%2) && ($t->{dnev} ne "") && ($t->{dnev} ne "Szülõi") ) { $paratlan[$t->{tanar}] = true; }
}

# my $workbook = Spreadsheet::WriteExcel->new($filename);
my $workbook = Spreadsheet::WriteExcel->new($DEBUG?"$filename":"-");
my @book;

$FormatOsszDiak = $workbook->add_format(text_wrap => 1);
$FormatTanarNev = $workbook->add_format(bold => 1, size => 18);

$book[0] = $workbook->add_worksheet('Összesített');

$book[0]->set_column('A:A', 25);

# Az idõpontok kiírása az összesítésbe
$oszlop=1;
for ($ido=$IDO_min; $ido<=$IDO_max; $ido+=2) {
	$book[0]->write(1, $oszlop, FiveToString($ido));
	$oszlop++;
}

$OsszesitoSor = 2;
for ($i = 1; $i <= $darab; $i++) {
	$OsszesitoSor++;
	$id = $nevsor[$i];

	($vnev = $tanar[$id]) =~ s/^([^ ]* .).*$/$1./;

	# Az összesített lista sorai
	# Tanárnév hivatkozásként a saját munkalapra
	$book[0]->write($OsszesitoSor, 0, "internal:'$vnev'!A1", $tanar[$id]);

	# a páros sorok
	$OsszesitoOszlop=0;
	for ($ido=$IDO_min; $ido<=$IDO_max; $ido+=2) {
		$OsszesitoOszlop++;
	   $book[0]->write($OsszesitoSor, $OsszesitoOszlop, $tabla[$id][$ido], $FormatOsszDiak);
	}

	# ha van, akkor a páratlan sorok
	if ($paratlan[$id]) {
		$OsszesitoSor++;
		$OsszesitoOszlop=0;
		for ($ido=$IDO_min+1; $ido<=$IDO_max+1; $ido+=2) {
			$OsszesitoOszlop++;
	   	$book[0]->write($OsszesitoSor, $OsszesitoOszlop, $tabla[$id][$ido], $FormatOsszDiak);
		}
	}

	# Itt jönnek a tanári listák
	$book[$i] = $workbook->add_worksheet($vnev);
	$book[$i]->write (1, 0, $tanar[$id], $FormatTanarNev);
	$EgyeniSor = 3; # hanyadik sorba kell kiírni?
	for ($ido=$min[$id]; $ido<=$max[$id]; $ido+=$paratlan[$id]?1:2) {
		$EgyeniSor++;
		$book[$i]->write ($EgyeniSor, 0, FiveToString($ido));
		$book[$i]->write ($EgyeniSor, 1, $tabla[$id][$ido]);
	}
}

sub FiveToString {
	my $ido = shift;
	return sprintf("%02d:%02d", int($ido/12), ($ido%12)*5);
}

$book[0]->set_row($_, 12) for (3..60);

