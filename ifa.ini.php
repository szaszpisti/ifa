<?php
/*
 *   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Szász Imre.
 *
 *   Ez egy szabad szoftver; terjeszthető illetve módosítható a GNU
 *   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
 *   későbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

/**
 * Globális változók
 */

/**
# $dsn:
#
#   Az fogadóóra adatbázisának adatforrás neve.
#
#   Megadható bárhogyan, akár a példától eltérő RDBMS használata is. Lényeg, hogy
#   a végén legyen egy létező $dsn változó -- a PDO által várt
#   módon: akár szöveges, akár tömb formában.
#
#   A DSN (Data Source Name) részletesebb leírására ld.
#   http://pear.activeventure.com/package/package.database.db.intro-dsn.html
#   http://pear.activeventure.com/package/package.database.db.intro-connect.html
#
#   Példa:
#
#   $dsn = 'pgsql://fadmin:jelszo@localhost/fogado'
#
#   $dsn = array(
#       'phptype'  => 'sqlite',
#       'database' => 'ifa.db',
#       'mode'     => '0644',
#   );
#
# FIGYELEM! Ne felejtsd ki a PERL_DSN-t!
*/

// $pdo = new \PDO("mysql:host={$host};dbname={$db_name}", $username, $password);
$pgsql_dsn = 'pgsql:host=localhost;dbname=fogado;user=fadmin;password=$1$JV6c.fJ6$PZLyMROI/Pct3ywWyNhgQ.';
$sqlite_dsn = 'sqlite:db/ifa.db';

# $dsn = $pgsql_dsn;
$dsn = $sqlite_dsn;

#
# PERL_DSN:
#
#   A fentivel összefüggésben kell a perl DBI kapcsolathoz tartozó adatforrást
#   beállítani -- ebből fogja a fogado-xls.cgi az adatbázis forrást kiolvasni.
#   Kompaktabb lenne PHP-val, de az még nem nagyon tud Excelt...
#   A PERL_DSN kezdetű sor PHP megjegyzésben található.
#
#   Példa:
#
#   PERL_DSN = "DBI:SQLite2:dbname=ifa.db"
#

/*
# PERL_DSN = "DBI:Pg:dbname=fog"
PERL_DSN = "DBI:SQLite:dbname=db/ifa.db"
*/

#
# $tanar_auth:
#
#   A tanárok authentikációjának módja a listájuk megtekintéséhez.
#
#   PAM:  Ha a helyi rendszeren van pam telepítve, akkor használható.
#         (Használatához telepíteni kell a php4-auth-pam modult.)
#   LDAP: Ha a tanárok LDAP-on keresztül akarnak authentikálni.
#         (Használatához ld. a $ldap változót!)
#   DB:   Ha a tanárok jelszója is ebben az adatbázisban van tárolva.
#
#   Példa:
#
#   $tanar_auth = 'DB';
#

$tanar_auth = 'LDAP';

#
# $ldap:
#
#   Tanári azonosításhoz az általános LDAP DN. Csak akkor van rá szükség, ha a
#   $tanar_auth változóban LDAP szerepel. Ekkor a 'base' dn-ben megkeresi a tanár
#   uid-et, a kapott dn-nel és a jelszóval próbál authentikálni.
#
#   Példa:
#
#   $ldap = array(
#       'host' => 'ldap://localhost',
#       'base' => 'uid=ou=People,dc=szepi,dc=hu',
#       'version' => 3
#   );
#

$ldap = array(
    'host' => 'ldap://localhost',
    'base' => 'ou=People,dc=szepi,dc=hu',
    'version' => 3
);

#
# $Fogado_tartam
# $Szuloi_tartam
#
#   Új időpont bejegyzésekor lehetőség van az alapértelmezett fogadási idő és az
#   alapértelmezett szülői értekezlet idejének beállítására.
#
#   Példa:
#
#   $Fogado_tartam = array (16,  0, 19,  0);
#   $Szuloi_tartam = array (17,  0, 17, 30);
#

$Fogado_tartam = array (16,  0, 19,  0);
$Szuloi_tartam = array (17,  0, 17, 30);

#
# $Kiir_tartam:
#
#   Az admin a select boxokban ezen órák közt tud választani.
#
#   Példa:
#
#   $Kiir_tartam = array (14, 20);
#

$Kiir_tartam = array (14, 20);

?>
