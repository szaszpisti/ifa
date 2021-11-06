#!/bin/bash

DB=ifa.db
SQL=local.sql

rm -f $DB
{
	sed "s/#ADMINPW#/$( cat admin.password )/" schema.sql
	cat $SQL
	echo 'INSERT INTO Admin VALUES (0, "2000-01-01", 192, 228, 2, "2000-01-01 08:00", "3000-01-01 12:00");'
} | sqlite3 $DB

chmod 660 $DB
chgrp www-data $DB
