<?php

namespace SMW\Test;

use SMW\SpecialStatsAddExtra;
use SMW\ExtensionContext;

/**
 * @covers \SMW\SpecialStatsAddExtra
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtraTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SpecialStatsAddExtra';
	}

	/**
	 * @since 1.9
	 *
	 * @return SpecialStatsAddExtra
	 */
	private function newInstance( &$extraStats = array(), $version = null ) {

		if ( $version === null ) {
			$version = '1.21';
		}

		$context = new ExtensionContext();
		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		$instance = new SpecialStatsAddExtra( $extraStats, $version, $this->getLanguage() );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testProcess() {

		$instance = $this->newInstance();

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

	}

	/**
	 * @dataProvider statisticsDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessOnMockStore( $setup, $expected ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getStatistics' => $setup['statistics']
		) );

		$extraStats = $setup['extraStats'];
		$instance   = $this->newInstance( $extraStats, $setup['version'] );
		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $mockStore );

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

		$this->assertTrue(
			$this->matchArray( $extraStats, $expected['statistics'] ),
			'Asserts that $extraStats contains an expected value'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testProcessOnSQLStore() {

		$extraStats = array();
		$instance   = $this->newInstance( $extraStats, '1.21' );
		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', \SMW\StoreFactory::getStore() );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		// This is a "cheap" check against the SQLStore as it could return any
		// value therefore we use a message key as only known constant to verify
		// that the matching process was successful
		$this->assertTrue(
			$this->matchArray( $extraStats, 'smw-statistics-property-instance' ),
			'asserts that $extraStats contains an expected key'
		);

	}

	/**
	 * @return boolean
	 */
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

	/**
	 * @return array
	 */
	public function statisticsDataProvider() {

		$provider = array();

		$input = array(
			'PROPUSES' => 1001
		);

		// #0 Legacy
		$provider[] = array(
			array(
				'version'    => '1.20',
				'extraStats' => array(),
				'statistics' => $input
			),
			array(
				'statistics' => '1,001'
			)
		);

		// #1 Legacy - unknown
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

		// #2 MW 1.21+
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

		// #3 MW 1.21+ - unknown
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
