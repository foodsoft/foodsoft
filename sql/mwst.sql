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
set @old1 = 7, @new1 = 5, @old2 = 19, @new2 = 16;
set @changetime = timestamp('2020-07-01 00:00:00');

/*
 * Ausführung:
 */
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
update produktpreise as d, updated_produktpreise as s set d.zeitende = s.zeitende where d.id = s.id;
insert into produktpreise select * from new_produktpreise;

select 'Aktualisiere Preiseinträge in aktuellen Bestellungen' as '';
update bestellvorschlaege as d,
    /* erste Referenz um den korrekten neuen Eintrag zu selektieren */
    produktpreise as s,
    /* zweite Referenz, um zu überprüfen dass der alte Eintrag noch falsch ist */
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

select 'Fertig :-)' as '';

