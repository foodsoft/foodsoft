FROM linuxconfig/lamp

ADD ./inital_db.sql /tmp/inital_db.sql
ADD ./init_db.sh /tmp/init_db.sh

RUN /tmp/init_db.sh

RUN ln -s /FoodSoft /var/www/html/FoodSoft
RUN apt-get update && apt-get -y install wget unzip
RUN cd /var/www/html && wget https://files.phpmyadmin.net/phpMyAdmin/4.8.1/phpMyAdmin-4.8.1-all-languages.zip && unzip phpMyAdmin-4.8.1-all-languages.zip && mv phpMyAdmin-4.8.1-all-languages pma && rm phpMyAdmin-4.8.1-all-languages.zip
