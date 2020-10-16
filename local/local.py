#!/usr/bin/env python3

"""
Elkészíti a "local.sql"-t, felhasználva a
- KIR-tanar.csv
- osztalyfonok.csv
- diak-jelszo.csv
fájlokat. Az első kettő a google könyvárban van,
a "diak-jelszo.csv"-t az uj-diak-jelszo.py generálja.
"""

import sys
base_dir = '/home/szaszi/g/'
sys.path.append(base_dir)

import hashlib
import utils
import os.path
import sys

LOCAL = 'local.sql'

# ha már van, akkor kilépünk
if os.path.isfile(LOCAL):
    print('Töröld a %s filet!' % LOCAL)
    sys.exit()

i = 1000
tanarok = []
tanar = {} # oid -> id összerendelés
for sor in open(base_dir + '%d/KIR-tanar.csv' % utils.tanev):
    i += 1
    oid, vnev, knev = sor.strip().split(',')
    tanar[oid] = i
    nev = vnev + ' ' + knev
    # kell az email
    email = 'szasz.imre@szeged.piarista.hu'
    email = utils.get_email(oid)
    tanarok.append("INSERT INTO 'Tanar' VALUES (%s, NULL, '%s', '%s');" % (i, email, nev))

osztalyok = []
for sor in open(base_dir + 'osztalyfonok.csv'):
    oszt, oid, nev, email = sor.strip().split(',')
    o = utils.Osztaly(oszt)
    if o.evfolyam > 12:
        continue
    osztalyok.append("INSERT INTO 'Osztaly' VALUES ('%s', '%s', %s);" % (oszt, o.osztaly, tanar[oid]))

i = 2000
diakok = []
for sor in open('diak-jelszo.csv'):
    i += 1
    oid, jelszo, nev, oszt = sor.strip().split(',')
    digest = hashlib.sha256(jelszo.encode('utf-8')).hexdigest()
    diakok.append("INSERT INTO 'Diak_base' VALUES (%s, '%s', '%s', '%s');" % (i, digest, nev, oszt))


with open(LOCAL, 'w') as out:
    print('\n'.join(tanarok), file=out)
    print(file=out)
    print('\n'.join(osztalyok), file=out)
    print(file=out)
    print('\n'.join(diakok), file=out)

print('./gen-db.php', LOCAL)
