#!/bin/sh

[ "$#" = 2 ] || { echo "usage: <date> <file>" >&2 ; exit 17 ; }
base="ou=terra,ou=fcnahrungskette,o=uni-potsdam,c=de"

echo "upload: $1 $2"

./antixls.modif -c "$2" | {

  while true; do
    if ! read line ; then
      echo "unbekanntes format... Hilfe...\n" >&2
      exit 17
    fi
    echo "theline: <pre>X:$line:Y</pre><br>"
    if printf "%s\n" "$line" | grep '^@Art.Nr. *@Bestell-Nr.@Milch *@Inhalt *@Einh. *@Land *@IK *@Verband *@ Netto-Preis *@/Einh. *@MwSt. % *@EAN-Code *@' &>/dev/null ; then
      echo 'input format: Terra frisch...' >&2
      fields="blubb anummer bnummer artikel gebinde einheit land ik verband netto preiseinheit mwst bla"
      pattern='^@[[:digit:] ]\+@[[:digit:] ]\+@'
      tag="Fr"
#                                     X:Art.Nr.  @@Bestell-Nr.@@Milch  @@@@@@Inhalt  @Einh.  @Land  @@IK  @Verband  @@Netto-Preis    @@/Einh.  @empf. VK@@MwSt. %  @@EAN-Code  @@@:Y
    elif printf "%s\n" "$line" | grep '^Art.Nr. *@@Bestell-Nr.@@Milch *@@@@@@Inhalt *@Einh. *@Land *@@IK *@Verband *@@ *Netto-Preis *@@/Einh. *@empf. VK@@MwSt. % *@@EAN-Code *@@@' &>/dev/null ; then
      echo 'input format: Terra frisch...' >&2
      fields="anummer bnummer artikel gebinde einheit land ik verband netto preiseinheit blubb mwst bla"
      pattern='^[[:digit:] ]\+@@[[:digit:] ]\+@'
      tag="Fr"
    elif printf "%s\n" "$line" | grep 'Art.Nr.@Bestell-Nr.@ZITRUS-FRÜCHTE *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@' &>/dev/null ; then
      echo 'input format: Terra obst&gemuese...' >&2
      fields="anummer bnummer artikel gebinde einheit land hkl ik verband netto preiseinheit mwst bla"
      pattern='^[[:digit:] ]\+@[[:digit:] ]\+@'
      tag="OG"
    elif printf "%s\n" "$line" | grep 'Artikelnr.@Bestellnr.@ Beschreibung@VPE *@Liefera *@Land@IK@Netto-Preis@Rabatt@MwSt.%@EAN- Code@' &>/dev/null ; then
      echo 'input format: Terra trocken...' >&2
      fields="anummer bnummer artikel einheit lieferant land ik netto rabatt mwst bla"
      # einheit=ST    # trockenprodukte = fest verpackt, also: immer(?) in stueck!
      pattern='^[[:digit:] ]\+@[[:digit:] ]\+@'
      tag="Tr"
    else
      continue
    fi
    break
  done

  n=0
  grep "$pattern" | tr -s @ | while IFS=@ read  $fields ; do
    if [ $tag = Tr ] ; then
      if [[ "$einheit" =~ ^[[:digit:]] ]] ; then
        gebinde="${einheit%% *}"
        einheit="${einheit##* }"
      else
        gebinde=1
      fi
    fi
    cents=`printf "%s\n" "$netto" | sed 's/^ *\([[:digit:].]*\).*$/0\1 100*1\/pq/' | dc`
    anummer=`printf "%s\n" "$anummer" | tr -d ' '`
    bnummer=`printf "%s\n" "$bnummer" | tr -d ' '`
    printf "anummer:%s bnummer:%s artikel:%s gebinde:%s einheit:%s<br>" "$anummer" "$bnummer" "$artikel" "$gebinde" "$einheit"  >&2
    printf "\n# %s\n" "$((++n))"
    printf "dn: terraArtikelnummer=%s,%s\nchangetype: delete\n\n" "$anummer" "$base"
    printf "dn: terraArtikelnummer=%s,%s\nchangetype: add\n" "$anummer" "$base"
  #  printf "changetype: add\n"
    printf "objectclass: terraArtikel\n"
    printf "terraArtikelnummer: %s\n" $anummer
    printf "terraBestellnummer: %s\n" $bnummer
    printf "cn: %s\n" "$artikel"
    printf "terraNettoPreisInCents: %s\n" "$cents"
    {
      printf "terraGebindegroesse: %s\n" "$gebinde" | tr ',' '.'
      printf "terraEinheit: %s\n" "$einheit" | sed -e 's/ä/ae/' -e 's/ö/oe/' -e 's/ü/ue/' -e 's/Ä/AE/' -e 's/Ö/OE/' -e 's/Ü/UE/' -e 's/ß/sz/'
      printf "terraMWST: %s\n" "$mwst"
      printf "terraHerkunft: %s\n" "${land:--}"
      printf "terraVerband: %s\n" "${verband:--}"
      printf "terraDatum: %s.%s\n" "$1" "$tag"
    } | sed -e 's/ä/ae/' -e 's/ö/oe/' -e 's/ü/ue/' -e 's/Ä/AE/' -e 's/Ö/OE/' -e 's/Ü/UE/' -e 's/ß/sz/'
    printf "\n#<br>\n"
  done \
  | iconv -t utf-8 -f iso-8859-1 | tee /tmp/ldif \
  | ldapmodify -x -D cn=superfoodi,ou=fcnahrungskette,o=uni-potsdam,c=de -w leckerpotsdam -c -H ldap://fcnahrungskette.qipc.org 2>&1

  echo "<br>done."
} 2>&1

