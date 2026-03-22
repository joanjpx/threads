.PHONY: up down restart build logs sh migrate setup \
	create-models start

# Docker Compose commands
up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

build:
	docker-compose build

logs:
	docker-compose logs -f

# App commands
sh:
	docker-compose exec app sh

migrate:
	docker-compose exec app php artisan migrate

migrate-fresh:
	docker-compose exec app php artisan migrate:fresh --seed

setup: build up
	@echo "Waiting for MySQL to be ready..."
	sleep 10
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate

# Events
rabbit:
	@echo "RabbitMQ Management UI: http://localhost:15672 (guest/guest)"

kafka-ui:
	@echo "Kafka UI: http://localhost:8080"
