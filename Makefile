.PHONY: test stan mutation benchmark p99 docs docs-serve

test:
	bash tests.sh --full

stan:
	@vendor/bin/phpstan analyse src tests

mutation:
	@vendor/bin/infection --threads=4

benchmark:
	@vendor/bin/phpbench run --report=default --iterations=10 --revs=1000 --warmup=2 --output=csv > benchmarks/bench_results.csv

p99:
	@cd benchmarks && php analyze_bench.php

docs:
	@./generate-docs.php

docs-serve:
	@echo "Starting documentation server on http://localhost:8000"
	@php -S localhost:8000 -t docs