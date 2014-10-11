<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\Tests\Util\UtilityFactory;
use SMW\MediaWiki\Specials\SpecialSearchByProperty;

use SMW\Application;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialSearchByProperty
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-specials
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SpecialSearchByPropertyTest extends \PHPUnit_Framework_TestCase {

	private $application;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->application->registerObject( 'Store', $store );

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialSearchByProperty',
			new SpecialSearchByProperty()
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testExecute( $query, $expected ) {

		$instance = new SpecialSearchByProperty();
		$instance->getContext()->setTitle( Title::newFromText( 'SearchByProperty' ) );

		$instance->execute( $query );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function queryParameterProvider() {

		#0
		$provider[] = array(
			'',
			array( 'value=""' )
		);

		#1
		$provider[] = array(
			'Foo/Bar',
			array( 'property=Foo', 'value=Bar' )
		);

		return $provider;
	}

}
