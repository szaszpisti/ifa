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

# A keresztneveket az aKnev-be
aKnev = []
for nap in open('keresztnev.txt').readlines():
    try: aKnev += nap.strip().split(': ')[1].split()
    except: pass
nKnev = len(aKnev)

# Az azonosítók kezdőértékei
nTanar, nDiak = 10, 10000

def sha(pw):
    return hashlib.sha256(pw.encode('utf-8')).hexdigest()

tJelszo = sha('t');
dJelszo = sha('d');

nev = lambda: aVnev[randint(0, nVnev-1)] + ' ' + aKnev[randint(0, nKnev-1)]

osztalyok = open('OSZTALY').read().replace('\n', ';').split(';') # 'd10a;7. A;....'
# Minden második osztályazonosító
OSZTALY = dict( [ (osztalyok[i], osztalyok[i+1]) for i in range(0, len(osztalyok)-1, 2) ] )

tanarOUT = []
osztalyOUT = ['']
diakOUT = ['']

tFormat = "INSERT INTO Tanar (id, jelszo, tnev) VALUES (%d, '%s', '%s');"
for oid, onev in OSZTALY.items():
    tanarOUT.append(tFormat % (nTanar, tJelszo, nev()))
    osztalyOUT.append("INSERT INTO Osztaly (oszt, onev, ofo) VALUES ('%s', '%s', %d);" % (oid, onev, nTanar))

    # minden osztályhoz felveszünk néhány diákot
    for i in range(randint(25, 35)):
        diakOUT.append("INSERT INTO Diak_base (id, jelszo, dnev, oszt) VALUES (%d, '%s', '%s', '%s');" % (nDiak, dJelszo, nev(), oid))
        nDiak += 1
    nTanar += 1

# még néhány tanárt hozzáadunk (eddig az osztályfőnökök vannak)
for i in range(randint(25, 35)):
    tanarOUT.append(tFormat % (nTanar, tJelszo, nev()))
    nTanar += 1

OUT = tanarOUT + osztalyOUT + diakOUT
open(outfile, 'w').write('\n'.join(OUT))

