# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT =

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

.DEFAULT_GOAL = help
.PHONY: *

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'


## —— Quality 🧰 ———————————————————————————————————————————————————————————————
qa: qa-cs qa-static ## Executes the full quality pipeline

qa-cs: ## Check coding standards
	$(PHP) vendor/bin/phpcs -n

qa-static: ## Run static analysis
	$(PHP) vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1

qa-fix: ## Executes rector and the cs fixer
	$(PHP) vendor/bin/rector
	$(PHP) vendor/bin/phpcbf -n
