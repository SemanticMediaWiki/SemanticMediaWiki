<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Specials\SpecialSearchByProperty;
use SMW\Tests\Utils\UtilityFactory;
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

		$propertyTableIdReferenceFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTableIdReferenceFinder', 'getPropertyValues', 'getPropertySubjects', 'service' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->will( $this->returnValue( $propertyTableIdReferenceFinder ) );

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

		$request = [
			'x' => ':Has-20subobject/Foo-23%7B%7D'
		];

		$expected = [
			'property=Has+subobject', 'value=Foo%23%257B%257D'
		];

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
		$provider[] = [
			'Foo/Bar',
			[ 'property=Foo', 'value=Bar' ]
		];

		#1
		$provider[] = [
			':Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			[ 'property=Has+foo', 'value=http%3A%2F%2Fexample.org%2Fid%2FCurly%2520Brackets%257B%257D' ]
		];

		return $provider;
	}

}
