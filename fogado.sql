
select e.esz, onev, e.enev from ember as e,osztaly_view as o where tip='d' and e.oszt=o.oszt and e.oszt in (select oszt from osztaly) order by onev, e.enev;

DROP TABLE Fogado_admin;
CREATE TABLE Fogado_admin (
	id SERIAL NOT NULL PRIMARY KEY,
	datum DATE,
	kezd INTEGER,
	veg INTEGER,
	tartam INTEGER
);
INSERT INTO Fogado_admin VALUES (1, '2004-04-03', 160, 200, 2);


DROP TABLE Fogado;
CREATE TABLE Fogado (
	fid INTEGER REFERENCES Fogado_admin (id),
	tanar INTEGER REFERENCES Tanar (id),
	ido INTEGER,
	diak INTEGER
);

-- nincsenek öt percek
INSERT INTO Fogado VALUES (1, 50, '164', NULL);
INSERT INTO Fogado VALUES (1, 50, '166', NULL);
INSERT INTO Fogado VALUES (1, 50, '168', NULL);
INSERT INTO Fogado VALUES (1, 50, '170', NULL);
INSERT INTO Fogado VALUES (1, 50, '172', NULL);
INSERT INTO Fogado VALUES (1, 50, '174', 376);
INSERT INTO Fogado VALUES (1, 50, '176', -1);
INSERT INTO Fogado VALUES (1, 50, '178', 0);
INSERT INTO Fogado VALUES (1, 50, '180', -1);
INSERT INTO Fogado VALUES (1, 50, '182', -1);
INSERT INTO Fogado VALUES (1, 50, '184', 0);
INSERT INTO Fogado VALUES (1, 50, '186', -2);
INSERT INTO Fogado VALUES (1, 50, '188', -2);
INSERT INTO Fogado VALUES (1, 50, '190', NULL);
INSERT INTO Fogado VALUES (1, 50, '192', NULL);
INSERT INTO Fogado VALUES (1, 50, '194', 375);
INSERT INTO Fogado VALUES (1, 50, '196', -1);

-- vannak öt percek
INSERT INTO Fogado VALUES (1, 6, '164', NULL);
INSERT INTO Fogado VALUES (1, 6, '165', NULL);
INSERT INTO Fogado VALUES (1, 6, '166', NULL);
INSERT INTO Fogado VALUES (1, 6, '167', NULL);
INSERT INTO Fogado VALUES (1, 6, '168', NULL);
INSERT INTO Fogado VALUES (1, 6, '169', 375);
INSERT INTO Fogado VALUES (1, 6, '170', -1);
INSERT INTO Fogado VALUES (1, 6, '171', 0);
INSERT INTO Fogado VALUES (1, 6, '172', -1);
INSERT INTO Fogado VALUES (1, 6, '173', -1);
INSERT INTO Fogado VALUES (1, 6, '174', -2);
INSERT INTO Fogado VALUES (1, 6, '175', -2);
INSERT INTO Fogado VALUES (1, 6, '176', NULL);
INSERT INTO Fogado VALUES (1, 6, '177', NULL);
INSERT INTO Fogado VALUES (1, 6, '178', 275);
INSERT INTO Fogado VALUES (1, 6, '179', -1);
INSERT INTO Fogado VALUES (1, 6, '180', -1);
INSERT INTO Fogado VALUES (1, 6, '181', -1);
INSERT INTO Fogado VALUES (1, 6, '182', -1);
INSERT INTO Fogado VALUES (1, 6, '183', -1);
INSERT INTO Fogado VALUES (1, 6, '184', 0);
INSERT INTO Fogado VALUES (1, 6, '185', -1);
INSERT INTO Fogado VALUES (1, 6, '186', -1);
INSERT INTO Fogado VALUES (1, 6, '187', -1);
INSERT INTO Fogado VALUES (1, 6, '188', -1);

