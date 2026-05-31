DOCKER_COMPOSE ?= docker compose
DOCKER_USER    ?= $(shell id -u):$(shell id -g)
export DOCKER_USER

PHP            = $(DOCKER_COMPOSE) exec app php
COMPOSER       = $(DOCKER_COMPOSE) exec app composer

.PHONY: up down install shell test stan cs-check cs-fix serve

up:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down

install:
	$(COMPOSER) install

shell:
	$(DOCKER_COMPOSE) exec app sh

test:
	$(PHP) vendor/bin/phpunit

stan:
	$(PHP) vendor/bin/phpstan analyse --memory-limit=512M

cs-check:
	$(PHP) vendor/bin/php-cs-fixer check --diff

cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix

serve:
	$(PHP) -S 0.0.0.0:8080 -t public/
