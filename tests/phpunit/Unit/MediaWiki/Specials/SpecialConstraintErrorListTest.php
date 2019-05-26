<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialConstraintErrorList;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialConstraintErrorList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialConstraintErrorListTest extends \PHPUnit_Framework_TestCase {

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

		$instance = new SpecialConstraintErrorList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialConstraintErrorList' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testFindRedirectURL() {

		$instance = new SpecialConstraintErrorList();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialConstraintErrorList' )
		);

		$expected = [
			'%5D%5D+%5B%5BProcessing+error+type%3A%3Aconstraint%5D%5D',
			'&po=%3FHas+improper+value+for%7C%3FHas+processing+error+text',
			'&p=class%3Dsortable-20smwtable-2Dstriped-20smwtable-2Dclean%2Fsep%3Dul',
			'&eq=no&limit=5',
			'&bTitle=constrainterrorlist',
			'&bHelp=smw-constrainterrorlist-helplink',
			'&bMsg=smw-constrainterrorlist-intro'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->findRedirectURL( 5 )
		);
	}

}
