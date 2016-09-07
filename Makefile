.PHONY: tests

up:
	@docker-compose rm -f
	@docker-compose pull
	@sed -e "s/HOSTIP/$$(docker-machine ip)/g" docker-compose.yml | docker-compose --file - up --build -d
	@docker-compose run laravel-pubsub /bin/bash

down:
	@docker-compose stop -t 1

tests:
	@./vendor/bin/phpunit --configuration phpunit.xml
