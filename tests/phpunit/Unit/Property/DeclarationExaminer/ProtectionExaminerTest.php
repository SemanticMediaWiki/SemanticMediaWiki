<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use SMW\Property\DeclarationExaminer\ProtectionExaminer;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminer\ProtectionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ProtectionExaminerTest extends \PHPUnit_Framework_TestCase {

	private $declarationExaminer;
	private $protectionValidator;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->will( $this->returnValue( [] ) );

		$this->protectionValidator = $this->getMockBuilder( '\SMW\Protection\ProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ProtectionExaminer::class,
			new ProtectionExaminer( $this->declarationExaminer, $this->protectionValidator )
		);
	}

	public function testCreateProtectionRight() {

		$this->declarationExaminer->expects( $this->any() )
			->method( 'isLocked' )
			->will( $this->returnValue( false ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasCreateProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getCreateProtectionRight' )
			->will( $this->returnValue( 'abc' ) );

		$dataItemFactory = new DataItemFactory();

		$instance = new ProtectionExaminer(
			$this->declarationExaminer,
			$this->protectionValidator
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Test:CreateProtectionRight' )
		);

		$this->assertContains(
			'["warning","smw-create-protection","Test:CreateProtectionRight","abc"]',
			$instance->getMessagesAsString()
		);
	}

	public function testEditProtectionRight() {

		$this->declarationExaminer->expects( $this->any() )
			->method( 'isLocked' )
			->will( $this->returnValue( false ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( 'abc_123' ) );

		$dataItemFactory = new DataItemFactory();

		$instance = new ProtectionExaminer(
			$this->declarationExaminer,
			$this->protectionValidator
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Test:EditProtectionRight' )
		);

		$this->assertContains(
			'["error","smw-edit-protection","abc_123"]',
			$instance->getMessagesAsString()
		);
	}

	public function testIsEditProtectedProperty() {

		$this->declarationExaminer->expects( $this->any() )
			->method( 'isLocked' )
			->will( $this->returnValue( false ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( false ) );

		$dataItemFactory = new DataItemFactory();

		$instance = new ProtectionExaminer(
			$this->declarationExaminer,
			$this->protectionValidator
		);

		$instance->check(
			$dataItemFactory->newDIProperty( '_EDIP' )
		);

		$this->assertContains(
			'smw-edit-protection-disabled',
			$instance->getMessagesAsString()
		);
	}

}
