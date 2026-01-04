## Performance

DataVerify is designed for production use with predictable, sub-millisecond performance.

### Run Benchmarks
```bash
make benchmark          # Standard benchmarks
make benchmark-stats    # With P50/P95/P99 percentiles
```

### Results (PHP 8.5.1)

**Core Operations** (P99):
- Simple validation: **22.3μs** (99% < 23μs)
- Complex nested: **41.7μs** (99% < 42μs)
- Custom strategy: **21.4μs** (99% < 22μs)

**Batch Processing:**
- Batch mode (100 fields): **1.42ms**
- Fail-fast mode (100 fields): **0.65ms** → **2.2x faster**

**Conditional Validations** (P99):
- Triggered: **24.2μs**
- Not triggered: **16.3μs** ← Faster (validation skipped)
- Failed + errors: **61μs** ← Most expensive (error rendering)
- Complex AND/OR: **~17μs**

**Translation Overhead:**
- With translation: **46μs**
- Without translation: **47.5μs**
- **Overhead: ~3%** (negligible)

**Memory:** ~4.9MB (stable, no leaks)

**Key Insights:**
- ✅ **99% of validations complete in <42μs** (sub-millisecond)
- ✅ **Very stable** - P99 within 5% of mean (low variance)
- ✅ **Fail-fast mode 2.2x faster** when you need speed
- ✅ **Conditional skip is fast** - unused validations add minimal overhead

*Benchmarks: [PHPBench 1.4.3](https://github.com/phpbench/phpbench) • PHP 8.5.1 • No opcache/xdebug*