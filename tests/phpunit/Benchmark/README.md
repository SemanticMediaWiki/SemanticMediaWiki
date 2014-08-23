## Benchmark tests

Benchmark tests use PHPUnit as integration platform and not always the right tool to represent a performance yardstick (depends on different environmental parameters such as hardware and software constraints which are outside of the control of the test environment) but can identify performance regressions among newly introduced features.

- `QueryEngineBenchmarkTest` to perform tests on different query conditions
- `JobQueueBenchmarkTest` to gather data on update and refresh jobs
- `RebuildDataBenchmarkTest` to gather data on the rebuild data script

Benchmarks are not performed in isolation and use the `MediaWiki` infrastucture as integral part to determine the overall performance during execution.

### Use PHPUnit

When running PHPUnit, use `--group semantic-mediawiki-benchmark` to indicate whether an annotated benchmark test is expected to perform an output.

### Benchmark git changes

When using `git`, it is relatively easy to run tests and see if a change introduces a significant regression or improvement in terms of performance over the existing `master` branch by comparing test results of the `master` against a `feature` branch.

### Benchmark conditions

`phpunit.xml.dist` can be used to adjust basic conditions of the benchmark environment. Available parameters are:
- `benchmarkQueryRepetitionExecutionThreshold` a value that specifies how many repetitions should be made per query (is to increase the mean value computation accuracy)
- `benchmarkQueryLimit` a value to specify the query limit
- `benchmarkQueryOffset` a value to specify the query offset
- `benchmarkPageCopyThreshold` a value to specify how many pages should be copied and made available during a test
- `benchmarkShowMemoryUsage` setting to display memory usage during a benchmark test
- `benchmarkReuseDatasets` indicating whether to reuse existing datasets during a benchmark run or not (to be used for large datasets like `benchmarkPageCopyThreshold` > 500 )

## See also 
- [Benchmark HHVM 3.3 vs. Zend PHP 5.6](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/513)
