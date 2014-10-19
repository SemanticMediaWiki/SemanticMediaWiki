<?php

namespace SMW\Test;

use SMW\NamespaceExaminer;
use SMW\Settings;

/**
 * @covers \SMW\NamespaceExaminer
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class NamespaceExaminerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			new NamespaceExaminer( array() )
		);

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			NamespaceExaminer::newFromArray( array() )
		);

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			NamespaceExaminer::getInstance()
		);
	}

	public function testIsSemanticEnabled() {

		$instance = new NamespaceExaminer( array( NS_MAIN => true ) );

		$this->assertTrue(
			$instance->isSemanticEnabled( NS_MAIN )
		);

		$instance = new NamespaceExaminer( array( NS_MAIN => false ) );

		$this->assertFalse(
			$instance->isSemanticEnabled( NS_MAIN )
		);
	}

	public function testNoNumberException() {

		$instance = new NamespaceExaminer( array( NS_MAIN => true ) );

		$this->setExpectedException( '\SMW\InvalidNamespaceException' );

		$this->assertTrue(
			$instance->isSemanticEnabled( 'ichi' )
		);
	}

	/**
	 * Bug 51435; return false instead of an Exception
	 */
	public function testNoValidNamespaceException() {

		$instance = new NamespaceExaminer( array( NS_MAIN => true ) );

		$this->assertFalse(
			$instance->isSemanticEnabled( 99991001 )
		);
	}

	public function testGetInstance() {

		$instance = NamespaceExaminer::getInstance();

		$this->assertSame(
			$instance,
			NamespaceExaminer::getInstance()
		);

		NamespaceExaminer::clear();

		$this->assertNotSame(
			$instance,
			NamespaceExaminer::getInstance()
		);
	}

	public function testNewFromArray() {

		$instance = NamespaceExaminer::newFromArray( array( NS_MAIN => true ) );

		$this->assertTrue(
			$instance->isSemanticEnabled( NS_MAIN )
		);
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
