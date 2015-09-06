<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\Specials\SpecialSearchByProperty;

use SMW\ApplicationFactory;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialSearchByProperty
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SpecialSearchByPropertyTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

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
	public function testQueryParameter( $query, $expected ) {

		$instance = new SpecialSearchByProperty();
		$instance->getContext()->setTitle( Title::newFromText( 'SearchByProperty' ) );

		$instance->execute( $query );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function testXRequestParameter() {

		$request = array(
			'x' => 'Has-20subobject/Foo-23%7B%7D'
		);

		$expected = array(
			'property=Has+subobject', 'value=Foo%23%7B%7D'
		);

		$instance = new SpecialSearchByProperty();
		$instance->getContext()->setTitle( Title::newFromText( 'SearchByProperty' ) );
		$instance->getContext()->setRequest( new \FauxRequest( $request, true ) );

		$instance->execute( null );

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

		#2
		$provider[] = array(
			'Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			array( 'property=Has+foo', 'value=http%3A-2F-2Fexample.org-2Fid-2FCurly%2520Brackets%257B%257D' )
		);

		return $provider;
	}

}
