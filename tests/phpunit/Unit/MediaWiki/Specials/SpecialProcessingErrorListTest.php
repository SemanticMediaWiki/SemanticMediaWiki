<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialProcessingErrorList;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialProcessingErrorList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialProcessingErrorListTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {

		$instance = new SpecialProcessingErrorList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialProcessingErrorList' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testGetLocalAskRedirectUrl() {

		$instance = new SpecialProcessingErrorList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialProcessingErrorList' )
		);

		$expected = [
			'%5B%5BHas+processing+error+text%3A%3A%2B%5D%5D',
			'&po=%3FHas+improper+value+for%7C%3FHas+processing+error+text',
			'&p=class%3Dsortable-20wikitable-20smwtable-2Dstriped',
			'&eq=no&limit=5',
			'&bTitle=processingerrorlist',
			'&bMsg=smw-processingerrorlist-intro'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getLocalAskRedirectUrl( 5 )
		);
	}

}
