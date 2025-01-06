<?php

namespace SMW\Tests\Benchmark;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GPL-2.0-or-later
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
