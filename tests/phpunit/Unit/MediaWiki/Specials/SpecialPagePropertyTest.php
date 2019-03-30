<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialPageProperty;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialPageProperty
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialPagePropertyTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues', 'service' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialPageProperty',
			new SpecialPageProperty()
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testQueryParameter( $query, $expected ) {

		$instance = new SpecialPageProperty();

		$instance->getContext()->setTitle(
			Title::newFromText( 'PageProperty' )
		);

		$instance->execute( $query );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function testRequestParameter() {

		$request = [
			'type' => 'Has subobject',
			'from' => 'Bar'
		];

		$expected = [
			'value="Has subobject"', 'value="Bar"'
		];

		$instance = new SpecialPageProperty();

		$instance->getContext()->setTitle(
			Title::newFromText( 'PageProperty' )
		);

		$instance->getContext()->setRequest(
			new \FauxRequest( $request, true )
		);

		$instance->execute( null );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function queryParameterProvider() {

		#0
		$provider[] = [
			'Has page::Has prop',
			[ 'type=Has+prop', 'from=Has+page' ]
		];

		return $provider;
	}

}
