<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialStatsAddExtra
 * @group smenatic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtraTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			SpecialStatsAddExtra::class,
			new SpecialStatsAddExtra( $store )
		);
	}

	/**
	 * @dataProvider statisticsDataProvider
	 */
	public function testProcess( $setup, $expected ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $setup['statistics'] ) );

		$extraStats = $setup['extraStats'];

		$instance = new SpecialStatsAddExtra( $store );

		$instance->setOptions(
			[
				'smwgSemanticsEnabled' => true
			]
		);

		$this->assertTrue(
			$instance->process( $extraStats )
		);

		$this->assertTrue(
			$this->matchArray( $extraStats, $expected['statistics'] )
		);
	}

	public function testProcessOnSQLStore() {

		$extraStats = [];

		$instance = new SpecialStatsAddExtra(
			ApplicationFactory::getInstance()->getStore()
		);

		$instance->setOptions(
			[
				'smwgSemanticsEnabled' => true
			]
		);

		$this->assertTrue(
			$instance->process( $extraStats )
		);

		// This is a "cheap" check against the SQLStore as it could return any
		// value therefore we use a message key as only known constant to verify
		// that the matching process was successful
		$this->assertTrue(
			$this->matchArray( $extraStats, 'smw-statistics-property-instance' )
		);
	}

	public function matchArray( array $matcher, $searchValue ) {

		foreach ( $matcher as $key => $value ) {

			if ( $searchValue === $key || $searchValue === $value ) {
				return true;
			};

			if ( is_array( $value ) ) {
				return $this->matchArray( $value, $searchValue );
			};
		}

		return $searchValue !== null ? false : true;
	}

	public function statisticsDataProvider() {

		$input = [
			'PROPUSES' => 1001
		];

		#0
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => $input
			],
			[
				'statistics' => 1001
			]
		];

		#1 unknown
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => [ 'Yeey' => 2002 ]
			],
			[
				'statistics' => null
			]
		];

		#2 MW 1.21+
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => $input
			],
			[
				'statistics' => 1001
			]
		];

		#3 MW 1.21+ - unknown
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => [ 'Quuxy' => 2002 ]
			],
			[
				'statistics' => null
			]
		];

		return $provider;
	}

}
