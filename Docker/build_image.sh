#!/bin/bash
source var.sh

exec $DOCKER_CMD build -t foodsoftimage .
