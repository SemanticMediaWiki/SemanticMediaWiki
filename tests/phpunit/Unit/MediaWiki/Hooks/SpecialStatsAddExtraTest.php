<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\StoreFactory;

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

		$userLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$extraStats = [];
		$version = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\SpecialStatsAddExtra',
			new SpecialStatsAddExtra( $extraStats, $version, $userLanguage )
		);
	}

	/**
	 * @dataProvider statisticsDataProvider
	 */
	public function testProcessForMockedStore( $setup, $expected ) {

		$formatNumReturnValue = isset( $setup['statistics']['PROPUSES'] ) ? $setup['statistics']['PROPUSES'] : '';

		$userLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$userLanguage->expects( $this->any() )
			->method( 'formatNum' )
			->will( $this->returnValue( $formatNumReturnValue ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $setup['statistics'] ) );

		ApplicationFactory::getInstance()->registerObject( 'Store', $store );
		ApplicationFactory::getInstance()->getSettings()->set( 'smwgSemanticsEnabled', true );

		$extraStats = $setup['extraStats'];
		$version = $setup['version'];

		$instance = new SpecialStatsAddExtra( $extraStats, $version, $userLanguage );

		$this->assertTrue( $instance->process() );

		$this->assertTrue(
			$this->matchArray( $extraStats, $expected['statistics'] )
		);
	}

	public function testProcessOnSQLStore() {

		$userLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		ApplicationFactory::getInstance()->registerObject( 'Store', StoreFactory::getStore() );
		ApplicationFactory::getInstance()->getSettings()->set( 'smwgSemanticsEnabled', true );

		$extraStats = [];
		$version = '1.21';

		$instance = new SpecialStatsAddExtra( $extraStats, $version, $userLanguage );

		$this->assertTrue( $instance->process() );

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

		#0 Legacy
		$provider[] = [
			[
				'version'    => '1.20',
				'extraStats' => [],
				'statistics' => $input
			],
			[
				'statistics' => 1001
			]
		];

		#1 Legacy - unknown
		$provider[] = [
			[
				'version'    => '1.20',
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
				'version'    => '1.21',
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
				'version'    => '1.21',
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
