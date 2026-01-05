## Performance

DataVerify is designed for production use with predictable, sub-millisecond performance.

### Run Benchmarks
```bash
make benchmark          # Standard benchmarks
make benchmark-stats    # With P50/P95/P99 percentiles
```

### Results (PHP 8.5.1 + OPcache)

**Core Operations** (P99):
- Simple validation: **8.8μs** (99% < 9μs)
- Complex nested: **17.1μs** (99% < 18μs)
- Custom strategy: **11.4μs** (99% < 12μs)

**Batch Processing:**
- Batch mode (100 fields): **1.15ms**
- Fail-fast mode (100 fields): **0.23ms** → **5x faster**

**Conditional Validations** (P99):
- Triggered: **8.9μs**
- Not triggered: **5.7μs** ← Faster (validation skipped)
- Failed + errors: **14.5μs** ← Most expensive (error rendering)
- Complex AND/OR: **~6.1μs**

**Translation Overhead:**
- With translation: **14.6μs**
- Without translation: **14.3μs**
- **Overhead: ~2%** (negligible)

**Memory:** ~4.9MB (stable, no leaks)

**Key Insights:**
- ✅ **99% of validations complete in <18μs** (sub-millisecond)
- ✅ **Very stable** - P99 within 5% of mean (low variance)
- ✅ **Fail-fast mode 5x faster** when you need speed
- ✅ **Conditional skip is fast** - unused validations add minimal overhead
- ✅ **Static translator cache** eliminates file I/O overhead

*Benchmarks: [PHPBench 1.4.3](https://github.com/phpbench/phpbench) • PHP 8.5.1 • OPcache enabled*