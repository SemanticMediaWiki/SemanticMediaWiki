<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialBrowse;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialBrowse
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SpecialBrowseTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgBrowseFeatures' => SMW_BROWSE_SHOW_INCOMING | SMW_BROWSE_USE_API
		] );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testQueryParameter( $query, $expected ) {

		$instance = new SpecialBrowse();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialBrowse' )
		);

		$instance->execute( $query );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function queryParameterProvider() {

		#0
		$provider[] = [
			'',
			[ 'smw-callout smw-callout-error' ]
		];

		#1
		$provider[] = [
			':Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			[ 'smw-callout smw-callout-error' ]
		];

		#2
		$provider[] = [
			'Foo/Bar',
			[
				'data-subject="Foo/Bar#0##"',
				'data-options="{&quot;dir&quot;:null,&quot;group&quot;:null,&quot;printable&quot;:null,&quot;offset&quot;:null,&quot;including&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;showGroup&quot;:false,&quot;showSort&quot;:false,&quot;api&quot;:true,&quot;valuelistlimit.out&quot;:&quot;30&quot;,&quot;valuelistlimit.in&quot;:&quot;20&quot;}"'
			]
		];

		#3
		$provider[] = [
			':Main-20Page-23_QUERY140d50d705e9566904fc4a877c755964',
			[
				'data-subject="Main_Page#0##_QUERY140d50d705e9566904fc4a877c755964"',
				'data-options="{&quot;dir&quot;:null,&quot;group&quot;:null,&quot;printable&quot;:null,&quot;offset&quot;:null,&quot;including&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;showGroup&quot;:false,&quot;showSort&quot;:false,&quot;api&quot;:true,&quot;valuelistlimit.out&quot;:&quot;30&quot;,&quot;valuelistlimit.in&quot;:&quot;20&quot;}"'
			]
		];

		return $provider;
	}

}
