Benchmark tests are to use `PHPUnit` as integration platform and do not always
represent the best tool for a performance comparison (as it depends on environmental
factors such as hardware and software constraints which might not be under the
control of the tester) but it can help to identify performance regressions among
newly introduced features that run with the same environmental specification.

Benchmarks are not performed in isolation and therefore run in concert with the
`MediaWiki` infrastructure to determine the overall performance impact during
execution.

When using `git`, it is relatively easy to run tests and see if a change
introduces a significant regression or improvement in terms of performance over
the existing `master` branch by comparing test results of the `master` against
a `feature` branch.

## Designing benchmark tests

The definition of what benchmarks are executed is specified by a `JSONScript`
found in the `TestCases` directory. Supported types are:

- `import` to import data from an external source
- `contentCopy` copy content from an internal source
- `editCopy` edit content from an internal source
- `job` running selected jobs
- `maintenance` running selected maintenance scripts
- `query` executing `#ask` queries

## Running benchmark tests

Running `composer benchmark` from the Semantic MediaWiki base directory should
output something similar to what can be seen below.

```
- mediawiki: "1.28.0-alpha"
- semantic-mediawiki: "2.5.0-alpha"
- environment: {"store":"SMWSQLStore3","db":"mysql"}
- benchmarks
- 35a205a6fa1db2cda4c484d3007953b3
 - type: "import"
 - source: "import-001.xml"
 - memory: 5564360
 - time: {"sum":5.9888241}
- 054543b5702e6fcbccafd00bf6dd27ac
 - type: "contentCopy"
 - source: "import-001.xml"
  - import
   - memory: 351392
   - time: {"sum":0.900929}
  - copy
   - copyFrom: "Lorem ipsum"
   - copyCount: 10
   - memory: 75056
   - time: {"sum":7.9295225,"mean":0.7929523,"sd":0.1008703,"norm":0.0792952}
 - time: 8.8402690887451

```
