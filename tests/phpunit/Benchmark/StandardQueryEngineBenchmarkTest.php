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
class StandardQueryEngineBenchmarkTest extends QueryEngineBenchmark {

	/**
	 * @return array
	 */
	public function getQuerySetProvider() {

		// $queryCondition, $printouts, $comments
		$querySets[] = array(
			'[[:+]]',
			array(),
			''
		);

		$querySets[] = array(
			'[[Category: Lorem ipsum]]',
			array(),
			''
		);

		$querySets[] = array(
			'[[Category: Lorem ipsum]] AND [[Property:+]]',
			array(),
			''
		);

		$querySets[] = array(
			'[[Has Url::+]]',
			array( 'Has Url' ),
			'(includes subobjects)'
		);

		$querySets[] = array(
			'[[Has quantity::+]]',
			array( 'Has quantity' ),
			'(includes subobjects)'
		);

		$querySets[] = array(
			'[[Has Url::+]][[Category: Lorem ipsum]]',
			array( 'Has Url' ),
			'(does not include subobjects)'
		);

		$querySets[] = array(
			'[[Has number::1111]][[Has quantity::25 sqmi]]',
			array( 'Has number', 'Has quantity' ),
			'(only subobjects)'
		);

		$querySets[] = array(
			'[[Has number::1111]] OR [[Has quantity::25 sqmi]]',
			array( 'Has number', 'Has quantity' ),
			'(only subobjects)'
		);

		$querySets[] = array(
			'[[Has date::1 Jan 2014]]',
			array( 'Has date' ),
			'(does not include subobjects)'
		);

		$querySets[] = array(
			'[[Has text::~Lorem ipsum dolor*]][[Category: Lorem ipsum]]',
			array( 'Has text' ),
			'(does not include subobjects)'
		);

		return $querySets;
	}

}
