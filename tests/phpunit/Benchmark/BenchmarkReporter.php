<?php

namespace SMW\Tests\Benchmark;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface BenchmarkReporter {

	/**
	 * @since 2.5
	 *
	 * @param array
	 */
	public function getBenchmarkReport();

}
