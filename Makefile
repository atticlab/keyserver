ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
CONTAINERS = $(shell docker ps -a -q)
VOLUMES = $(shell docker volume ls |awk 'NR>1 {print $2}')
COMPOSER_DIR = $(realpath $(PWD))

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
	@if [ ! -f ./.env ]; then\
  	read -p "Enter riak host:" riak_host; echo "RIAK_HOST=$$riak_host" >> ./.env; \
  	read -p "Enter api host:" api_host; echo "API_HOST=$$api_host" >> ./.env; \
	fi
	docker-compose build
	docker-compose up -d

build-hard:
	docker-compose build --no-cache
	docker-compose up -d

attach:
	docker exec -i -t ${c} /bin/bash

purge:
	docker stop $(CONTAINERS)
	docker rm $(CONTAINERS)
	docker volume rm $(VOLUMES)
