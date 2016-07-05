ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
CONTAINERS = $(shell docker ps -a -q)
VOLUMES = $(shell docker volume ls |awk 'NR>1 {print $2}')
list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\/\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

#############################
# Docker machine states
#############################

start:
	docker-compose start

stop:
	docker-compose stop

state:
	docker-compose ps

build:
	sudo chmod 777 storage/riak
	docker-compose build
	docker-compose up -d

	docker exec keyserver_fpm composer --working-dir=/app install

build-hard:
	docker-compose build --no-cache
	docker-compose up -d

attach:
	docker exec -i -t ${c} /bin/bash

purge:
	docker stop $(CONTAINERS)
	docker rm $(CONTAINERS)
	docker volume rm $(VOLUMES)