<?php

namespace SMW\Tests\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use SMW\MediaWiki\Specials\SpecialPendingTaskList;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialPendingTaskList
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPendingTaskListTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {
		$instance = new SpecialPendingTaskList();

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialPendingTaskList' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testHtmlOutput() {
		$instance = new SpecialPendingTaskList();

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialPendingTaskList' )
		);

		$instance->getContext()->setRequest(
			new FauxRequest( [], true )
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
