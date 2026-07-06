<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialSearchByProperty;
use SMW\Settings;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialSearchByProperty
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SpecialSearchByPropertyTest extends TestCase {

	private $store;
	private $settings;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$propertyTableIdReferenceFinder = $this->getMockBuilder( PropertyTableIdReferenceFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTableIdReferenceFinder', 'getPropertyValues', 'getPropertySubjects', 'service' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->willReturn( $propertyTableIdReferenceFinder );

		$this->settings = $this->createMock( Settings::class );

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecialSearchByProperty::class,
			new SpecialSearchByProperty( $this->store, $this->settings )
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testQueryParameter( $query, $expected ) {
		$instance = new SpecialSearchByProperty( $this->store, $this->settings );
		$instance->getContext()->setTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SearchByProperty' ) );

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

		$instance = new SpecialSearchByProperty( $this->store, $this->settings );
		$instance->getContext()->setTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SearchByProperty' ) );
		$instance->getContext()->setRequest( new FauxRequest( $request, true ) );

		$instance->execute( null );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function queryParameterProvider() {
		# 0
		$provider[] = [
			'Foo/Bar',
			[ 'property=Foo', 'value=Bar' ]
		];

		# 1
		$provider[] = [
			':Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			[ 'property=Has+foo', 'value=http%3A%2F%2Fexample.org%2Fid%2FCurly%2520Brackets%257B%257D' ]
		];

		return $provider;
	}

}
