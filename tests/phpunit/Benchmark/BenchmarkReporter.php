<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageReader;
use RuntimeException;

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
