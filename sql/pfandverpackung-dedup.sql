/*
 * Mit diesem Skript kann man doppelte Pfandverpackungen vereinigen.
 *
 * Man gibt dem Skript die alte und die neue ID an. Das Skript prüft, ob beide
 * IDs existieren und ob Wert und MwSt. übereinstimmen. Falls nicht, wird das
 * Skript abgebrochen
 *
 * Verwendung:
 *
 * 1. Backup der Datenbank machen:
 * # mariadb-dump <DATABASENAME> | bzip2 > backup.sql.bz2
 *
 * 2. Skript ausführen:
 * # mariadb <DATABASENAME> <<EOF
 * set @old_id = <OLD_ID>, @new_id = <NEW_ID>;
 * $(cat pfandverpackung-dedup.sql)
 * EOF
 *
 * 3. Fertig :-D
 *
 * Bemerkungen:
 * Sollte man das Skript ein zweites Mal mit den gleichen IDs laufen lassen,
 * wird ein Fehler gemeldet, weil die alte ID nicht mehr existiert.
 *
 * Sollte das Skript mit Fehler beendet werden, bleibt eine Hilfsfunktion in
 * der Datenbank zurück. Sie kann entfernt werden mit:
 *
 * # mysql <DATABASENAME> -e 'DROP FUNCTION killConnection'
 */

DROP FUNCTION IF EXISTS killConnection;

DELIMITER $$
CREATE FUNCTION killConnection() RETURNS INT
BEGIN
    SELECT connection_id() into @connectionId;
    KILL @connectionId;
    RETURN @connectionId;
END $$
DELIMITER ;

set @ok = @old_id IS NOT NULL AND @new_id IS NOT NULL;
select if(! @ok, 'Setze @old_id und @new_id!', '') AS '';
select if(! @ok, killConnection(), '') AS '';

select concat('Ersetze Pfandverpackung ', @old_id, ' durch ', @new_id, ':') as '';

set @ok = @old_id != @new_id;
select if(! @ok, 'IDs müssen unterschiedlich sein!', '') AS '';
select if(! @ok, killConnection(), '') AS '';

set @ok = exists(select 1 from pfandverpackungen where id=@old_id);
select if(! @ok, concat(@old_id, ' ungültig!'), '') AS '';
select if(! @ok, killConnection(), '') AS '';

set @ok = exists(select 1 from pfandverpackungen where id=@new_id);
select if(! @ok, concat(@new_id, ' ungültig!'), '') AS '';
select if(! @ok, killConnection(), '') AS '';

select concat('Alte Pfandverpackung: ', name, ' ', wert, '€ (', mwst, '%)') as '' from pfandverpackungen where id=@old_id;
select concat('Neue Pfandverpackung: ', name, ' ', wert, '€ (', mwst, '%)') as '' from pfandverpackungen where id=@new_id;

set @compat = (select pv1.lieferanten_id = pv2.lieferanten_id AND pv1.wert = pv2.wert AND pv1.mwst = pv2.mwst from pfandverpackungen pv1, pfandverpackungen pv2 where pv1.id = @old_id and pv2.id = @new_id);
set @compat = (select IFNULL(@compat, 0));

select if(! @compat, 'Lieferant, Wert oder MwSt stimmen nicht überein!', '') AS '';
select if(! @compat, killConnection(), '') AS '';

select 'Buche um...' AS '';

insert into lieferantenpfand(verpackung_id, bestell_id, anzahl_voll, anzahl_leer)
select @new_id, lp.bestell_id, lp.anzahl_voll, lp.anzahl_leer
from lieferantenpfand lp where lp.verpackung_id = @old_id
on duplicate key
update
  anzahl_voll = lieferantenpfand.anzahl_voll + lp.anzahl_voll,
  anzahl_leer = lieferantenpfand.anzahl_leer + lp.anzahl_leer;

select concat('Lösche ', @old_id,'...') AS '';

delete from lieferantenpfand where verpackung_id = @old_id;
delete from pfandverpackungen where id = @old_id;

DROP FUNCTION IF EXISTS killConnection;

select 'Fertig.' AS '';
