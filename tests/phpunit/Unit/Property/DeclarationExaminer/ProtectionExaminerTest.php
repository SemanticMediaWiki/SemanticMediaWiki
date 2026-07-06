<?php

namespace SMW\Tests\Unit\Property\DeclarationExaminer;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Property\DeclarationExaminer;
use SMW\Property\DeclarationExaminer\ProtectionExaminer;
use SMW\Protection\ProtectionValidator;
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
class ProtectionExaminerTest extends TestCase {

	private $declarationExaminer;
	private $protectionValidator;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->declarationExaminer = $this->getMockBuilder( DeclarationExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$this->protectionValidator = $this->getMockBuilder( ProtectionValidator::class )
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
			'smw-edit-protection-disabled',
			$instance->getMessagesAsString()
		);
	}

}
