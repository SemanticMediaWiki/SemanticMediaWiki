<?php

namespace SMW\Tests\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPropertyLabelSimilarityTest extends \PHPUnit\Framework\TestCase {

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
		$instance = new SpecialPropertyLabelSimilarity();

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialPropertyLabelSimilarity' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

}
