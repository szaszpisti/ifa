#!/usr/bin/env python3

"""
Elkészíti az aktuális jelszó fájlokat:
- diak-jelszo.csv
  * az előző diak-jelszo.csv állományról mentést készít
- ifa-jelszo.csv - a titkárságnak
A diak-jelszo.csv alapján fogjuk később a local.sql-t csinálni.
"""

import sys
base_dir = '/home/szaszi/g/'
sys.path.append(base_dir)

from subprocess import Popen, PIPE
import os
import locale
import datetime
import utils
import configuration

diak_jelszo = 'diak-jelszo.csv'
ifa_jelszo = 'ifa-jelszo.csv'
config = configuration.config

locale.setlocale(locale.LC_ALL, 'hu_HU.UTF-8')

def main():
    Users = utils.Users()

    date = datetime.datetime.now().strftime('%F')
    regifile = diak_jelszo[:-4] + '-' + date + '.csv' # majd átnevezzük: diak-jelszo.csv -> diak-jelszo-2019-10-09.csv

    # Ha van már ilyen fájl (ma már játszottunk), akkor kilép
    if os.path.isfile(regifile):
        print('Már van:', regifile)
        sys.exit()

    # a KIR-ben létező összes oid
    oids = Users.oids

    diak = set() # a régi diákok oid-jeit gyújtjük ide
    osztalyok = {utils.Osztaly(oszt).signal: [] for oszt in Users.osztalyok} # {'12A': [[1234, 'jelszo', 'Pumpa Pál', 'd18b', '12. A'], ['1234', ...], ...], '12B': [[... ], ... ] }

    # Ezek vannak most, ezen végigmegyünk:
    for sor in open(diak_jelszo):
        d = sor.strip().split(',')
        oid, jelszo, nev, oszt = tuple(d)

        o = utils.Osztaly(oszt, upper=True)
        signal = o.signal

        # az elment diákokat nem őrizgetjük
        if not oid in oids:
            continue

        if oszt != oids[oid]['ou']:
            print(sor.strip(), '=>', oids[oid]['ou'])

        diak.add(oid)
        osztalyok[signal].append([oid, jelszo, nev, o.oszt, o.osztaly])

    # Végignézzük a KIR-t; ha még nem találkoztunk az oid-del, generálunk neki jelszót és beillesztjük
    # Ha ugyan találkoztunk az id-del, de nem jó az osztály, jelezzük. Ezt kézzel kell javítani.

    for sor in open('%s/%s/%s' % (base_dir, configuration.stanev, config['kir'])):
        d = sor.strip().split(',')
        oid, vnev, knev, osztaly = tuple(d)

        # if oid in diak:
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
    os.rename(diak_jelszo, regifile)
    out = open(diak_jelszo, 'w')

    # kati: a titkárságra is kell egy excelben megnyitható állomány - a telefonos segítséghez
    kati = open(ifa_jelszo, 'w', encoding='latin1') # Addig van latin1-ben nyitva, amíg az excel BOM-ot kiírjuk
    kati.write('\xEF\xBB\xBF')
    kati.close()
    with open(ifa_jelszo, 'a') as kati:
        for signal in sorted(osztalyok):
            nevsor = osztalyok[signal]
            nevsor.sort(key=lambda d: locale.strxfrm(d[2]))
            for d in nevsor:
                print(','.join(d[:-1]), file=out)
                print(';'.join([d[1], d[2], d[4]]), file=kati)

    print('scp', ifa_jelszo, 'boxer:"/pub/titkarsag/fogadó\ óra"')
    os.chmod(diak_jelszo, 0o400)

main()

