/*
 * Mit diesem Skript kann man alle aktuellen Preiseinträge zu einem definierten
 * Termin auf neue MwSt-Sätze umstellen.
 *
 * Für Bestellungen, deren Liefertermin nach dem Termin liegt, werden diese
 * neuen Preiseinträge gesetzt, sofern sie noch auf Preiseinträge mit altem
 * MwSt-Satz verweisen.
 *
 * Verwendung:
 *
 * 1. Backup der Datenbank machen:
 * # mysqldump <DATABASENAME> | bzip2 > backup.sql.bz2
 *
 * 2. Skript konfigurieren (falls nötig):
 * # vi mwst.sql
 *
 * 3. Skript ausführen:
 * # mysql <DATABASENAME> < mwst.sql
 *
 * 4. Fertig :-D
 *
 * Bemerkungen:
 * Dieses Skript ist (hoffentlich) idempotent, kann also mehrfach ausgeführt werden und hat nur beim
 * ersten Mal einen Effekt.
 *
 * Sollte einem eine Bestellung durch die Lappen gegangen sein, weil das Lieferdatum vor dem
 * Stichtag lag, kann man das Lieferdatum ändern und das Skript nochmal laufen lassen, um diese
 * Bestellung nachträglich zu konvertieren.
 */

/*
 * Skript-Konfiguration:
 */
set @old1 = 5.00, @new1 = 7.00, @old2 = 16.00, @new2 = 19.00;
set @changetime = timestamp('2021-01-01 00:00:00');

/*
 * Umstellung der Rückgaben kann im allgemeinen drei Monate später erfolgen, siehe
 * https://datenbank.nwb.de/Dokument/Anzeigen/827824/
 */
set @changetime_deposit_return = timestamp('2020-10-01 00:00:00');

/* Die meisten Händler machen davon keinen Gebrauch und stellen zum gleichen Zeitpunkt um: */
set @changetime_deposit_return = @changetime;


/*
 * Ausführung:
 */
select concat('Setze Katalog-MwSt-Sätze auf ', @new1, '% und ', @new2, '%:') as '';
update leitvariable set value=@new1 where name='katalog_mwst_reduziert';
update leitvariable set value=@new2 where name='katalog_mwst_standard';
select name, value from leitvariable where name like 'katalog_mwst_%';

select concat('Suche aktuelle Preiseinträge mit MwSt ', @old1, '% oder ', @old2, '%:') as '';

drop table if exists new_produktpreise;
create temporary table new_produktpreise as select * from produktpreise
    where mwst in (@old1, @old2)
    and zeitende is null;
select concat(row_count(), ' gefunden.') as '';
alter table new_produktpreise modify id int(11) null;

drop table if exists updated_produktpreise;
create temporary table updated_produktpreise as select * from new_produktpreise;
alter table updated_produktpreise modify id int(11) null;

select 'Schließe aktuelle Preiseinträge ab.' as '';
update updated_produktpreise set zeitende=timestampadd(SECOND, -1, @changetime);

select 'Erzeuge neue Preiseinträge.' as '';
update new_produktpreise set
    id=NULL,
    zeitstart=@changetime,
    mwst=
    case
        when mwst = @old1 then @new1
        when mwst = @old2 then @new2
        else mwst
    end;

select 'Pflege Änderungen ein.' as '';
start transaction;
update produktpreise as d, updated_produktpreise as s set d.zeitende = s.zeitende where d.id = s.id;
insert into produktpreise select * from new_produktpreise;

select 'Aktualisiere Preiseinträge in aktuellen Bestellungen' as '';
update bestellvorschlaege as d,
#   erste Referenz um den korrekten neuen Eintrag zu selektieren
    produktpreise as s,
#   zweite Referenz, um zu überprüfen dass der alte Eintrag noch falsch ist
    produktpreise as c,
    gesamtbestellungen as g
    set d.produktpreise_id = s.id
    where s.produkt_id = d.produkt_id
        and s.zeitende is null
        and c.id = d.produktpreise_id
        and c.mwst in (@old1, @old2)
        and d.gesamtbestellung_id = g.id
        and g.lieferung >= @changetime;

select concat(row_count(), ' Einträge aktualisiert.') as '';
commit;

# Pfandverpackungen
select concat('Suche nach Pfandverpackungen mit alter MwSt ohne Entsprechung mit neuer MwSt') as '';
drop table if exists new_pfandverpackungen;
create temporary table new_pfandverpackungen as select p1.* from pfandverpackungen as p1
    where p1.mwst in (@old1, @old2)
    and (select count(*) from pfandverpackungen as p2
        where p2.lieferanten_id=p1.lieferanten_id
        and p2.name=p1.name
        and p2.mwst=
        case
            when p1.mwst = @old1 then @new1
            when p1.mwst = @old2 then @new2
        end
        )=0
    order by lieferanten_id, sort_id;
select concat(row_count(), ' gefunden.') as '';
alter table new_pfandverpackungen modify id int(11) null;
update new_pfandverpackungen set
    id=NULL,
    sort_id=0,
    mwst=
    case
        when mwst=@old1 then @new1
        when mwst=@old2 then @new2
    end;
insert into pfandverpackungen select * from new_pfandverpackungen;
update pfandverpackungen set
    sort_id=id
    where sort_id=0;
select concat(row_count(), ' neue Pfandverpackungen mit aktueller MwSt eingefügt.') as '';

select 'Setze Pfandbuchungen auf neue Pfandverpackungen um...' as '';

drop table if exists new_lieferantenpfand;
create temporary table new_lieferantenpfand as select * from lieferantenpfand where 0;
alter table new_lieferantenpfand modify id int(11) null;

# Pfandbuchungen für Lieferungen nach @changetime auf neue MwSt umsetzen
insert into new_lieferantenpfand (id, verpackung_id, bestell_id, anzahl_voll)
    select l.id, d.id, l.bestell_id, l.anzahl_voll from lieferantenpfand as l
        left join pfandverpackungen as s on s.id = l.verpackung_id
        left join pfandverpackungen as d
            on d.lieferanten_id = s.lieferanten_id
            and d.name = s.name
            and d.mwst =
                case
                    when s.mwst = @old1 then @new1
                    when s.mwst = @old2 then @new2
                end
        left join gesamtbestellungen as b on b.id = l.bestell_id
        where b.lieferung >= @changetime
        and l.anzahl_voll <> 0
        and s.mwst in (@old1, @old2);

# Pfandbuchungen für Rückgaben nach @changetime aber vor @changetime_deposit_return auf alter MwSt lassen
insert into new_lieferantenpfand (id, verpackung_id, bestell_id, anzahl_leer)
    select l.id, l.verpackung_id, l.bestell_id, l.anzahl_leer from lieferantenpfand as l
        inner join new_lieferantenpfand as n on n.id = l.id
        left join pfandverpackungen as s on s.id = l.verpackung_id
        left join gesamtbestellungen as b on b.id = l.bestell_id
        where b.lieferung < @changetime_deposit_return
        and l.anzahl_leer <> 0
        and s.mwst in (@old1, @old2);

# Pfandbuchungen für Rückgaben nach @changetime_deposit_return auf neue MwSt umsetzen
insert into new_lieferantenpfand (id, verpackung_id, bestell_id, anzahl_leer)
    select l.id, d.id, l.bestell_id, l.anzahl_leer from lieferantenpfand as l
        left join pfandverpackungen as s on s.id = l.verpackung_id
        left join pfandverpackungen as d
            on d.lieferanten_id = s.lieferanten_id
            and d.name = s.name
            and d.mwst =
                case
                    when s.mwst = @old1 then @new1
                    when s.mwst = @old2 then @new2
                end
        left join gesamtbestellungen as b on b.id = l.bestell_id
        where b.lieferung >= @changetime_deposit_return
        and l.anzahl_leer <> 0
        and s.mwst in (@old1, @old2);

# Die Quell-IDs treten i.A. mehrfach auf, weil Lieferung und Rückgabe gesplittet werden.
select concat((select count(distinct id) from new_lieferantenpfand), ' gefunden.') as '';

drop table if exists comparison;
create temporary table comparison (lieferant text, name text, anzahl_voll int, anzahl_leer int);
insert into comparison (lieferant, name, anzahl_voll, anzahl_leer)
    select l.name, concat(p.name, ' ', p.mwst), -sum(b.anzahl_voll), -sum(b.anzahl_leer)
        from lieferantenpfand as b
        left join pfandverpackungen as p on p.id = b.verpackung_id
        left join lieferanten as l on l.id = p.lieferanten_id
        group by l.name, p.name
        order by l.name, p.name;

start transaction;
# Lösche alte Buchungen, die jetzt ersetzt werden
delete l from lieferantenpfand as l
    inner join new_lieferantenpfand as n
    where l.id = n.id;

# Lege neue Buchungen an
insert into lieferantenpfand (verpackung_id, bestell_id, anzahl_voll, anzahl_leer)
    select verpackung_id, bestell_id, sum(anzahl_voll), sum(anzahl_leer)
    from new_lieferantenpfand
    group by bestell_id, verpackung_id
    order by bestell_id, verpackung_id;
commit;

insert into comparison (lieferant, name, anzahl_voll, anzahl_leer)
    select l.name, concat(p.name, ' ', p.mwst), sum(b.anzahl_voll), sum(b.anzahl_leer)
        from lieferantenpfand as b
        left join pfandverpackungen as p on p.id = b.verpackung_id
        left join lieferanten as l on l.id = p.lieferanten_id
        group by l.name, p.name
        order by l.name, p.name;

select * from
    (
    select lieferant, name, sum(anzahl_voll) as anzahl_voll, sum(anzahl_leer) as anzahl_leer
    from comparison
    group by lieferant, name
    order by lieferant, name
    ) as t
    where anzahl_voll <> 0
    or anzahl_leer <> 0;

select 'Fertig :-)' as '';

