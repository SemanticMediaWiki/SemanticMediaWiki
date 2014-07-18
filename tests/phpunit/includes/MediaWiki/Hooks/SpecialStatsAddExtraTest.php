<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\Application;
use SMW\StoreFactory;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialStatsAddExtra
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtraTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		Application::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$userLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$extraStats = array();
		$version = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\SpecialStatsAddExtra',
			new SpecialStatsAddExtra( $extraStats, $version, $userLanguage )
		);
	}

	/**
	 * @dataProvider statisticsDataProvider
	 */
	public function testProcessOnMockedStore( $setup, $expected ) {

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

		Application::getInstance()->registerObject( 'Store', $store );
		Application::getInstance()->getSettings()->set( 'smwgShowJobQueueStatistics', false );

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

		Application::getInstance()->registerObject( 'Store', StoreFactory::getStore() );
		Application::getInstance()->getSettings()->set( 'smwgShowJobQueueStatistics', true );

		$extraStats = array();
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

	public function testProcessJobQueueStatisticsOnMockedStore() {

		$userLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'estimateRowCount' )
			->will( $this->returnValue( 9999 ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		Application::getInstance()->registerObject( 'Store', $store );
		Application::getInstance()->getSettings()->set( 'smwgShowJobQueueStatistics', true );

		$extraStats = array();
		$version = '1.21';

		$instance = new SpecialStatsAddExtra( $extraStats, $version, $userLanguage );

		$this->assertTrue( $instance->process() );

		$this->assertTrue( $this->matchArray(
			$extraStats['smw-statistics-jobqueue'],
			'smw-statistics-jobqueue-update-count'
		) );
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

		$input = array(
			'PROPUSES' => 1001
		);

		#0 Legacy
		$provider[] = array(
			array(
				'version'    => '1.20',
				'extraStats' => array(),
				'statistics' => $input
			),
			array(
				'statistics' => 1001
			)
		);

		#1 Legacy - unknown
		$provider[] = array(
			array(
				'version'    => '1.20',
				'extraStats' => array(),
				'statistics' => array( 'Yeey' => 2002 )
			),
			array(
				'statistics' => null
			)
		);

		#2 MW 1.21+
		$provider[] = array(
			array(
				'version'    => '1.21',
				'extraStats' => array(),
				'statistics' => $input
			),
			array(
				'statistics' => 1001
			)
		);

		#3 MW 1.21+ - unknown
		$provider[] = array(
			array(
				'version'    => '1.21',
				'extraStats' => array(),
				'statistics' => array( 'Quuxy' => 2002 )
			),
			array(
				'statistics' => null
			)
		);

		return $provider;
	}

}
