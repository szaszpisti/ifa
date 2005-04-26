#!/usr/bin/perl

use strict;
use DBI;
use Spreadsheet::WriteExcel;
use POSIX qw(strftime);
use constant true => 1;

# use Data::Dumper;
# print Dumper \@tabla;

my $DEBUG = 0;

my $db_fogado = DBI->connect("DBI:Pg:dbname=fogado", "www");

# Az legutóbb bejegyzett fogadóóra kigyûjtése
my $FA = $db_fogado->prepare("SELECT * FROM Fogado_admin"
				. " WHERE id=(SELECT MAX(id) FROM Fogado_admin)");
$FA->execute();

my $fogadoEntry = $FA->fetchrow_hashref;
my $fid = $fogadoEntry->{id} or die "Nincs fid!\n";

(my $fogadoDate = $fogadoEntry->{datum}) =~ s{-}{.}g;
# my $filename = "fogado-$fogadoDate.xls";
my $filename = strftime ("fogado-%Y.%m.%d-%H%M%S.xls", localtime);

if(!$DEBUG) {
	print "Content-type: application/vnd.ms-excel\n";
	print "Content-Disposition: attachment; filename=$filename\n\n";
}

my $tanarLista = $db_fogado->prepare("SELECT tanar, tnev FROM Fogado, Tanar "
		. "WHERE fid=" . $fid . " AND Fogado.tanar=Tanar.id "
		. "GROUP BY tanar, tnev ORDER BY tnev");

# A tanárokat névsorba rendezzük
$tanarLista->execute;
my $darab = 0;
my (@nevsor, @tanar);
while (my $sor = $tanarLista->fetchrow_hashref) {
	$darab++;
	$nevsor[$darab] = $sor->{tanar};
	$tanar[$sor->{tanar}] = $sor->{tnev};
}

my $minmaxGlobal = $db_fogado->prepare("SELECT min(ido), max(ido) "
			. "FROM Fogado WHERE fid=" . $fid);
$minmaxGlobal->execute();
my $mm = $minmaxGlobal->fetchrow_hashref;
my $IDO_min = 2*int($mm->{min}/2);
my $IDO_max = 2*int($mm->{max}/2);

# minden tanárhoz ittlétének kezdete és vége
my $minmaxPerUser = $db_fogado->prepare("SELECT tanar, MIN(ido), MAX(ido) "
			. "FROM Fogado WHERE fid=" . $fid . " GROUP BY tanar");
$minmaxPerUser->execute();
my (@minPerUser, @maxPerUser);
while (my $t = $minmaxPerUser->fetchrow_hashref) {
	$minPerUser[$t->{tanar}] = $t->{min};
	$maxPerUser[$t->{tanar}] = $t->{max};
}

# nagy táblázat, minden bejegyzés benne van
my $mind = $db_fogado->prepare("SELECT tanar, ido, dnev, onev FROM Fogado AS F"
	. " LEFT OUTER JOIN"
	. "   ( SELECT * FROM Diak UNION"
	. "     SELECT -2, NULL, 'Szülõi', NULL, NULL, NULL, NULL ) AS D"
	. "   ON (F.diak=D.id)"
	. " WHERE F.fid=" . $fid . " AND (F.diak>0 OR F.diak=-2) ORDER BY ido");
$mind->execute();

my (@tabla, @paratlan);
while (my $t = $mind->fetchrow_hashref) {
	# az if defined nélkül "Use of uninitialized value" hiba van strict-nél...
	(my $onev = $t->{onev}) =~ s{ }{}g; # if defined $onev;
	$tabla[$t->{tanar}][$t->{ido}] = $t->{dnev} . " " . $onev; # if defined $onev;
	if ( ($t->{ido}%2) && ($t->{dnev} ne "") && ($t->{dnev} ne "Szülõi") ) {
		$paratlan[$t->{tanar}] = true;
	}
}

# DEBUG esetén (parancssorból indítva) fájlt hozunk létre
my $workBook = Spreadsheet::WriteExcel->new($DEBUG?"$filename":"-");
my @book;

my $formatOsszDiak = $workBook->add_format(text_wrap => 1);
my $formatTanarNev = $workBook->add_format(bold => 1, size => 18);

$book[0] = $workBook->add_worksheet('Összesített');

$book[0]->set_column('A:A', 25);

# A fogadóóra dátumát beírjuk a sarokba
$book[0]->write (0, 0, $fogadoDate);

# Az idõpontok kiírása az összesítésbe
my $oszlop = 1;
for (my $ido = $IDO_min; $ido <= $IDO_max; $ido += 2) {
	$book[0]->write(1, $oszlop, fiveToString($ido));
	$oszlop++;
}

my $osszesitoSor = 2;
my $vnev;
for (my $i = 1; $i <= $darab; $i++) {
	$osszesitoSor++;
	my $id = $nevsor[$i];

	($vnev = $tanar[$id]) =~ s{^([^ ]* .).*$}{$1.};

	# Az összesített lista sorai
	# Tanárnév hivatkozásként a saját munkalapra
	$book[0]->write($osszesitoSor, 0, "internal:'$vnev'!A1", $tanar[$id]);

	# a páros sorok
	my $osszesitoOszlop = 0;
	for (my $ido = $IDO_min; $ido <= $IDO_max; $ido += 2) {
		$osszesitoOszlop++;
	   $book[0]->write($osszesitoSor, $osszesitoOszlop, $tabla[$id][$ido], $formatOsszDiak);
	}

	# ha van, akkor a páratlan sorok
	if ($paratlan[$id]) {
		$osszesitoSor++;
		$osszesitoOszlop = 0;
		for (my $ido = $IDO_min+1; $ido <= $IDO_max+1; $ido += 2) {
			$osszesitoOszlop++;
	   	$book[0]->write($osszesitoSor, $osszesitoOszlop, $tabla[$id][$ido], $formatOsszDiak);
		}
	}

	# Itt jönnek a tanári listák
	$book[$i] = $workBook->add_worksheet($vnev);
	$book[$i]->write (1, 0, $tanar[$id], $formatTanarNev);
	my $egyeniSor = 3; # hanyadik sorba kell kiírni?
	for (my $ido = $minPerUser[$id]; $ido <= $maxPerUser[$id]; $ido += $paratlan[$id]?1:2) {
		$egyeniSor++;
		$book[$i]->write ($egyeniSor, 0, fiveToString($ido));
		$book[$i]->write ($egyeniSor, 1, $tabla[$id][$ido]);
	}
}

sub fiveToString {
	my $ido = shift;
	return sprintf("%02d:%02d", int($ido/12), ($ido%12)*5);
}

$book[0]->set_row($_, 12) for (3..60);

