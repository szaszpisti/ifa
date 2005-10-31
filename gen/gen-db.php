#!/usr/bin/php
<?php
require_once('DB.php');

if ($argc != 2) {
	print "Az INSERT-filet argumentumként kell megadni!\n";
	print "Létrehozza az aktuális könyvtárban a 'fogado.db' nevû sqlite adatbázist.\n";
	print "(Vagy ha más DSN van megadva, akkor azt.)\n";
	return;
}
$ins = file($argv[1]);

// A DSN-ben itt is meg lehet adni tetszõleges adatbázist.

$pgsql_dsn = array(
    'phptype'  => 'pgsql',
    'database' => 'fog',
    'username' => 'fadmin',
    'password' => '$1$JV6c.fJ6$PZLyMROI/Pce3ywWyAhgQ.',
    'hostspec' => 'localhost',
);

$sqlite_dsn = array(
	'phptype'  => 'sqlite',
	'database' => 'fogado.db',
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
CREATE TABLE fogado_admin (
    id serial NOT NULL,
    datum date,
    kezd integer,
    veg integer,
    tartam integer,
    valid_kezd timestamp without time zone,
    valid_veg timestamp without time zone
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE ulog (
    id serial NOT NULL,
    ido timestamp without time zone,
    uid integer,
    host inet,
    log text
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE diak (
    id integer NOT NULL,
    jelszo character(32),
    dnev text,
    oszt character(4),
    onev character(5),
    ofo integer,
    ofonev text
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE tanar (
    id integer NOT NULL,
    jelszo character(32),
    emil text,
    tnev text
)' );
if (DB::isError($res)) { die($res->getMessage()); }

$res =& $db->query('
CREATE TABLE fogado (
    fid integer,
    tanar integer,
    ido integer,
    diak integer
)' );
if (DB::isError($res)) { die($res->getMessage()); }

@array_walk($ins, 'insert');

?>
