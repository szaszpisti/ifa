#!/usr/bin/python3

# Összehasonlítja a diak_jelszo fájl aktuális névsorát
# az ifa.db névsorával.

import sqlite3
import utils
import hashlib

DB = '../db/ifa.db'

conn = sqlite3.connect(DB)
cur = conn.cursor()

diak_regi = {} # {'d12a-Pumpa Pál': 2012, ... }
q = "SELECT oszt || '-' || dnev, id FROM Diak_base"
for row in cur.execute(q):
    nev, diak_id = row
    diak_regi[nev] = diak_id

# Az aktuális diákok adatai
diak_aktualis = {} # {'d12a-Pumpa Pál': 'titok', ... }
for row in open(utils.diak_jelszo):
    oid, jelszo, nev, oszt = row.strip().split(',')
    diak_aktualis[oszt + '-' + nev] = {'nev': nev, 'oszt': oszt, 'jelszo': jelszo}

A = set(diak_regi)
B = set(diak_aktualis)

print('PRAGMA foreign_keys=ON;')
# Ezek csak az SQL-ben vannak, törölhetők
for nev in A-B:
    print('DELETE FROM Diak_base WHERE id=%s; -- %s' % (diak_regi[nev], nev))

print()
max_id = max(diak_regi.values())
i = max_id
# Az új diákokat föl kell venni
for nev in B-A:
    i += 1
    d = diak_aktualis[nev]
    digest = hashlib.sha256(d['jelszo'].encode('utf-8')).hexdigest()
    print("INSERT INTO 'Diak_base' VALUES (%s, '%s', '%s', '%s');" % (i, digest, d['nev'], d['oszt']))

print('sqlite3', DB)

