<?

$db_host = 'localhost';
$db_name = 'fogado';
$db_user = 'fadmin';
$db_pass = '$1$JV6c.fJ6$PZLyMROI/Pct3ywWyNhgQ.';
$db_type = 'pgsql';

$host = 'www.szepi.hu';

// tanar_auth: a tan�rok megn�zhetik a list�jukat,
//   ha van helyi pam, akkor az azonos�t�juk kell a t�bl�ba,
//   ha nincs, akkor a jelsz�.
// 'PAM', 'LDAP' vagy 'DB'
$tanar_auth = 'LDAP';
$ldap = array(
    'host' => 'ldap://localhost',
    'base' => 'uid=#USER#,ou=People,dc=szepi,dc=hu',
    'version' => 3
);


// A fogad��ra �s sz�l�i �rtekezlet alap�rtelmezett id�tartama
$Fogado_tartam = array (16,  0, 19,  0);
$Szuloi_tartam = array (17,  0, 17, 30);

// A select boxokban ezen �r�k k�zt lehet v�lasztani
$Kiir_tartam = array (14, 20);

?>
