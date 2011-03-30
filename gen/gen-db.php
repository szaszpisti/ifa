#!/usr/bin/php
<?php
require_once('DB.php');
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

$sqlite_dsn = array(
    'phptype'  => 'sqlite3',
    'database' => $IFA_db,
    'mode'     => '0644',
);

// $dsn = $pgsql_dsn;
$dsn = $sqlite_dsn;

$options = array(
    'debug'       => 2,
    'portability' => DB_PORTABILITY_ALL,
);

$db =& DB::connect($dsn, $options);
if (DB::isError($db)) {
    die($db->getMessage());
}

function insert($value, $key) {
    if (preg_match('/^$/', $value)) return;
    global $db;
    $res =& $db->query($value);
    if (DB::isError($res)) { die($res->getMessage()); }
}

$res =& $db->query('
CREATE TABLE Admin (
    id serial NOT NULL PRIMARY KEY,
    datum date,
    kezd integer,
    veg integer,
    tartam integer,
    valid_kezd timestamp without time zone,
    valid_veg timestamp without time zone
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE Ulog (
    id serial NOT NULL PRIMARY KEY,
    ido timestamp without time zone,
    uid integer,
    host inet,
    log text
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE Diak_base (
    id integer NOT NULL PRIMARY KEY,
    jelszo character(32),
    dnev text,
    oszt character(4),
    FOREIGN KEY(oszt) REFERENCES Osztaly(oszt)
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE Tanar (
    id integer NOT NULL PRIMARY KEY,
    jelszo character(32),
    emil text,
    tnev text
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE Osztaly (
    oszt text NOT NULL PRIMARY KEY,
    onev text,
    ofo integer
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE Fogado (
    fid integer NOT NULL,
    tanar integer,
    ido integer,
    diak integer,
    FOREIGN KEY(fid) REFERENCES Admin(id),
    FOREIGN KEY(tanar) REFERENCES Tanar(id),
    FOREIGN KEY(diak) REFERENCES Diak(id)
)' );

$res =& $db->query('CREATE VIEW Diak AS
                SELECT  D.id AS id,
                        D.jelszo AS jelszo,
                        D.dnev AS dnev,
                        D.oszt AS oszt,
                        O.onev AS onev,
                        O.ofo AS ofo,
                        T.tnev AS ofonev
                    FROM Osztaly AS O, Tanar AS T, Diak_base AS D
                    WHERE D.oszt = O.oszt AND O.ofo = T.id
                UNION
                SELECT 0, "b5c6ccf9dade76f27e48a96599855083", "Admin", "", "", "", "";' );

if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('CREATE TABLE ulog_id_seq (id INTEGER UNSIGNED PRIMARY KEY);' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('CREATE TABLE admin_id_seq (id INTEGER UNSIGNED PRIMARY KEY);' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('CREATE TRIGGER admin_id_seq_cleanup AFTER INSERT ON admin_id_seq
   BEGIN DELETE FROM admin_id_seq WHERE id<LAST_INSERT_ROWID(); END;' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('CREATE TRIGGER ulog_id_seq_cleanup AFTER INSERT ON ulog_id_seq
   BEGIN DELETE FROM ulog_id_seq WHERE id<LAST_INSERT_ROWID(); END;' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('INSERT INTO Admin VALUES (0, "2000-01-01", 192, 228, 2, "2000-01-01 08:00:00", "3000-01-01 12:00:00");' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('INSERT INTO admin_id_seq VALUES (1)' );
if (DB::isError($res)) { die($res->getMessage()); }

@array_walk($ins, 'insert');

chmod ($IFA_db, 0660);
chgrp ($IFA_db, 'www-data');

?>
