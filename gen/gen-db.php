#!/usr/bin/php
<?php
// A script könyvtára; itt kell keresni a schema.sql-t.
$BASE = dirname(__FILE__);
$IFA_db = 'ifa.db';

if ($argc != 2) {
    print "Az INSERT-filet argumentumként kell megadni!\n";
    print "Létrehozza az aktuális könyvtárban a 'ifa.db' nevű sqlite adatbázist.\n";
    print "(Vagy ha más DSN van megadva, akkor azt.)\n";
    return;
}
$ins = file($argv[1]);

// A DSN-ben itt is meg lehet adni tetszőleges adatbázist.

$pgsql_dsn = array(
    'phptype'  => 'pgsql',
    'database' => 'fogado',
    'username' => 'fadmin',
    'password' => '$1$JV6c.fJ6$PZLyMROI/Pce3ywWyAhgQ.',
    'hostspec' => 'localhost',
);
// $pgsql_dsn = "pgsql:dbname=fogado;host=localhost", "fadmin", '$1$JV6c.fJ6$PZLyMROI/Pce3ywWyAhgQ.'

$sqlite_dsn = "sqlite:$IFA_db";

// $dsn = $pgsql_dsn;
$dsn = $sqlite_dsn;

try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $db->exec('PRAGMA foreign_keys = true;');
} catch (PDOException $e) {
    die($e->getMessage());
}

function insert($value, $key) {
    if (preg_match('/^$/', $value)) return;
    global $db;
    $db->exec($value);
}

try {
    $schema = file_get_contents($BASE . '/schema.sql');
    $db->exec($schema);

    $db->exec('INSERT INTO Admin VALUES (0, "2000-01-01", 192, 228, 2, "2000-01-01 08:00", "3000-01-01 12:00");' );

} catch (PDOException $e) {
    echo $e->getMessage();
}

$db->beginTransaction();
array_walk($ins, 'insert');
$db->commit();

chmod ($IFA_db, 0660);
// chgrp ($IFA_db, 'www-data');

?>
