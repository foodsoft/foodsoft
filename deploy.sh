#!/bin/sh
#
# this file is generated - do not modify!
#

wd_clean ()
{
  test -z "$(git status --porcelain)"
}

export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git rev-parse --short HEAD`
COMMIT_FULL=`git rev-parse HEAD`
DIRTY=""
wd_clean || DIRTY='-dirty'
echo "<a rel='noreferrer noopener' target='_blank' href='http://github.com/fcpotsdamwest/foodsoft/commit/$COMMIT_FULL'>$BRANCH-$COMMIT$DIRTY</a>" >src/version.txt

chmod 644 .gitattributes
chmod 644 .gitignore
chmod 644 GITHOOKS
chmod 644 INSTALL
chmod 644 README.md
chmod 644 ToDo.txt
chmod 644 apache.sample.conf
chmod 644 db/mwst.sql
chmod 700 deploy.sh
chmod 644 dev/.env.sample
chmod 644 dev/db/config/foodsoft.cnf
chmod 644 dev/docker-compose.yml
chmod 644 dev/web/Containerfile.php7
chmod 644 dev/web/Containerfile.php8
chmod 644 dev/web/assets/foodsoft.conf
chmod 644 dev/web/assets/start-web.sh
chmod 644 docs/files_und_skripte
chmod 644 docs/links_und_parameter
chmod 644 dokuwiki_auth_plugin/authfoodsoft/README
chmod 644 dokuwiki_auth_plugin/authfoodsoft/auth/authentication.php
chmod 644 dokuwiki_auth_plugin/authfoodsoft/lang/en/lang.php
chmod 644 dokuwiki_auth_plugin/authfoodsoft/plugin.info.txt
chmod 755 git-hooks/post-checkout
chmod 755 git-hooks/post-commit
chmod 755 git-hooks/post-merge
chmod 755 git-hooks/pre-commit
chmod 644 src/code/common.php
chmod 644 src/code/config.php
chmod 644 src/code/err_functions.php
chmod 644 src/code/forms.php
chmod 644 src/code/html.php
chmod 644 src/code/inlinks.php
chmod 644 src/code/katalogsuche.php
chmod 644 src/code/login.php
chmod 644 src/code/views.php
chmod 644 src/code/zuordnen.php
chmod 644 src/css/foodsoft.css
chmod 644 src/css/modified.gif
chmod 644 src/css/readonly.gif
chmod 644 src/dump.php
chmod 644 src/favicon.ico
chmod 644 src/fcck.php
chmod 644 src/foodsoft.class.php
chmod 644 src/head.php
chmod 644 src/img/arrow.down.blue.png
chmod 644 src/img/arrow.up.blue.png
chmod 644 src/img/b_browse.png
chmod 644 src/img/b_drop.png
chmod 644 src/img/b_edit.png
chmod 644 src/img/birne_rot.png
chmod 644 src/img/card.png
chmod 644 src/img/chalk_trans.gif
chmod 644 src/img/chart.png
chmod 644 src/img/close_black.gif
chmod 644 src/img/close_black_hover.gif
chmod 644 src/img/close_black_trans.gif
chmod 644 src/img/euro.png
chmod 644 src/img/fant.gif
chmod 644 src/img/gluehbirne_15x16.png
chmod 644 src/img/green.png
chmod 644 src/img/magic_wand.png
chmod 644 src/img/minus.png
chmod 644 src/img/open_black_trans.gif
chmod 644 src/img/people.png
chmod 644 src/img/plus.png
chmod 644 src/img/print_black.gif
chmod 644 src/img/question.png
chmod 644 src/img/question_small.png
chmod 644 src/img/reload_black.gif
chmod 644 src/index.php
chmod 644 src/js/Acronyms.js
chmod 644 src/js/foodsoft.js
chmod 644 src/js/lib/prototype.js
chmod 644 src/js/tooltip.js
chmod 644 src/leitvariable.php
chmod 644 src/phpinfo.php
chmod 644 src/setup.php
chmod 644 src/structure.php
chmod 644 src/templates/bestellschein.html.tpl
chmod 644 src/windows/abrechnung.php
chmod 644 src/windows/abschluss.php
chmod 644 src/windows/artikelsuche.php
chmod 644 src/windows/basar.php
chmod 644 src/windows/bestellen.php
chmod 644 src/windows/bestellfax.php
chmod 644 src/windows/bestellschein.php
chmod 644 src/windows/bestellungen.php
chmod 644 src/windows/bilanz.php
chmod 644 src/windows/catalogue_acronyms.php
chmod 644 src/windows/dienstkontrollblatt.php
chmod 644 src/windows/dienstplan.php
chmod 644 src/windows/editBestellung.php
chmod 644 src/windows/editBuchung.php
chmod 644 src/windows/editKonto.php
chmod 644 src/windows/editLieferant.php
chmod 644 src/windows/editProdukt.php
chmod 644 src/windows/editProduktgruppe.php
chmod 644 src/windows/editVerpackung.php
chmod 644 src/windows/gesamtlieferschein.php
chmod 644 src/windows/gruppen.php
chmod 644 src/windows/gruppenkonto.php
chmod 644 src/windows/gruppenmitglieder.php
chmod 644 src/windows/gruppenpfand.php
chmod 644 src/windows/head.php
chmod 644 src/windows/katalog_upload.php
chmod 644 src/windows/konto.php
chmod 644 src/windows/lieferanten.php
chmod 644 src/windows/lieferantenkonto.php
chmod 644 src/windows/menu.php
chmod 644 src/windows/pfandverpackungen.php
chmod 644 src/windows/produkte.php
chmod 644 src/windows/produktpreise.php
chmod 644 src/windows/produktverteilung.php
chmod 644 src/windows/verluste.php
chmod 700 .git
