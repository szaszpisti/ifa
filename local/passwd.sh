#!/bin/bash

DB="../db/ifa.db"

# set -x
uid=0
[ -n "$1" ] && uid=$1

TANAR=$( echo "SELECT tnev FROM Tanar WHERE id=$uid;" | sqlite3 $DB )
DIAK=$( echo "SELECT dnev FROM Diak_base WHERE id=$uid;" | sqlite3 $DB )

if [ "$uid" == "0" ]; then
	NEV="Admin"
elif [ -n "$TANAR" ]; then
	NEV="$TANAR"
	TABLE="Tanar"
elif [ -n "$DIAK" ]; then
	NEV="$DIAK"
	TABLE="Diak_base"
fi

if [ -n "$NEV" ]; then
	echo -en "\nFIGYELEM! Nem írom vissza a mozanaplo*.csv-be!\n$NEV új jelszava (kilépés: Ctrl-C): "
	read -s PW
	HASH=`echo -n $PW | sha256sum | cut -d' ' -f1`
	echo
else
	echo "Nincs $uid azonosítójú felhasználó!"
fi

if [ "$uid" == "0" ]; then
	{
		echo 'DROP VIEW Diak;'
		sed -n '/CREATE VIEW Diak AS/,/^$/p' schema.sql \
	 	| sed "s/#ADMINPW#/$HASH/"
	} | sqlite3 $DB
elif [ -n "$TABLE" ]; then
	echo "UPDATE $TABLE SET jelszo='$HASH' WHERE id=$uid;" \
	| sqlite3 $DB
fi

