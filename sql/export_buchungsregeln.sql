/*
 * Run as mysql -N {database} < export_buchungsregeln.sql
 */
select concat('update bankkonten set buchungsregeln=\'', buchungsregeln, '\' where id=', id, ';') from bankkonten where buchungsregeln <> '';
select concat('update lieferanten set buchungsregeln=\'', buchungsregeln, '\' where id=', id, ';') from lieferanten where buchungsregeln <> '';
select concat('update bestellgruppen set buchungsregeln=\'', buchungsregeln, '\' where id=', id, ';') from bestellgruppen where buchungsregeln <> '';
