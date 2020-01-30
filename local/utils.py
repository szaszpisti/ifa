#!/usr/bin/env python3

"""
base_dir: innen olvassuk be a KIR-diak.csv és az osztalyfonok.csv fájlokat.
KIR, diak_jelszo: fájlnevek
Osztaly: az osztálynév tulajdonságai
"""

import re
import datetime
from socket import gethostname

tanev = (datetime.date.today() - datetime.timedelta(days=180)).year

hostname = gethostname()
base_dir = '/home/szaszi/iskola/google/'
if hostname == 'fules':
    base_dir = '/home/szaszi/p/iskola/google/'

KIR = base_dir + '%d/KIR-diak.csv' % tanev
diak_jelszo = 'diak-jelszo.csv'

class Osztaly:
    """
    Az oszt (d23a) vagy osztaly (8. A) paraméterből idei osztályneveket csinál.
    - oszt: d23a vagy 8a, 9. B,...
    - sep ('. '): az évfolyam és a betűjel közé teszi
    - upper (True): kis- vagy nagybetűs legyen a betűjel?
    Pl.
    get_osztaly("d23a", upper=False) -> "9. a"
    get_osztaly("d23a", sep='', upper=False) -> "9a"
    get_osztaly("d23a", sep='.') -> "9.A"
    get_osztaly("9. a", sep='.') -> "9.A"
    """
    def __init__(self, oszt, sep='. ', upper=True):
        reOsztaly = re.compile(r'(\d+)\.? *(\w)')
        reOszt = re.compile('d(\d\d)(\w)')

        m = reOszt.match(oszt)
        if m:
            vegzes, ab = int(m.group(1)), m.group(2)
            evfolyam = tanev - 2000 + 13 - vegzes
            self.oszt = oszt
        else:
            m = reOsztaly.match(oszt)
            if m:
                evfolyam, ab = int(m.group(1)), m.group(2)
                vegzes = tanev - 2000 + 13 - evfolyam
                self.oszt = 'd%d%s' % (vegzes, ab.lower())
            else:
                return False

        if upper:
            self.ab = ab.upper()
        else:
            self.ab = ab.lower()

        self.evfolyam = evfolyam
        self.osztaly = '%s%s%s' % (evfolyam, sep, self.ab)
        self.signal = '%02d%c' % (evfolyam, self.ab) # 09A, 10A - a sorbarendezés végett

