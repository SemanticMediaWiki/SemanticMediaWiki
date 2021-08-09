<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialPendingTaskList;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialPendingTaskList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPendingTaskListTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {

		$instance = new SpecialPendingTaskList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialPendingTaskList' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testHtmlOutput() {

		$instance = new SpecialPendingTaskList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialPendingTaskList' )
		);

		$instance->getContext()->setRequest(
			new \FauxRequest( [], true )
		);

		$expected = [
			'<div class="smw-tabs smw-pendingtasks">'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

}
