<?php

namespace SMW\Test;

use SMW\SimpleDictionary;

use SMWRequestOptions;

/**
 * Tests for the CacheableObjectCollector class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\Store\CacheableObjectCollector
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class CacheableObjectCollectorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Store\CacheableObjectCollector';
	}

	/**
	 * Helper method that returns a CacheableObjectCollector object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return CacheableObjectCollector
	 */
	private function getInstance( $doCollect = array(), $cacheSetup = array() ) {

		$collector = $this->newMockBuilder()->newObject( 'CacheableObjectCollector', array(
			'doCollect'  => $doCollect,
			'cacheSetup' => new SimpleDictionary( $cacheSetup )
		) );

		return $collector;
	}

	/**
	 * @test CacheableObjectCollector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test CacheableObjectCollector::getResults
	 * @dataProvider resultDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetResults( $setup, $expected ) {

		$instance = $this->getInstance( $setup['test'], $setup['cacheSetup'] );
		$instance->setRequestOptions( new SMWRequestOptions() );

		$result   = $instance->getResults();

		$this->assertInternalType( 'array', $result );
		$this->assertInternalType( $expected['cacheDate'], $instance->getCacheDate() );
		$this->assertEquals( $expected['isCached'], $instance->isCached() );
		$this->assertEquals( $expected['result'], $result );
		$this->assertEquals( $expected['count'], $instance->getCount() );

	}

	/**
	 * @var array
	 */
	public function resultDataProvider() {

		$provider = array();

		$result = array( $this->newRandomString() );

		// #0 Non-cached scenario
		$provider[] = array(
			array(
				'test'        => $result,
				'cacheSetup'  => array(
					'id'      => rand(),
					'type'    => false,
					'enabled' => false,
					'expiry'  => 100,
				)
			),
			array(
				'result'     => $result,
				'cacheDate'  => 'null',
				'isCached'   => false,
				'count'      => count( $result )
			)
		);

		// #1 Cached scenario
		$id = rand();
		$result = array( $this->getRandomString(), $this->getRandomString() );

		$provider[] = array(
			array(
				'test'        => $result,
				'cacheSetup'  => array(
					'id'      => $id,
					'type'    => 'hash',
					'enabled' => true,
					'expiry'  => 100,
				)
			),
			array(
				'result'     => $result,
				'cacheDate'  => 'null',
				'isCached'   => false,
				'count'      => count( $result )
			)
		);

		// #2 Initialized with a different 'nonRelevant' set, id is kept
		// the same and results are expected to be equal with the previous
		// initialization (cached results)
		$nonRelevant = array( 'Lula' );

		$provider[] = array(
			array(
				'test'        => $nonRelevant,
				'cacheSetup'  => array(
					'id'      => $id,
					'type'    => 'hash',
					'enabled' => true,
					'expiry'  => 100,
				)
			),
			array(
				'result'     => $result,
				'cacheDate'  => 'string',
				'isCached'   => true,
				'count'      => count( $result )
			)
		);

		return $provider;

	}

}
