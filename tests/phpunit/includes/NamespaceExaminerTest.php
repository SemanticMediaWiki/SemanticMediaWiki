<?php

namespace SMW\Test;

use SMW\NamespaceExaminer;
use SMW\Settings;

/**
 * Tests for the NamespaceExaminer class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\NamespaceExaminer
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class NamespaceExaminerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\NamespaceExaminer';
	}

	/**
	 * Helper method that returns a NamespaceExaminer object
	 *
	 * @param array $namespaces
	 *
	 * @return NamespaceExaminer
	 */
	private function getInstance( array $namespaces = array() ) {
		return new NamespaceExaminer( $namespaces );
	}

	/**
	 * @test NamespaceExaminer::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( array( NS_MAIN => true ) );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test NamespaceExaminer::isSemanticEnabled
	 *
	 * @since 1.9
	 */
	public function testIsSemanticEnabled() {

		$instance = $this->getInstance( array( NS_MAIN => true ) );
		$this->assertTrue( $instance->isSemanticEnabled( NS_MAIN ) );

		$instance = $this->getInstance( array( NS_MAIN => false ) );
		$this->assertFalse( $instance->isSemanticEnabled( NS_MAIN ) );

		$instance = $this->getInstance();
		$this->assertFalse( $instance->isSemanticEnabled( NS_MAIN ) );

	}

	/**
	 * @test NamespaceExaminer::isSemanticEnabled
	 *
	 * @since 1.9
	 */
	public function testNoNumberException() {
		$this->setExpectedException( '\SMW\InvalidNamespaceException' );

		$instance = $this->getInstance( array( NS_MAIN => true ) );
		$this->assertTrue( $instance->isSemanticEnabled( 'lula' ) );
	}

	/**
	 * @test NamespaceExaminer::isSemanticEnabled
	 *
	 * Bug 51435; return false instead of an Exception
	 *
	 * @since 1.9
	 */
	public function testNoValidNamespaceException() {
		$instance = $this->getInstance( array( NS_MAIN => true ) );
		$this->assertFalse( $instance->isSemanticEnabled( 99991001 ) );
	}

	/**
	 * @test NamespaceExaminer::getInstance
	 *
	 * @since 1.9
	 */
	public function testGetInstance() {

		$instance = NamespaceExaminer::getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );

		// Static instance
		$this->assertTrue( $instance === NamespaceExaminer::getInstance() );

		// Reset static instance
		NamespaceExaminer::reset();
		$this->assertFalse( $instance === NamespaceExaminer::getInstance() );

	}

	/**
	 * @test NamespaceExaminer::newFromArray
	 *
	 * @since 1.9
	 */
	public function testNewFromArray() {
		$instance = NamespaceExaminer::newFromArray( array( NS_MAIN => true ) );

		$this->assertInstanceOf( $this->getClass(), $instance );
		$this->assertTrue( $instance->isSemanticEnabled( NS_MAIN ) );
	}

	/**
	 * @see smwfIsSemanticsProcessed
	 *
	 * FIXME Delete this test in 1.11
	 *
	 * @since 1.9
	 */
	public function testSmwfIsSemanticsProcessed() {
		$result = smwfIsSemanticsProcessed( NS_MAIN );

		$this->assertInternalType( 'boolean', $result );
		$this->assertTrue( $result );
	}
}
