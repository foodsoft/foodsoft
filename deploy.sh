#!/bin/sh
#
# this file is generated - do not modify!
#

export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git rev-parse --short HEAD`
COMMIT_FULL=`git rev-parse HEAD`
DIRTY=""
git status | grep -qF 'working directory clean' || DIRTY='-dirty'
echo "<a href='http://github.com/foodsoft/foodsoft/commits/$COMMIT_FULL'>$BRANCH-$COMMIT$DIRTY</a>" >version.txt

chmod 644 .gitattributes
chmod 644 .gitignore
chmod 644 GITHOOKS
chmod 644 INSTALL
chmod 644 README.md
chmod 644 ToDo.txt
chmod 755 antixls.modif
chmod 644 apache.sample.conf
chmod 644 code/common.php
chmod 644 code/config.php
chmod 644 code/err_functions.php
chmod 644 code/forms.php
chmod 644 code/html.php
chmod 644 code/inlinks.php
chmod 644 code/katalogsuche.php
chmod 644 code/login.php
chmod 644 code/views.php
chmod 644 code/zuordnen.php
chmod 644 css/foodsoft.css
chmod 644 css/modified.gif
chmod 644 css/readonly.gif
chmod 755 deploy.sh
chmod 644 dokuwiki_auth_plugin/authfoodsoft/README
chmod 644 dokuwiki_auth_plugin/authfoodsoft/auth/authentication.php
chmod 644 dokuwiki_auth_plugin/authfoodsoft/lang/en/lang.php
chmod 644 dokuwiki_auth_plugin/authfoodsoft/plugin.info.txt
chmod 644 dump.php
chmod 644 fcck.php
chmod 644 files_und_skripte
chmod 644 foodsoft.class.php
chmod 644 head.php
chmod 644 img/arrow.down.blue.png
chmod 644 img/arrow.up.blue.png
chmod 644 img/b_browse.png
chmod 644 img/b_drop.png
chmod 644 img/b_edit.png
chmod 644 img/birne_rot.png
chmod 644 img/card.png
chmod 644 img/chalk_trans.gif
chmod 644 img/chart.png
chmod 644 img/close_black.gif
chmod 644 img/close_black_hover.gif
chmod 644 img/close_black_trans.gif
chmod 644 img/euro.png
chmod 644 img/fant.gif
chmod 644 img/gluehbirne_15x16.png
chmod 644 img/green.png
chmod 644 img/magic_wand.png
chmod 644 img/minus.png
chmod 644 img/open_black_trans.gif
chmod 644 img/people.png
chmod 644 img/plus.png
chmod 644 img/print_black.gif
chmod 644 img/question.png
chmod 644 img/question_small.png
chmod 644 img/reload_black.gif
chmod 644 index.php
chmod 644 js/Acronyms.js
chmod 644 js/foodsoft.js
chmod 644 js/lib/prototype.js
chmod 644 js/tooltip.js
chmod 644 leitvariable.php
chmod 644 links_und_parameter
chmod 755 pre-commit
chmod 755 setup.php
chmod 644 structure.php
chmod 644 templates/bestellschein.tex
chmod 644 templates/prettytables.tex
chmod 644 windows/abrechnung.php
chmod 644 windows/abschluss.php
chmod 644 windows/artikelsuche.php
chmod 644 windows/basar.php
chmod 644 windows/bestellen.php
chmod 644 windows/bestellen.php.neu
chmod 644 windows/bestellfax.php
chmod 644 windows/bestellschein.php
chmod 644 windows/bestellungen.php
chmod 644 windows/bilanz.php
chmod 644 windows/catalogue_acronyms.php
chmod 644 windows/dienstkontrollblatt.php
chmod 644 windows/dienstplan.php
chmod 644 windows/editBestellung.php
chmod 644 windows/editBuchung.php
chmod 644 windows/editKonto.php
chmod 644 windows/editLieferant.php
chmod 644 windows/editProdukt.php
chmod 644 windows/editProduktgruppe.php
chmod 644 windows/editVerpackung.php
chmod 644 windows/gesamtlieferschein.php
chmod 644 windows/gruppen.php
chmod 644 windows/gruppenkonto.php
chmod 644 windows/gruppenmitglieder.php
chmod 644 windows/gruppenpfand.php
chmod 644 windows/head.php
chmod 644 windows/katalog_upload.php
chmod 644 windows/konto.php
chmod 644 windows/lieferanten.php
chmod 644 windows/lieferantenkonto.php
chmod 644 windows/menu.php
chmod 644 windows/pfandverpackungen.php
chmod 644 windows/produkte.php
chmod 644 windows/produktpreise.php
chmod 644 windows/produktverteilung.php
chmod 644 windows/updownload.php
chmod 644 windows/verluste.php
chmod 700 .git
