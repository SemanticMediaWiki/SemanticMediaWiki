## Benchmark tests

Benchmark tests are to use PHPUnit as integration platform and do not always represent the best tool for a performance comparison (as it depends on environmental factors such as hardware and software constraints which might not be under the control of the tester) but it can help to identify performance regressions among newly introduced features that run with the same environmental specification.

- `ImportPageCopyBenchmarkTest` to perform import and page copy benchmarks
- `JobQueueBenchmarkTest` to gather data for listed jobs
- `MaintenanceBenchmarkTest` to gather data for listed maintenance scripts
- `StandardQueryEngineBenchmarkTest` to perform tests for different query conditions that are ought to be executable on each store
- `ExtraQueryEngineBenchmarkTest` to perform tests for query conditions that might not be executable on each store
- `PageEditBenchmarkTest` to perform edits for different (#set, #subobject, template) annotation methods

Benchmarks are not performed in isolation and therefore run in concert with the `MediaWiki` infrastucture to determine the overall performance impact during execution.

### Use PHPUnit

When running PHPUnit, use `--group semantic-mediawiki-benchmark` to indicate whether an annotated benchmark test is expected to perform an output and is run according to the listed order described by `phpunit.xml.dist`.

The following [video][video] demonstrates on how to install PHPUnit and to run the benchmark tests from a shell environment.

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

[video]: https://vimeo.com/108833255
