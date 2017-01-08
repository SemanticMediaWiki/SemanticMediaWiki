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

		$this->testEnvironment = new TestEnvironment( array(
			'smwgBrowseShowInverse' => false,
			'smwgBrowseShowAll'     => true,
			'smwgBrowseByApi'       => true
		) );

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
		$provider[] = array(
			'',
			array( 'smw-callout smw-callout-error' )
		);

		#1
		$provider[] = array(
			':Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			array( 'smw-callout smw-callout-error' )
		);

		#2
		$provider[] = array(
			'Foo/Bar',
			array(
				'data-subject="Foo/Bar#0#"',
				'data-options="{&quot;dir&quot;:null,&quot;offset&quot;:null,&quot;printable&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;including&quot;:null}"'
			)
		);

		#3
		$provider[] = array(
			':Main-20Page-23_QUERY140d50d705e9566904fc4a877c755964',
			array(
				'data-subject="Main_Page#0##_QUERY140d50d705e9566904fc4a877c755964"',
				'data-options="{&quot;dir&quot;:null,&quot;offset&quot;:null,&quot;printable&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;including&quot;:null}"'
			)
		);

		return $provider;
	}

}
