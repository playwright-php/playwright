# Makefile

.PHONY: about help show-help warning qa sa cs csfix cslint test phpunit phpstan phpcsfixer

.DEFAULT_GOAL := help

ARGS ?=
PHP_CMD ?= php

get_args = $(if $(filter-out $1,$(MAKECMDGOALS)),$(filter-out $1,$(MAKECMDGOALS)),$(ARGS))

define run_cmd
@echo "[·] \033[2m$(1) $(2)\033[0m\n"
@start_time=$$(date +%s.%N); \
$(1) $(2); \
exit_code=$$?; \
end_time=$$(date +%s.%N); \
elapsed_time=$$(echo "$$end_time - $$start_time" | bc | awk '{printf "%.2f", $$1}'); \
command="$(1) $(2)"; \
if [ $$exit_code -eq 0 ]; then \
  printf "\n[\033[32m✓\033[0m] \033[2m%s\033[0m\033[116G\033[2m[\033[0m\033[32m%s\033[0m \033[2mseconds\033[0m\033[2m]\033[0m\n" "$$command" "$$elapsed_time"; \
else \
  printf "\n[\033[31m✗\033[0m] \033[2m%s\033[0m\033[116G\033[2m[\033[0m\033[31m%s\033[0m \033[2mseconds\033[0m\033[2m]\033[0m\n" "$$command" "$$elapsed_time"; \
fi
endef

define php_cmd
 $(call run_cmd,$(PHP_CMD) $(1),$(2))
endef

PROJECT_NAME := Playwright PHP
REPO_URL     := https://github.com/playwright-php/playwright

about:
	@width=62; \
	bar=$$(head -c $$width /dev/zero | tr '\0' '─'); \
	echo " ╭$${bar}╮ "; \
	printf " │%*s\033[38;5;110m%s\033[0m \033[38;5;252m%s\033[0m%*s│\n" 24 "" "PLAYWRIGHT" "PHP" 24 ""; \
	echo " │$${bar}│ "; \
	printf " │ %-60s │\n" "       $(REPO_URL)"; \
	echo " ╰$${bar}╯ \n";

warning:
	@echo "  \033[33m⚠︎\033[0m \033[2mThis Makefile is intended for \033[0;0mPlaywright \033[38;5;110mdevelopment\033[2m.\033[0m"
	@echo "  \033[2mIt includes tools and commands for quality checks and tests.\033[0m"
	@echo "  \033[2mEnd users \033[0mdo not\033[2m need to run these commands.\033[0m"

help: about warning show-help

show-help:
	@awk 'BEGIN{FS=":.*"} \
	/^##@/{printf "\n\033[38;5;110m%s\033[0m\n", substr($$0,4); next} \
	/^##/{sub(/^##[ ]?/,"",$$0); gsub(/<code>/,"\033[38;5;110m",$$0); gsub(/<\/code>/,"\033[0m",$$0); desc=$$0; next} \
	/^[A-Za-z0-9_.-]+:/{tgt=$$1; sub(/:$$/,"",tgt); if (desc) printf "  \033[38;5;40m%-18s\033[0m %s\n", tgt, desc; desc=""}' $(MAKEFILE_LIST)

phpcsfixer:
	$(call php_cmd,vendor/bin/php-cs-fixer fix,$(call get_args,$@))

phpstan:
	$(call php_cmd,vendor/bin/phpstan analyse ,$(call get_args,$@))

phpunit:
	$(call php_cmd,vendor/bin/phpunit,$(call get_args,$@))

##@ QA

## Fix code style (<code>vendor/bin/php-cs-fixer</code>)
cs:
	@$(MAKE) phpcsfixer ARGS="--diff"

## Static analysis (<code>vendor/bin/phpstan</code>)
sa:
	@$(MAKE) phpstan ARGS="--memory-limit=-1"

## Run all CI checks (CS + SA + tests)
ci:
	@$(MAKE) cs
	@$(MAKE) sa
	@$(MAKE) test

##@ Tests

## Run all tests (<code>vendor/bin/phpunit</code>)
test:
	@$(MAKE) phpunit

## Run unit tests
test-unit:
	@$(MAKE) phpunit ARGS="--testsuite unit"

## Run integration tests
test-integration:
	@$(MAKE) phpunit ARGS="--testsuite integration"

## Run tests with code coverage
test-coverage:
	@$(MAKE) phpunit ARGS="--coverage-html coverage --colors=never"

## List playwright server processes
ps-server:
	@ps aux | grep -i '[p]laywright-server.js' || true

## Kill lingering playwright server processes
kill-server:
	@pkill -f 'playwright-server.js' 2>/dev/null || true
	@echo "Killed (if any) playwright-server.js processes."

##@ Setup

## Install PHP deps & Playwright browsers
setup:
	@$(MAKE) install-deps
	@$(MAKE) install-browsers

## Install PHP & Node dependencies
install:
	$(call run_cmd,$(PHP_CMD) composer.phar install || composer install,)
	$(call run_cmd,$(NPM) install,)

## Install Playwright browsers
install-browsers: install-browsers
	$(call run_cmd,$(PLAYWRIGHT) install --with-deps,)

%:
	@:
