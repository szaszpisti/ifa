#!/usr/bin/perl
#
#   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
#   This file is part of the IFA suite,
#   Copyright 2004-2005 Szász Imre.
#
#   Ez egy szabad szoftver; terjeszthetõ illetve módosítható a GNU
#   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
#   késõbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
#
#   This program is free software; you can redistribute it and/or
#   modify it under the terms of the GNU General Public License
#   as published by the Free Software Foundation; either version
#   2 of the License, or (at your option) any later version.
#

use strict;
use DBI;
use Spreadsheet::WriteExcel;
use POSIX qw(strftime);
use constant true => 1;

# use diagnostics;
use constant DEBUG => 0;
if (DEBUG) { use Data::Dumper; } # érdekes, ezt mindig betölti...

# print Dumper DEBUG;

# Nem egységes az erõforrás-leírás, a fogado.ini.php-ba kell egy PERL_DSN = "..." sor
my $PERL_DSN;
open (INI, "< fogado.ini.php");
while (<INI>) {
	$PERL_DSN = $1 if (/^PERL_DSN = "(.*)"$/);
}
close (INI);

my $db_fogado = DBI->connect($PERL_DSN, "www");

# Az legutóbb bejegyzett fogadóóra kigyûjtése
my $FA = $db_fogado->prepare("SELECT * FROM Admin"
				. " WHERE id=(SELECT MAX(id) FROM Admin)");
$FA->execute();

my $fogadoEntry = $FA->fetchrow_hashref;
my $fid = $fogadoEntry->{id} or die "Nincs fid!\n";

(my $fogadoDate = $fogadoEntry->{datum}) =~ s{-}{.}g;
# my $filename = "fogado-$fogadoDate.xls";
my $filename = strftime ("fogado-%Y.%m.%d-%H%M%S.xls", localtime);

if (!DEBUG) {
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

my $minmaxGlobal = $db_fogado->prepare("SELECT MIN(ido) AS min, MAX(ido) AS max "
			. "FROM Fogado WHERE fid=" . $fid);
$minmaxGlobal->execute();
my $mm = $minmaxGlobal->fetchrow_hashref;
my $IDO_min = 2*int($mm->{min}/2);
my $IDO_max = 2*int($mm->{max}/2);

# minden tanárhoz ittlétének kezdete és vége
my $minmaxPerUser = $db_fogado->prepare("SELECT tanar, MIN(ido) AS min, MAX(ido) AS max "
			. "FROM Fogado WHERE fid=" . $fid . " GROUP BY tanar");
$minmaxPerUser->execute();
my (@minPerUser, @maxPerUser);
while (my $t = $minmaxPerUser->fetchrow_hashref) {
	$minPerUser[$t->{tanar}] = $t->{min};
	$maxPerUser[$t->{tanar}] = $t->{max};
}

# nagy táblázat, minden bejegyzés benne van
my $q = "SELECT tanar, ido, dnev, onev FROM Fogado AS F"
		. " LEFT OUTER JOIN"
		. "   ( SELECT * FROM Diak UNION"
		. "     SELECT -2 AS id, NULL AS jelszo, 'Szülõi' AS dnev, NULL AS oszt,"
		. "            NULL AS onev, NULL AS ofo, NULL AS ofonev ) AS D"
		. "   ON (F.diak=D.id)"
		. " WHERE F.fid=" . $fid . " AND (F.diak>0 OR F.diak=-2) ORDER BY ido";

my $mind = $db_fogado->prepare($q);
$mind->execute();

my (@tabla, @paratlan);
while (my $t = $mind->fetchrow_hashref) {
	(my $onev = $t->{onev}) =~ s{ }{}g;
	$tabla[$t->{tanar}][$t->{ido}] = $t->{dnev} . " " . $onev;
	if ( ($t->{ido}%2) && ($t->{dnev} ne "") && ($t->{dnev} ne "Szülõi") ) {
		$paratlan[$t->{tanar}] = true;
	}
}

# DEBUG esetén (parancssorból indítva) fájlt hozunk létre
my $workBook = Spreadsheet::WriteExcel->new(DEBUG?"$filename":"-");
my @book;

my $formatOsszDiak = $workBook->add_format(text_wrap => 1);
my $formatTanarNev = $workBook->add_format(bold => 1, size => 18);

my $osszesitoNev = 'Összesített';
$book[0] = $workBook->add_worksheet($osszesitoNev);

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

	# $vnev: a tanári lap neve: 'Monoton Manó' -> 'Monoton M'
	($vnev = $tanar[$id]) =~ s{^([^ ]* .).*$}{$1};

	# Az összesített lista sorai
	# Tanárnév hivatkozásként a saját munkalapra
	$book[0]->write($osszesitoSor, 0, "internal:'$vnev'!A1", $tanar[$id]);

	# a páros sorok
	my $osszesitoOszlop = 0;
	for (my $ido = $IDO_min; $ido <= $IDO_max; $ido += 2) {
		$osszesitoOszlop++;
	   $book[0]->write($osszesitoSor, $osszesitoOszlop, $tabla[$id][$ido], $formatOsszDiak);
	}

	# ha vannak páratlan sorok, azokat külön sorba írjuk
	if ($paratlan[$id]) {
		$osszesitoSor++;
		$osszesitoOszlop = 0;
		for (my $ido = $IDO_min+1; $ido <= $IDO_max+1; $ido += 2) {
			$osszesitoOszlop++;
	   	$book[0]->write($osszesitoSor, $osszesitoOszlop, $tabla[$id][$ido], $formatOsszDiak);
		}
	}

	# Itt jönnek a tanári listák külön lapokra
	$book[$i] = $workBook->add_worksheet($vnev);
	$book[$i]->write(1, 0, "internal:'$osszesitoNev'!A1", $tanar[$id], $formatTanarNev);
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
# $book[0]->activate();

