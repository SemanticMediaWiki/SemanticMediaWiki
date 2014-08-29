<?php

namespace SMW\Tests\Benchmark;

/**
 * @group semantic-mediawiki-benchmark
 * @large
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ExtendedQueryEngineBenchmarkTest extends QueryEngineBenchmark {

	/**
	 * @return array
	 */
	public function getQuerySetProvider() {

		// $queryCondition, $printouts, $comments
		$querySets[] = array(
			'[[Has subobject.Has temperature::100 °F]]',
			array( 'Has date', 'Has quantity', 'Has date', 'Has Url', 'Has annotation uri', 'Has wattage', 'Has temperature', 'Has text' ),
			''
		);

		$querySets[] = array(
			'<q>[[Has page.Has number::1001]][[Has page.Has telephone number::+1-201-555-0123]]</q> OR [[Has subobject.Has temperature::100 °F]][[Category:!Lorem enim]]',
			array( 'Has date', 'Has quantity', 'Has date', 'Has Url', 'Has annotation uri', 'Has wattage', 'Has temperature', 'Has text' ),
			''
		);

		return $querySets;
	}

}
