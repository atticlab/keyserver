ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent

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
		read -p "Enter current node ip:" host; echo "HOST=$$host" >> ./.env; \
		read -p "Enter Riak host (with protocol and port):" riak_host; echo "RIAK_HOST=$$riak_host" >> ./.env; \
		read -p "Enter number of riak nodes:" n_val; echo "N_VAL=$$n_val" >> ./.env; \
	fi
	docker-compose build
	docker-compose up -d

indexes:
	docker exec keyserver-php bash -c '/usr/local/bin/php /src/php/app/cli/cli.php index yokozuna'

attach:
	docker exec -i -t ${c} /bin/bash

purge:
	docker-compose down