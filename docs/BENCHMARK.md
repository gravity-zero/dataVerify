## Performance

DataVerify is designed for production use with predictable, sub-millisecond performance.

### Run Benchmarks
```bash
make benchmark          # Standard benchmarks
make benchmark-stats    # With P50/P95/P99 percentiles
```

### Results (PHP 8.5.1 + OPcache)

**Core Operations** (P99):
- Simple validation: **8.7μs** (99% < 9μs)
- Complex nested: **16.4μs** (99% < 17μs)
- Custom strategy: **11.2μs** (99% < 12μs)

**Batch Processing:**
- Batch mode (100 fields): **~0.53ms**
- Fail-fast mode (100 fields): **~0.23ms** → **2.3x faster**

**Conditional Validations** (P99):
- Triggered: **8.5μs**
- Not triggered: **5.7μs** ← Faster (validation skipped)
- Failed + errors: **13μs** ← Most expensive (error rendering)
- Complex AND/OR: **~6.1μs**

**Translation Overhead:**
- With translation: **14.6μs**
- Without translation: **14.3μs**
- **Overhead: ~2-3%** (negligible)

**Memory:** ~4.9MB (stable, no leaks)

**Key Insights:**
- ✅ **99% of validations complete in <17μs** (sub-millisecond)
- ✅ **Very low variance** - P99 tightly clustered around the mean
- ✅ **Fail-fast mode 2.3x faster** when you need speed
- ✅ **Conditional skip is fast** - unused validations add minimal overhead
- ✅ **Static translator cache** eliminates file I/O overhead

*Benchmarks: [PHPBench 1.4.3](https://github.com/phpbench/phpbench) • PHP 8.5.1 • OPcache enabled*