## Benchmark tests

Benchmark tests use PHPUnit as integration platform and are not always the right tool to represent a performance yardstick (as it depends on environmental factors such as hardware and software constraints and might not be under the control of the test environment) but can identify performance regressions among newly introduced features.

- `ImportPageCopyBenchmarkTest` to perform import and page copy benchmarks
- `JobQueueBenchmarkTest` to gather data on the jobs
- `MaintenanceBenchmarkTest` to gather data on the maintenance scripts
- `StandardQueryEngineBenchmarkTest` to perform tests for different query conditions (ought to be executable on each store)
- `ExtraQueryEngineBenchmarkTest` to perform tests for query conditions that might not be executable on each store
- `PageEditBenchmarkTest` to perform edits for different (#set, #subobject, template) annotation methods

Benchmarks are not performed in isolation and run in concert with the `MediaWiki` infrastucture to determine the overall performance impact during execution.

### Use PHPUnit

When running PHPUnit, use `--group semantic-mediawiki-benchmark` to indicate whether an annotated benchmark test is expected to perform an output and is run according to the the listed order of `phpunit.xml.dist`.

### Benchmark git changes

When using `git`, it is relatively easy to run tests and see if a change introduces a significant regression or improvement in terms of performance over the existing `master` branch by comparing test results of the `master` against a `feature` branch.

### Benchmark conditions

`phpunit.xml.dist` can be used to adjust basic conditions of the benchmark environment. Available parameters are:

- `benchmarkQueryRepetitionExecutionThreshold` a value that specifies how many repetitions should be made per query (is to increase the mean value computation accuracy)
- `benchmarkQueryLimit` a value to specify the query limit
- `benchmarkQueryOffset` a value to specify the query offset
- `benchmarkPageCopyThreshold` a value to specify how many pages should be copied and made available during a test
- `benchmarkShowMemoryUsage` setting to display memory usage during a benchmark test
- `benchmarkReuseDatasets` whether to reuse imported datasets (by `ImportPageCopyBenchmarkTest`) or not

## See also
- Running Semantic MediaWiki related [Benchmark HHVM 3.3 vs. Zend PHP 5.6](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/513) tests
