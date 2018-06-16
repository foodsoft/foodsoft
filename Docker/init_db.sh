#!/bin/bash
mysqld_safe & 

while [[ ! $(echo "SHOW DATABASES;" | mysql --user=admin --password=pass) ]]
	do echo "waiting for mysql"
	sleep 1
done

echo "CREATE DATABASE foodcoop_foodsoft;" | mysql --user=admin --password=pass && mysql --user=admin --password=pass foodcoop_foodsoft < /tmp/inital_db.sql
