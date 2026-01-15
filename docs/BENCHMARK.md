## Performance

**[← Back to Main Documentation](../README.md)**

**Navigation:** [Validations](VALIDATIONS.md) | [Conditional Validation](CONDITIONAL_VALIDATION.md) | [Rules & Schemas](RULES_AND_SCHEMAS.md) | [Custom Strategies](CUSTOM_STRATEGIES.md) | [Error Handling](ERROR_HANDLING.md) | [Internationalization](INTERNATIONALIZATION.md) | [Benchmarks](BENCHMARK.md)

### Run Benchmarks
```bash
make benchmark          # Standard benchmarks
make p99                # With P50/P95/P99 percentiles
```

### Results (PHP 8.5.1 + OPcache)

**Core Operations** (P99):
- Simple validation: **9.7μs**
- Complex nested: **19.1μs**
- Custom strategy: **14.2μs**

**Batch Processing:**
- Batch mode (100 fields): **572.9μs**
- Fail-fast mode (100 fields): **256.1μs** → 2x faster

**Conditional Validations** (P99):
- Triggered: **10.2μs**
- Not triggered: **8.3μs** ← Faster (validation skipped)
- Failed + errors: **16.2μs** ← Most expensive (error rendering)
- Complex AND/OR: **7.8μs**
- Deferred evaluation: **11.9μs**

**Custom Validation Strategies** (P99):
- Custom strategy execution: **14.2μs**
- Load from directory (bootstrap): **29.4μs**

**Rules & Schemas** (P99):
- Rule registration: **1.4μs**
- Rule application: **5.9μs**
- Schema registration: **4.6μs**
- Schema application: **7.7μs**
- Schema vs manual: **7.7μs vs 12.3μs**
- Schema with rules: **10.0μs**
- Schema with conditionals: **9.4μs**

**Batch Loading:**
- Load 20 rules: **20.6μs**
- Load 10 schemas: **45.9μs**

**Translation:**
- With translation: **17.3μs**
- Without translation: **16.7μs**

**Memory:** ~2MB

### FrankenPHP Worker Mode

Tested for long-running process stability:

| Metric | Result |
|--------|--------|
| Requests | 3M+ |
| Memory | 2MB stable (Δ0 after warmup) |
| Throughput | ~3400 req/s (4 workers) |
| Errors | 0 |

✅ **Production-ready** for worker mode - no memory leaks detected.

**Key Insights:**
- ✅ 99% of validations complete in <20μs
- ✅ Fail-fast mode is 2.2x faster than batch mode
- ✅ Schemas are 37% faster than manual validation (7.7μs vs 12.3μs)
- ✅ Translation overhead is minimal (~3.6%)
- ✅ Batch loading is efficient: 1μs per rule, 4.6μs per schema

*Benchmarks: [PHPBench 1.4.3](https://github.com/phpbench/phpbench) • PHP 8.5.1 • OPcache enabled*

---

**Navigation:** [Validations](VALIDATIONS.md) | [Conditional Validation](CONDITIONAL_VALIDATION.md) | [Rules & Schemas](RULES_AND_SCHEMAS.md) | [Custom Strategies](CUSTOM_STRATEGIES.md) | [Error Handling](ERROR_HANDLING.md) | [Internationalization](INTERNATIONALIZATION.md) | [Benchmarks](BENCHMARK.md)

**[← Back to Main Documentation](../README.md)**
