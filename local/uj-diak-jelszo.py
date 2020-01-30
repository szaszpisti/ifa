#!/usr/bin/env python3

"""
Elkészíti az aktuális jelszó fájlokat:
- diak-jelszo.csv
- ifa-jelszo.csv - a titkárságnak
A diak-jelszo.csv alapján fogjuk később a local.sql-t csinálni.
"""

from subprocess import Popen, PIPE
import sys
import os
import locale
import datetime
import utils

locale.setlocale(locale.LC_ALL, 'hu_HU.UTF-8')

def main():

    date = datetime.datetime.now().strftime('%F')
    regifile = utils.diak_jelszo[:-4] + '-' + date + '.csv' # majd átnevezzük: diak-jelszo.csv -> diak-jelszo-2019-10-09.csv

    # Ha van már ilyen fájl (ma már játszottunk), akkor kilép
    if os.path.isfile(regifile):
        print('Már van:', regifile)
        sys.exit()

    # a KIR-ben létező összes oid
    oids = [sor.split(',')[0] for sor in open(utils.KIR)]

    diak = set() # a régi diákok oid-jeit gyújtjük ide
    osztalyok = {} # {'12A': [[1234, 'jelszo', 'Pumpa Pál', 'd18b', '12. A'], ['1234', ...], ...], '12B': [[... ], ... ] }

    for sor in open(utils.diak_jelszo):
        d = sor.strip().split(',')
        oid, jelszo, nev, osztaly = tuple(d)

        # az elment diákokat nem őrizgetjük
        if not oid in oids:
            continue

        o = utils.Osztaly(osztaly, upper=True)
        signal = o.signal

        diak.add(oid)
        if not signal in osztalyok:
            osztalyok[signal] = []
        osztalyok[signal].append([oid, jelszo, nev, o.oszt, o.osztaly])

    # végignézzük a KIR-t; ha még nem találkoztunk az oid-del, generálunk neki jelszót és beillesztjük
    for sor in open(utils.KIR):
        d = sor.strip().split(',')
        oid, vnev, knev, osztaly = tuple(d)

        # Ha már van ez a diák, továbblépünk
        if oid in diak:
            continue

        nev = vnev + ' ' + knev
        o = utils.Osztaly(osztaly, upper=True)
        signal = o.signal

        # egyszerű jelszót generálunk
        proc = Popen(['gpw', '1'], stdout=PIPE)
        jelszo = proc.stdout.readline().decode().strip()

        if not signal in osztalyok:
            osztalyok[signal] = []
        osztalyok[signal].append([oid, jelszo, nev, o.oszt, o.osztaly])

    if not osztalyok:
        print('Nincs változás. (KIR rendben?)')
        sys.exit()

    # az eddigi diak-jelszo-t átnevezzük, archiváljuk a mai dátumra
    os.rename(utils.diak_jelszo, regifile)
    out = open(utils.diak_jelszo, 'w')

    # kati: a titkárságra is kell egy excelben megnyitható állomány - a telefonos segítséghez
    kati = open('ifa-jelszo.csv', 'w', encoding='latin1') # Addig van latin1-ben nyitva, amíg az excel BOM-ot kiírjuk
    kati.write('\xEF\xBB\xBF')
    kati.close()
    kati = open('ifa-jelszo.csv', 'a')
    for signal in sorted(osztalyok):
        nevsor = osztalyok[signal]
        nevsor.sort(key=lambda d: locale.strxfrm(d[2]))
        for d in nevsor:
            print(','.join(d[:-1]), file=out)
            print(';'.join([d[1], d[2], d[4]]), file=kati)

    os.chmod(utils.diak_jelszo, 0o400)

main()

