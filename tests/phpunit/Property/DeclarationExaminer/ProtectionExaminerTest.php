<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use SMW\DataItemFactory;
use SMW\Property\DeclarationExaminer\ProtectionExaminer;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminer\ProtectionExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ProtectionExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $declarationExaminer;
	private $protectionValidator;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$this->protectionValidator = $this->getMockBuilder( '\SMW\Protection\ProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
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
			->willReturn( false );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasCreateProtection' )
			->willReturn( true );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getCreateProtectionRight' )
			->willReturn( 'abc' );

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
			->willReturn( false );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtection' )
			->willReturn( true );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->willReturn( 'abc_123' );

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
			->willReturn( false );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtection' )
			->willReturn( true );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->willReturn( false );

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
