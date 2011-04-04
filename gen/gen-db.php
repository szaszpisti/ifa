#!/usr/bin/php
<?php
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
    $db->exec('
    CREATE TABLE Admin (
        id INTEGER PRIMARY KEY,
        datum DATE,
        kezd INTEGER,
        veg INTEGER,
        tartam INTEGER,
        valid_kezd TIMESTAMP WITHOUT TIME ZONE,
        valid_veg TIMESTAMP WITHOUT TIME ZONE
    )' );

    $db->exec('
    CREATE TABLE Ulog (
        id INTEGER PRIMARY KEY,
        ido TIMESTAMP WITHOUT TIME ZONE,
        uid INTEGER,
        host INET,
        log TEXT
    )' );

    $db->exec('
    CREATE TABLE Tanar (
        id INTEGER NOT NULL PRIMARY KEY,
        jelszo CHARACTER(32),
        emil TEXT,
        tnev TEXT
    )' );

    $db->exec('
    CREATE TABLE Osztaly (
        oszt TEXT NOT NULL PRIMARY KEY,
        onev TEXT,
        ofo INTEGER,
        FOREIGN KEY(ofo) REFERENCES Tanar(id) ON UPDATE CASCADE ON DELETE SET NULL
    )' );

    $db->exec('
    CREATE TABLE Diak_base (
        id INTEGER NOT NULL PRIMARY KEY,
        jelszo CHARACTER(32),
        dnev TEXT,
        oszt CHARACTER(4),
        FOREIGN KEY(oszt) REFERENCES Osztaly(oszt) ON UPDATE CASCADE
    )' );

    $db->exec('
    CREATE TABLE Fogado (
        fid INTEGER,
        tanar INTEGER,
        ido INTEGER NOT NULL,
        diak INTEGER,
        FOREIGN KEY(fid) REFERENCES Admin(id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY(tanar) REFERENCES Tanar(id) ON DELETE CASCADE ON UPDATE CASCADE
    )' );

    $db->exec('CREATE VIEW Diak AS
                    SELECT  D.id AS id,
                            D.jelszo AS jelszo,
                            D.dnev AS dnev,
                            D.oszt AS oszt,
                            O.onev AS onev,
                            O.ofo AS ofo,
                            T.tnev AS ofonev
                        FROM Osztaly AS O, Tanar AS T, Diak_base AS D
                        WHERE D.oszt = O.oszt AND O.ofo = T.id
                    UNION SELECT 0, "0cc175b9c0f1b6a831c399e269772661", "Admin", "", "", "", "" -- public'
               );

    $db->exec('INSERT INTO Admin VALUES (0, "2000-01-01", 192, 228, 2, "2000-01-01 08:00", "3000-01-01 12:00");' );

} catch (PDOException $e) {
    echo $e->getMessage();
}

$db->beginTransaction();
@array_walk($ins, 'insert');
$db->commit();

chmod ($IFA_db, 0660);
// chgrp ($IFA_db, 'www-data');

?>
