<?
# [fogado.ini.php]
# Az �llom�nyban be�ll�that� v�ltoz�k

#
# $dsn:
#
#   Az fogad��ra adatb�zis�nak adatforr�s neve.
#
#   Megadhat� b�rhogyan, ak�r a p�ld�t�l elt�r� RDBMS haszn�lata is. L�nyeg, hogy
#   a v�g�n legyen egy l�tez� $dsn v�ltoz� -- a BEAR DB::connect �ltal v�rt
#   m�don: ak�r sz�veges, ak�r t�mb form�ban.
#
#   A DSN (Data Source Name) r�szletesebb le�r�s�ra ld.
#   http://pear.activeventure.com/package/package.database.db.intro-dsn.html
#   http://pear.activeventure.com/package/package.database.db.intro-connect.html
#
#   P�lda:
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
#    a PEAR DB absztrakt adatb�zis kapcsolat tulajdons�gai.
#

$options = array(
	'debug'       => 2,
	'portability' => DB_PORTABILITY_ALL,
);

#
# PERL_DSN:
#
#   A fentivel �sszef�gg�sben kell a perl DBI kapcsolathoz tartoz� adatforr�st
#   be�ll�tani -- ebb�l fogja a fogado-xls.pl az adatb�zis forr�st kiolvasni.
#   Kompaktabb lenne PHP-val, de az m�g nem nagyon tud Excelt...
#   A PERL_DSN kezdet� sor PHP megjegyz�sben tal�lhat�.
#
#   P�lda:
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
#   A tan�rok authentik�ci�j�nak m�dja a list�juk megtekint�s�hez.
#
#   PAM:  Ha a helyi rendszeren van pam telep�tve, akkor haszn�lhat�.
#         (Haszn�lat�hoz telep�teni kell a php4-auth-pam modult.)
#   LDAP: Ha a tan�rok LDAP-on kereszt�l akarnak authentik�lni.
#         (Haszn�lat�hoz ld. a $ldap v�ltoz�t!)
#   DB:   Ha a tan�rok jelsz�ja is ebben az adatb�zisban van t�rolva.
#
#   P�lda:
#
#   $tanar_auth = 'DB';
#

$tanar_auth = 'LDAP';

#
# $ldap:
#
#   Tan�ri azonos�t�shoz az �ltal�nos LDAP DN. Csak akkor van r� sz�ks�g, ha a
#   $tanar_auth v�ltoz�ban LDAP szerepel. Ekkor a #USER# hely�re fogja
#   a program be�rni az tan�ri azonos�t�t.
#
#   P�lda:
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
#   �j id�pont bejegyz�sekor lehet�s�g van az alap�rtelmezett fogad�si id� �s az
#   alap�rtelmezett sz�l�i �rtekezlet idej�nek be�ll�t�s�ra.
#
#   P�lda:
#
#   $Fogado_tartam = array (16,  0, 19,  0);
#   $Szuloi_tartam = array (17,  0, 17, 30);
#

$Fogado_tartam = array (16,  0, 19,  0);
$Szuloi_tartam = array (17,  0, 17, 30);

#
# $Kiir_tartam:
#
#   Az admin a select boxokban ezen �r�k k�zt tud v�lasztani.
#
#   P�lda:
#
#   $Kiir_tartam = array (14, 20);
#

$Kiir_tartam = array (14, 20);

?>
