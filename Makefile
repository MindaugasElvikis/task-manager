.DEFAULT: help

help: ## Display usage
	@grep -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?##"}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build project after cloning
	docker run --rm -u "$(id -u):$(id -g)" -v $(pwd):/var/www/html -w /var/www/html laravelsail/php81-composer:latest composer install --ignore-platform-reqs

up: ## Start project
	./vendor/bin/sail up -d

down: ## Stop project
	./vendor/bin/sail down

destroy: ## Destroy docker environment
	./vendor/bin/sail down --rmi all -v
