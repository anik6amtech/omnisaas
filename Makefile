# OmniReply — developer task runner.
# `make help` lists targets. App-context targets run inside the `app` container.

DC := docker compose
EXEC := $(DC) exec -T app

.DEFAULT_GOAL := help
.PHONY: help up down build rebuild restart ps logs shell key migrate fresh seed \
        test pint pint-test stan watch reload horizon-status

help: ## List available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	  awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Build (if needed) and start the full stack in the background
	$(DC) up -d --build

down: ## Stop the stack (keeps volumes)
	$(DC) down

build: ## Build the app image
	$(DC) build

rebuild: ## Rebuild the app image with no cache
	$(DC) build --no-cache

restart: ## Restart all services
	$(DC) restart

ps: ## Show service status
	$(DC) ps

logs: ## Tail logs for all services
	$(DC) logs -f

shell: ## Open a shell in the app container
	$(DC) exec app bash

key: ## Generate APP_KEY (run once after `cp .env.example .env`)
	$(EXEC) php artisan key:generate

migrate: ## Run database migrations
	$(EXEC) php artisan migrate

fresh: ## Drop, re-migrate, and seed the database
	$(EXEC) php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(EXEC) php artisan db:seed

test: ## Run the Pest test suite (in the app container)
	$(EXEC) php artisan test

pint: ## Format code with Pint
	$(EXEC) vendor/bin/pint

pint-test: ## Check formatting without changing files
	$(EXEC) vendor/bin/pint --test

stan: ## Run Larastan static analysis
	$(EXEC) vendor/bin/phpstan analyse --memory-limit=1G

watch: ## Run Octane with file watching (hot reload) in the foreground
	$(DC) exec app php artisan octane:frankenphp --host=0.0.0.0 --port=8000 --watch

reload: ## Reload Octane workers after code changes
	$(EXEC) php artisan octane:reload

horizon-status: ## Show Horizon status
	$(EXEC) php artisan horizon:status
