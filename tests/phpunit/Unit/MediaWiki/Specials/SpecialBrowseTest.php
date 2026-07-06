<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialBrowse;
use SMW\SerializerFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialBrowse
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SpecialBrowseTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $settings;
	private $serializerFactory;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgBrowseFeatures' => [ 'show-incoming', 'use-api' ]
		] );

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$this->settings = ApplicationFactory::getInstance()->getSettings();

		$this->serializerFactory = $this->createMock( SerializerFactory::class );

		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testQueryParameter( $query, $expected ) {
		$instance = new SpecialBrowse( $this->store, $this->settings, $this->serializerFactory );
		$services = MediaWikiServices::getInstance();

		$instance->getContext()->setTitle(
			$services->getTitleFactory()->newFromText( 'SpecialBrowse' )
		);

		$languageFactory = $services->getLanguageFactory();
		$instance->getContext()->setLanguage(
			$languageFactory->getLanguage( 'en' )
		);

		$instance->execute( $query );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getOutput()->getHtml()
		);
	}

	public function queryParameterProvider() {
		# 0
		$provider[] = [
			'',
			[ 'smw-error-browse' ]
		];

		# 1
		$provider[] = [
			':Has-20foo/http:-2F-2Fexample.org-2Fid-2FCurly-2520Brackets-257B-257D',
			[ 'smw-error-browse' ]
		];

		# 2
		$provider[] = [
			'Foo/Bar',
			[
				'data-mw-smw-browse-subject="{&quot;dbkey&quot;:&quot;Foo\/Bar&quot;,&quot;ns&quot;:0,&quot;iw&quot;:&quot;&quot;,&quot;subobject&quot;:&quot;&quot;}"',
				'data-mw-smw-browse-options="{&quot;dir&quot;:null,&quot;lang&quot;:&quot;en&quot;,&quot;group&quot;:null,&quot;printable&quot;:null,&quot;offset&quot;:null,&quot;including&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;showGroup&quot;:false,&quot;showSort&quot;:false,&quot;api&quot;:true,&quot;valuelistlimit.out&quot;:&quot;30&quot;,&quot;valuelistlimit.in&quot;:&quot;20&quot;}"'
			]
		];

		# 3
		$provider[] = [
			':Main-20Page-23_QUERY140d50d705e9566904fc4a877c755964',
			[
				'data-mw-smw-browse-subject="{&quot;dbkey&quot;:&quot;Main_Page&quot;,&quot;ns&quot;:0,&quot;iw&quot;:&quot;&quot;,&quot;subobject&quot;:&quot;_QUERY140d50d705e9566904fc4a877c755964&quot;}"',
				'data-mw-smw-browse-options="{&quot;dir&quot;:null,&quot;lang&quot;:&quot;en&quot;,&quot;group&quot;:null,&quot;printable&quot;:null,&quot;offset&quot;:null,&quot;including&quot;:null,&quot;showInverse&quot;:false,&quot;showAll&quot;:true,&quot;showGroup&quot;:false,&quot;showSort&quot;:false,&quot;api&quot;:true,&quot;valuelistlimit.out&quot;:&quot;30&quot;,&quot;valuelistlimit.in&quot;:&quot;20&quot;}"'
			]
		];

		return $provider;
	}

}
