#!/usr/bin/python
# coding: utf-8

import sys, os.path, hashlib
from random import randint

reload(sys)
sys.setdefaultencoding( "utf-8" )

if len(sys.argv) != 2:
    print "Az első paraméterben megadott névvel létrehozott fájlba írja az SQL INSERT-eket."
    sys.exit()

outfile = sys.argv[1]
if os.path.isfile(outfile):
    print "Már létezik a fájl, nem merek bele írni: %s" % outfile
    sys.exit()

# A vezetékneveket beolvassuk az aVnev tömbbe
aVnev = open('csaladnev.txt').read().strip().split('\n')
nVnev = len(aVnev)

# A keresztneveket beolvassuk az aKnev tömbbe
aKnev = []
for nap in open('keresztnev.txt').readlines():
    try: aKnev += nap.strip().split(': ')[1].split()
    except: pass
nKnev = len(aKnev)

# Az azonosítók kezdőértékei
nTanar, nDiak = 10, 10000

md5 = lambda pw: hashlib.md5(pw).hexdigest()
tJelszo = md5('t');
dJelszo = md5('d');

nev = lambda: aVnev[randint(0, nVnev-1)] + ' ' + aKnev[randint(0, nKnev-1)]

osztalyok = open('OSZTALY').read().replace('\n', ';').split(';') # 'd10a;7. A;d09a;8. A;...'
# Minden második osztályazonosító
OSZTALY = dict( [ (osztalyok[i], osztalyok[i+1]) for i in range(0, len(osztalyok)-1, 2) ] )

osztalyOUT = ['']
diakOUT = ['']
tanarOUT = ['']

for oid, onev in OSZTALY.items():
    tanarOUT.append("INSERT INTO Tanar VALUES (%d, '%s', '', '%s');" % (nTanar, tJelszo, nev()))
    osztalyOUT.append("INSERT INTO Osztaly VALUES ('%s', '%s', %d);" % (oid, onev, nTanar))

    # minden osztályhoz felveszünk néhány diákot
    for i in range(randint(25, 35)):
        diakOUT.append("INSERT INTO Diak_base VALUES (%d, '%s', '%s', '%s');" % (nDiak, dJelszo, nev(), oid))
        nDiak += 1
    nTanar += 1

# még néhány tanárt hozzáadunk (eddig az osztályfőnökök vannak)
for i in range(randint(25, 35)):
    tanarOUT.append("INSERT INTO Tanar VALUES (%d, '%s', '', '%s');" % (nTanar, tJelszo, nev()))
    nTanar += 1

OUT = tanarOUT + osztalyOUT + diakOUT
open(outfile, 'w').write('\n'.join(OUT))

