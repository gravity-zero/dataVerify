.PHONY: test stan mutation benchmark p99 docs docs-serve

test:
	@vendor/bin/phpunit --colors --display-warnings --testdox

stan:
	@vendor/bin/phpstan analyse -l max src

mutation:
	@vendor/bin/infection --threads=4

benchmark:
	sudo mv /etc/php/8.5/cli/conf.d/20-pcov.ini /etc/php/8.5/cli/conf.d/20-pcov.ini.bak
	@vendor/bin/phpbench run --report=default --warmup=2 --output=csv > benchmarks/bench_results.csv
	sudo mv /etc/php/8.5/cli/conf.d/20-pcov.ini.bak /etc/php/8.5/cli/conf.d/20-pcov.ini

p99:
	@cd benchmarks && php analyze_bench.php

docs:
	@./generate-docs.php

docs-serve:
	@echo "Starting documentation server on http://localhost:8000"
	@php -S localhost:8000 -t docs