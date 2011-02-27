#!/bin/sh
#
# this file is generated - do not modify!
#

export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git rev-parse --short HEAD`
DIRTY=""
git status | grep -qF 'working directory clean' || DIRTY='-dirty'
echo "$BRANCH-$COMMIT$DIRTY" >version.txt

chmod 755 .
chmod 644 ./gruppen.php
chmod 644 ./bestellschein.php
chmod 644 ./editProduktgruppe.php
chmod 644 ./editBestellung.php
chmod 755 ./artikelsuche.php
chmod 644 ./produktverteilung.php
chmod 644 ./konto.php
chmod 644 ./editVerpackung.php
chmod 644 ./menu.php
chmod 644 ./abschluss.php
chmod 644 ./bestellungen.php
chmod 644 ./gruppenkonto.php
chmod 644 ./basar.php
chmod 755 ./updownload.php
chmod 644 ./lieferantenkonto.php
chmod 644 ./produkte.php
chmod 644 ./editProdukt.php
chmod 644 ./lieferanten.php
chmod 644 ./pfandverpackungen.php
chmod 644 ./gruppenmitglieder.php
chmod 644 ./gesamtlieferschein.php
chmod 644 ./dienstplan.php
chmod 755 ./produktpreise.php
chmod 644 ./verluste.php
chmod 644 ./gruppenpfand.php
chmod 644 ./bestellen.php
chmod 644 ./svn-commit.tmp
chmod 644 ./editBuchung.php
chmod 644 ./abrechnung.php
chmod 644 ./head.php
chmod 644 ./editLieferant.php
chmod 755 ./katalog_upload.php
chmod 644 ./bestellen.php.neu
chmod 644 ./dienstkontrollblatt.php
chmod 644 ./editKonto.php
chmod 644 ./bilanz.php
chmod 600 ./deploy.sh
chmod 700 .git
