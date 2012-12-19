#!/usr/bin/python
# coding: utf-8

import sys, hashlib
import sys

try:
    userFile = sys.argv[1]
    fUser = open(userFile)

except IndexError:
    print '''
Az első paraméterként megadott (userFile) állományból generál insert sorokat
az adatbázishoz, Létrehoz egy userFile.pw fájlt a kiosztandó jelszókkal és egy
userFile.insert fájlt, amit fel lehet használni a gen-db.php programhoz.

A userFile felépítése:
Tanar Neve;tid
===
oid;Osztaly Neve;tid
===
Diak Neve;did;oid

Példa:
# az üres vagy # kezdetű sorokat nem veszi figyelembe
Monoton Manó;117
===
d05a;2. A;117
===
Pumpa Pál;32;d05a
'''
    sys.exit(1)

except IOError:
    print "Nem létezik a fájl: %s" % userFile

def Exit(msg = ''):
    print 'Hiba: ', msg
    sys.exit(1)

md5 = lambda pw: hashlib.md5(pw).hexdigest()

# Based on PHP code of Ell Gree <ellgree@gmx.net>, http://unix.freshmeat.net/projects/gen_password/
def gen_password(p='', l=8, f=4):
    import string
    from re import sub
    from random import randint

    d = { 'a':'ntrsldicmzp', 'b':'euloayribsj', 'c':'oheaktirulc',
          'd':'eiorasydlun', 'e':'nrdsaltevcm', 'f':'ioreafltuyc',
          'g':'aeohrilunsg', 'h':'eiaotruykms', 'i':'ntscmledorg',
          'j':'ueoairhjklm', 'k':'eiyonashlus', 'l':'eoiyaldsfut',
          'm':'eaoipsuybmn', 'n':'goeditscayl', 'o':'fnrzmwtovls',
          'p':'earolipuths', 'q':'uuuuaecdfok', 'r':'eoiastydgnm',
          's':'eothisakpuc', 't':'hoeiarzsuly', 'u':'trsnlpgecim',
          'v':'eiaosnykrlu', 'w':'aiheonrsldw', 'x':'ptciaeuohnq',
          'y':'oesitabpmwc', 'z':'eaiozlryhmt' }

    a = string.ascii_lowercase
    l %= 50
    f %= 11

    p = str.lower(sub('[^a-zA-Z]', '', p[:l-1]))

    if p == '':
        p = a[randint(0, len(a)-1)]

    while len(p) < l:
        ff = f
        i = 1
        while i > 0:
            k = d[p[-1]][randint(0, ff%11)]
            i = p.count(p[-1] + k)
            ff += 1
            if ff > 10: break;
        p += k
    return p


fUser = open(userFile)

jelszo = gen_password()
Insert = "INSERT INTO %s (id, jelszo, %s) VALUES (%s, '%s', '%s');"

# Az Admint is berakjuk de jelszót külön kell neki adni!
INSERT = [Insert % ('Diak', 'dnev', '0', md5(jelszo), 'Admin'), '']

OUT = ['Admin;%s' % jelszo, '===']

List = fUser.read().split('===\n')

# Tanárok felsorolása
listTanar = {}
for sor in List[0].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    nev, uid = sor.split(';')
    if listTanar.has_key(uid): Exit('Dupla tanár azonosító: %s' % uid)

    listTanar[uid] = nev
    jelszo = gen_password()

    OUT.append('%s;%s' % (nev, jelszo))
    INSERT.append(Insert % ('Tanar', 'tnev', uid, md5(jelszo), nev))

OUT.append('===')
INSERT.append('')

listOsztaly = {}
# Osztályok felsorolása
for sor in List[1].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    oszt, onev, ofonev = sor.split(';')
    if listOsztaly.has_key(oszt): Exit('Dupla osztály azonosító: %s' % oszt)
    if not listTanar.has_key(ofonev): Exit('%s osztály főnöke (%s) nem szerepel a tanárok közt!' % (oszt, ofonev))

    listOsztaly[oszt] = "INSERT INTO Diak (id, jelszo, dnev, oszt, onev, ofo, ofonev) VALUES (" +\
                        "%s, '%s', '%s', '" + oszt + "', '" + onev + "', '" + ofonev + "');"


# Tanárok felsorolása
listDiak = {}
for sor in List[2].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    nev, uid, oszt = sor.split(';')
    if listDiak.has_key(uid): Exit('Dupla diák azonosító: %s' % uid)
    if not listOsztaly.has_key(oszt): Exit('%s osztálya (%s) nem szerepel az osztályok közt!' % (nev, oszt));

    jelszo = gen_password()

    OUT.append('%s;%s' % (nev, jelszo))
    INSERT.append(listOsztaly[oszt] % (uid, md5(jelszo), nev))


open(userFile + '.insert', 'w').write('\n'.join(INSERT))
open(userFile + '.pw', 'w').write('\n'.join(OUT))

