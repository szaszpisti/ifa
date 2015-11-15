#!/usr/bin/perl
#
#   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
#   This file is part of the IFA suite,
#   Copyright 2004-2005 Szász Imre.
#
#   Ez egy szabad szoftver; terjeszthető illetve módosítható a GNU
#   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
#   későbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
#
#   This program is free software; you can redistribute it and/or
#   modify it under the terms of the GNU General Public License
#   as published by the Free Software Foundation; either version
#   2 of the License, or (at your option) any later version.

# http://search.cpan.org/dist/Spreadsheet-WriteExcel/lib/Spreadsheet/WriteExcel.pm
# http://search.cpan.org/dist/Spreadsheet-WriteExcel/lib/Spreadsheet/WriteExcel/Examples.pm

# aptitude install libspreadsheet-writeexcel-perl libtext-unaccent-perl libdbd-sqlite3-perl
use strict;
use DBI;
use Spreadsheet::WriteExcel;
use POSIX qw(strftime);
use constant true => 1;
use Encode 'decode';
use Text::Unaccent;
use utf8;


# use diagnostics;
use constant DEBUG => 0;
if (DEBUG) { use Data::Dumper; } # érdekes, ezt mindig betölti...

# print Dumper DEBUG;

# Nem egységes az erőforrás-leírás, a ifa.ini.php-ba kell egy PERL_DSN = "..." sor
my $PERL_DSN;
open (INI, "< ifa.ini.php");
while (<INI>) {
    $PERL_DSN = $1 if (/^PERL_DSN = "(.*)"$/);
}
close (INI);

my $db_fogado = DBI->connect($PERL_DSN, "www");

# Az legutóbb bejegyzett fogadóóra kigyűjtése
my $FA = $db_fogado->prepare("SELECT * FROM Admin"
                . " WHERE id=(SELECT MAX(id) FROM Admin)");
$FA->execute();

my $fogadoEntry = $FA->fetchrow_hashref;
my $fid = $fogadoEntry->{id} or die "Nincs fid!\n";

(my $fogadoDate = $fogadoEntry->{datum}) =~ s{-}{.}g;
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
    $tanar[$sor->{tanar}] = decode('utf8', $sor->{tnev});
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
my $q = "SELECT * FROM Fogado AS F"
        . " LEFT OUTER JOIN"
        . "   ( SELECT * FROM Diak UNION"
        . "     SELECT -2 AS id, NULL AS jelszo, 'Szülői értekezlet' AS dnev, NULL AS oszt,"
        . "            NULL AS onev, NULL AS ofo, NULL AS ofonev ) AS D"
        . "   ON (F.diak=D.id)"
        . " WHERE F.fid=" . $fid . " AND (F.diak>0 OR F.diak=-2) ORDER BY ido";

my $mind = $db_fogado->prepare($q);
$mind->execute();

my (@tabla, @paratlan);
while (my $t = $mind->fetchrow_hashref) {
    (my $onev = $t->{onev}) =~ s{ }{}g;
#    from_to($t->{dnev}, "utf-8", "iso-8859-2");
    $tabla[$t->{tanar}][$t->{ido}] = $t->{dnev} . " " . $onev;
    if ( ($t->{ido}%2) && ($t->{dnev} ne "") && (unac_string('iso-8859-2', $t->{dnev}) ne "Szuloi") ) {
        $paratlan[$t->{tanar}] = true;
    }
}

# DEBUG esetén (parancssorból indítva) fájlt hozunk létre
my $workBook = Spreadsheet::WriteExcel->new(DEBUG?"$filename":"-");
my @book;

my $formatOsszDiak = $workBook->add_format(text_wrap => 1);
my $formatTanarNev = $workBook->add_format(bold => 1, size => 18);
my $formatListaDiak = $workBook->add_format(bottom => 1);
my $formatListaSzuloi = $workBook->add_format(bottom => 1, italic => 1, bold => 1);
my $formatListaTanar = $workBook->add_format(bold => 1, size => 16, top => 2, bottom => 1, text_wrap => 1, valign  => 'top');

my $osszesitoNev = 'Osszesitett';
my $listaNev = 'Lista';

$book[0] = $workBook->add_worksheet($osszesitoNev);
$book[0]->set_column('A:A', 25);

$book[1] = $workBook->add_worksheet($listaNev);
$book[1]->set_margins_TB(.2);
$book[1]->set_margins_LR(.5);
$book[1]->hide_gridlines(1); # nyomtatásban ne legyenek rácsok
$book[1]->fit_to_pages(1);
$book[1]->print_area('A:E');
$book[1]->set_column('C:C', 15);

# A fogadóóra dátumát beírjuk a sarokba
$book[0]->write (0, 0, $fogadoDate);

# Az időpontok kiírása az összesítésbe
my $oszlop = 1;
for (my $ido = $IDO_min; $ido <= $IDO_max; $ido += 2) {
    $book[0]->write(1, $oszlop, fiveToString($ido));
    $oszlop++;
}

$book[1]->set_column('B:B', 40);
$book[1]->set_column('E:E', 40);

my ($format, $diak, $tanarLink);
my $osszesitoSor = 2;
my $listaSor = 1;
for (my $i = 2; $i <= $darab+1; $i++) { # 0, 1 foglalt, 2-től kezdődnek a tanári lapok
    $osszesitoSor++;
    my $id = $nevsor[$i-1];

    # $tanarLink: a tanári lap neve: 'Monoton Manó' -> 'Monoton M'
    ($tanarLink = $tanar[$id]) =~ s{^(.{30}).*$}{$1};
    $tanarLink = unac_string ('utf8', $tanarLink);

    # Az összesített lista sorai
    # Tanárnév hivatkozásként a saját munkalapra
    $book[0]->write($osszesitoSor, 0, "internal:'$tanarLink'!A1", $tanar[$id]);

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
    $book[$i] = $workBook->add_worksheet($tanarLink);
    $book[$i]->write(1, 0, "internal:'$osszesitoNev'!A1", $tanar[$id], $formatTanarNev);

    # A Listába is betesszük a tanárt
    $book[1]->set_row($listaSor, 33);
    $book[1]->merge_range('A'.($listaSor+1).':B'.($listaSor+1), $tanar[$id], $formatListaTanar);
    $book[1]->merge_range('D'.($listaSor+1).':E'.($listaSor+1), $tanar[$id], $formatListaTanar);
    $listaSor += 1;

    my $egyeniSor = 3; # hanyadik sorba kell kiírni?
    my $elsoSzuloi = 1;
    for (my $ido = $minPerUser[$id]; $ido <= $maxPerUser[$id]; $ido += $paratlan[$id]?1:2) {
        $egyeniSor++;
        $format = $formatListaDiak;
        $diak = decode('utf8', $tabla[$id][$ido]); # diák neve vagy "x" (Szülői értekezlet)

        $book[$i]->write ($egyeniSor, 0, fiveToString($ido));
        $book[$i]->write ($egyeniSor, 1, $diak);

        if ($diak ne '' && ($diak !~ /Szülői értekezlet/ || $elsoSzuloi)){ # diák, vagy szülői esetén az első
            if ($diak =~ /Szülői értekezlet/) { # Csak az első szülőit jelenítse meg
                $elsoSzuloi = 0;
                $format = $formatListaSzuloi;
            }
            $book[1]->write($listaSor, 0, fiveToString($ido), $formatListaDiak);
            $book[1]->write($listaSor, 1, $diak, $format);
            $book[1]->write($listaSor, 3, fiveToString($ido), $formatListaDiak);
            $book[1]->write($listaSor, 4, $diak, $format);
            $listaSor += 1;
        }
    }
    $book[$i]->set_row(1, 23);
    $book[1]->set_row($listaSor, 40);
    $listaSor += 1;
}

sub fiveToString {
    my $ido = shift;
    return sprintf("%02d:%02d", int($ido/12), ($ido%12)*5);
}

$book[0]->set_row($_, 12) for (3..60);
$book[1]->activate();

