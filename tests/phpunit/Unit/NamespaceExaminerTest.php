<?php

namespace SMW\Tests;

use SMW\NamespaceExaminer;

/**
 * @covers \SMW\NamespaceExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class NamespaceExaminerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NamespaceExaminer::class,
			new NamespaceExaminer( [] )
		);
	}

	public function testInNamespace_Title() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$instance = new NamespaceExaminer( [ NS_MAIN => true ] );
		$instance->setValidNamespaces( [ NS_MAIN ] );

		$this->assertTrue(
			$instance->inNamespace( $title )
		);

		$instance->setValidNamespaces( [] );

		$this->assertFalse(
			$instance->inNamespace( $title )
		);
	}

	public function testInNamespace_DIWikiPage() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$instance = new NamespaceExaminer( [ NS_MAIN => true ] );
		$instance->setValidNamespaces( [ NS_MAIN ] );

		$this->assertTrue(
			$instance->inNamespace( $subject )
		);

		$instance->setValidNamespaces( [] );

		$this->assertFalse(
			$instance->inNamespace( $subject )
		);
	}

	public function testIsSemanticEnabled() {

		$instance = new NamespaceExaminer( [ NS_MAIN => true ] );
		$instance->setValidNamespaces( [ NS_MAIN ] );

		$this->assertTrue(
			$instance->isSemanticEnabled( NS_MAIN )
		);

		$instance = new NamespaceExaminer( [ NS_MAIN => false ] );

		$this->assertFalse(
			$instance->isSemanticEnabled( NS_MAIN )
		);
	}

	public function testNoNumberNamespaceThrowsException() {

		$instance = new NamespaceExaminer( [ NS_MAIN => true ] );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->isSemanticEnabled( 'ichi' );
	}

	/**
	 * Bug 51435; return false instead of an Exception
	 */
	public function testNoValidNamespaceException() {

		$instance = new NamespaceExaminer( [ NS_MAIN => true ] );

		$this->assertFalse(
			$instance->isSemanticEnabled( 99991001 )
		);
	}

}
