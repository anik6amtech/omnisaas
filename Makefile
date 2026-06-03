# OmniReply — developer task runner. `make help` lists targets.
#
# Dev model: Docker runs the backing services (Postgres, Redis, Mailpit, MinIO,
# Reverb); the app + queue + Vite run on the HOST via `composer dev` (fast, full
# RAM, no Octane). `make up-full` runs everything in Docker for parity (needs a
# roomy Docker VM). App/quality commands below run on the host.

DC := docker compose

.DEFAULT_GOAL := help
.PHONY: help up up-full down build ps logs dev shell key migrate fresh seed \
        test pint pint-test stan horizon

help: ## List available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	  awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Start backing services (Postgres, Redis, Mailpit, MinIO, Reverb)
	$(DC) up -d

up-full: ## Start the ENTIRE stack in Docker incl. app roles (needs a roomy VM)
	$(DC) --profile full up -d --build

down: ## Stop the stack (keeps volumes)
	$(DC) --profile full down

build: ## Build the app image
	$(DC) build

ps: ## Show service status
	$(DC) --profile full ps

logs: ## Tail logs for all services
	$(DC) logs -f

dev: ## Run the app on the host: serve + horizon + vite + logs (no Octane)
	composer dev

shell: ## Shell into the app container (requires `make up-full`)
	$(DC) exec app bash

key: ## Generate APP_KEY (after `cp .env.example .env`)
	php artisan key:generate

migrate: ## Run database migrations (host)
	php artisan migrate

fresh: ## Drop, re-migrate, and seed the database (host)
	php artisan migrate:fresh --seed

seed: ## Run database seeders (host)
	php artisan db:seed

test: ## Run the Pest suite (host, Postgres-backed)
	php artisan test

pint: ## Format code with Pint (host)
	vendor/bin/pint

pint-test: ## Check formatting without changing files (host)
	vendor/bin/pint --test

stan: ## Run Larastan static analysis (host)
	vendor/bin/phpstan analyse --memory-limit=1G

horizon: ## Run Horizon in the foreground (host)
	php artisan horizon
