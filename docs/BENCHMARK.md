## Performance

DataVerify is designed for production use with predictable, sub-millisecond performance.

### Run Benchmarks
```bash
make benchmark          # Standard benchmarks
make benchmark-stats    # With P50/P95/P99 percentiles
```

### Results (PHP 8.5.1)

**Core Operations** (P99):
- Simple validation: **49.6μs** (99% < 50μs)
- Complex nested: **71.8μs** (99% < 72μs)
- Custom strategy: **53.6μs** (99% < 54μs)

**Batch Processing:**
- Batch mode (100 fields): **1.5ms**
- Fail-fast mode (100 fields): **0.68ms** → **2.1x faster**

**Conditional Validations** (P99):
- Triggered: **50.4μs**
- Not triggered: **42.2μs** ← Faster (validation skipped)
- Failed + errors: **62.7μs** ← Most expensive (error rendering)
- Complex AND/OR: **~44μs**

**Translation Overhead:**
- With translation: **50.4μs**
- Without translation: **48.5μs**
- **Overhead: ~4%** (negligible)

**Memory:** ~4.9MB (stable, no leaks)

**Key Insights:**
- ✅ **99% of validations complete in <72μs** (sub-millisecond)
- ✅ **Very stable** - P99 within 5% of mean (low variance)
- ✅ **Fail-fast mode 2x faster** when you need speed
- ✅ **Conditional skip is fast** - unused validations add minimal overhead

*Benchmarks: [PHPBench 1.4.3](https://github.com/phpbench/phpbench) • PHP 8.5.1 • No opcache/xdebug*