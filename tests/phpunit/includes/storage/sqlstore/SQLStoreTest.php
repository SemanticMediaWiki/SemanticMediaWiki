<?php

namespace SMW\Test\SQLStore;

use SMWSQLStore3;

/**
 * Tests for the SMWSQLStore3 class
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers SMWSQLStore3
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class SQLStoreTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWSQLStore3';
	}

	/**
	 * Helper method that returns a SQLStore object
	 *
	 * @since 1.9
	 *
	 * @return SQLStore
	 */
	private function getInstance() {
		return new SMWSQLStore3();
	}

	/**
	 * @test SQLStore::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SQLStore::getPropertyTables
	 *
	 * @since 1.9
	 */
	public function testGetPropertyTables() {

		$instance = $this->getInstance();
		$result = $instance->getPropertyTables();

		$this->assertInternalType( 'array', $result );

		foreach ( $result as $tid => $propTable ) {
			$this->assertInstanceOf( '\SMW\SQLStore\TableDefinition', $propTable );
		}
	}

	/**
	 * @test SQLStore::getStatisticsTable
	 *
	 * @since 1.9
	 */
	public function testGetStatisticsTable() {

		$instance = $this->getInstance();
		$this->assertInternalType( 'string', $instance->getStatisticsTable() );

	}

	/**
	 * @test SQLStore::getObjectIds
	 *
	 * @since 1.9
	 */
	public function testGetObjectIds() {

		$instance = $this->getInstance();
		$this->assertInternalType( 'object', $instance->getObjectIds() );
		$this->assertInternalType( 'string', $instance->getObjectIds()->getIdTable() );

	}
}
