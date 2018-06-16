#!/bin/bash

source var.sh
echo  \
"This runs (provisions) a container based on  an image, you can stop it later via 
        $DOCKER_CMD stop FoodSoft 
and start it again via 
        $DOCKER_CMD start FoodSoft"

cp config.php ../code/config.php
exec $DOCKER_CMD run -p 80:80 -v $(dirname $PWD):/FoodSoft --name FoodSoft foodsoftimage
