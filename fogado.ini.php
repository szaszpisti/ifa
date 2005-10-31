<?
# [fogado.ini.php]
# Az állományban beállítható változók

#
# $dsn:
#
#   Az fogadóóra adatbázisának adatforrás neve.
#
#   Megadható bárhogyan, akár a példától eltérõ RDBMS használata is. Lényeg, hogy
#   a végén legyen egy létezõ $dsn változó -- a BEAR DB::connect által várt
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
#       'database' => 'fogado.db',
#       'mode'     => '0644',
#   );
#

$pgsql_dsn = array(
	'phptype'  => 'pgsql',
	'username' => 'fadmin',
	'password' => '$1$JV6c.fJ6$PZLyMROI/Pct3ywWyNhgQ.',
	'hostspec' => 'localhost',
	'database' => 'fog',
);

$sqlite_dsn = array(
	'phptype'  => 'sqlite',
	'database' => 'fogado.db',
	'mode'     => '0644',
);

# $dsn = $pgsql_dsn;
$dsn = $sqlite_dsn;

# $options:
#
#    a PEAR DB absztrakt adatbázis kapcsolat tulajdonságai.
#

$options = array(
	'debug'       => 2,
	'portability' => DB_PORTABILITY_ALL,
);

#
# PERL_DSN:
#
#   A fentivel összefüggésben kell a perl DBI kapcsolathoz tartozó adatforrást
#   beállítani -- ebbõl fogja a fogado-xls.pl az adatbázis forrást kiolvasni.
#   Kompaktabb lenne PHP-val, de az még nem nagyon tud Excelt...
#   A PERL_DSN kezdetû sor PHP megjegyzésben található.
#
#   Példa:
#
#   PERL_DSN = "DBI:SQLite2:dbname=fogado.db"
#

/*
# PERL_DSN = "DBI:Pg:dbname=fog"
PERL_DSN = "DBI:SQLite2:dbname=fogado.db"
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
#   $tanar_auth változóban LDAP szerepel. Ekkor a #USER# helyére fogja
#   a program beírni az tanári azonosítót.
#
#   Példa:
#
#   $ldap = array(
#       'host' => 'ldap://localhost',
#       'base' => 'uid=#USER#,ou=People,dc=szepi,dc=hu',
#       'version' => 3
#   );
#

$ldap = array(
	'host' => 'ldap://localhost',
	'base' => 'uid=#USER#,ou=People,dc=szepi,dc=hu',
	'version' => 3
);

#
# $Fogado_tartam
# $Szuloi_tartam
#
#   Új idõpont bejegyzésekor lehetõség van az alapértelmezett fogadási idõ és az
#   alapértelmezett szülõi értekezlet idejének beállítására.
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
