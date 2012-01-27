
CREATE TABLE Admin (
        id INTEGER PRIMARY KEY,
        datum DATE,
        kezd INTEGER,
        veg INTEGER,
        tartam INTEGER,
        valid_kezd TIMESTAMP WITHOUT TIME ZONE,
        valid_veg TIMESTAMP WITHOUT TIME ZONE
    );
CREATE TABLE Ulog (
        id INTEGER PRIMARY KEY,
        ido TIMESTAMP WITHOUT TIME ZONE,
        uid INTEGER,
        host INET,
        log TEXT
    );
CREATE TABLE Tanar (
        id INTEGER NOT NULL PRIMARY KEY,
        jelszo CHARACTER(32),
        emil TEXT,
        tnev TEXT
    );
CREATE TABLE Osztaly (
        oszt TEXT NOT NULL PRIMARY KEY,
        onev TEXT,
        ofo INTEGER,
        FOREIGN KEY(ofo) REFERENCES Tanar(id) ON UPDATE CASCADE ON DELETE SET NULL
    );
CREATE TABLE Diak_base (
        id INTEGER NOT NULL PRIMARY KEY,
        jelszo CHARACTER(32),
        dnev TEXT,
        oszt CHARACTER(4),
        FOREIGN KEY(oszt) REFERENCES Osztaly(oszt) ON UPDATE CASCADE
    );
CREATE TABLE Fogado (
        fid INTEGER,
        tanar INTEGER,
        ido INTEGER NOT NULL,
        diak INTEGER,
        FOREIGN KEY(fid) REFERENCES Admin(id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY(tanar) REFERENCES Tanar(id) ON DELETE CASCADE ON UPDATE CASCADE
    );
CREATE VIEW Diak AS
                    SELECT  D.id AS id,
                            D.jelszo AS jelszo,
                            D.dnev AS dnev,
                            D.oszt AS oszt,
                            O.onev AS onev,
                            O.ofo AS ofo,
                            T.tnev AS ofonev
                        FROM Osztaly AS O, Tanar AS T, Diak_base AS D
                        WHERE D.oszt = O.oszt AND O.ofo = T.id
                    UNION SELECT 0, "0cc175b9c0f1b6a831c399e269772661", "Admin", "", "", "", "" -- public;

CREATE VIEW Ip as SELECT COUNT(host) AS num, host FROM Ulog WHERE ido > (SELECT MAX(valid_kezd) FROM admin) GROUP BY host;

