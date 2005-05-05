<?

$db_host = 'localhost';
$db_name = 'fogado';
$db_user = 'fadmin';
$db_pass = '$1$JV6c.fJ6$PZLyMROI/Pct3ywWyNhgQ.';
$db_type = 'pgsql';

$host = 'www.szepi.hu';

// tanar_auth: a tanárok megnézhetik a listájukat,
//   ha van helyi pam, akkor az azonosítójuk kell a táblába,
//   ha nincs, akkor a jelszó.
// 'PAM', 'LDAP' vagy 'DB'
$tanar_auth = 'LDAP';
$ldap = array(
    'host' => 'ldap://localhost',
    'base' => 'uid=#USER#,ou=People,dc=szepi,dc=hu',
    'version' => 3
);


// A fogadóóra és szülõi értekezlet alapértelmezett idõtartama
$Fogado_tartam = array (16,  0, 19,  0);
$Szuloi_tartam = array (17,  0, 17, 30);

// A select boxokban ezen órák közt lehet választani
$Kiir_tartam = array (14, 20);

?>
