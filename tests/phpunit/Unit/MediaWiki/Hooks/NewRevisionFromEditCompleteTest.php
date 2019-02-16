<?php

namespace SMW\Tests\MediaWiki\Hooks;

use ParserOutput;
use Revision;
use SMW\DIProperty;
use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\Tests\TestEnvironment;
use Title;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\NewRevisionFromEditComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NewRevisionFromEditCompleteTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $eventDispatcher;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			NewRevisionFromEditComplete::class,
			new NewRevisionFromEditComplete( $title, $editInfoProvider, $pageInfoProvider )
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testProcess( $settings, $title, $editInfoProvider, $pageInfoProvider, $expected ) {

		$this->eventDispatcher->expects( $expected ? $this->atLeastOnce() : $this->never() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateEntityCache' ) );

		$this->testEnvironment->withConfiguration( $settings );

		$instance = new NewRevisionFromEditComplete(
			$title,
			$editInfoProvider,
			$pageInfoProvider
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process()
		);

		if ( $expected ) {
			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$editInfoProvider->fetchSemanticData()
			);
		}
	}

	public function wikiPageDataProvider() {

		#0 No parserOutput object

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->setMethods( [ 'getOutput' ] )
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			[],
			$title,
			$editInfoProvider,
			$pageInfoProvider,
			false
		];

		#1 With annotation
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->setMethods( [ 'getOutput' ] )
			->getMock();

		$editInfoProvider->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( new ParserOutput() ) );

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider->expects( $this->atLeastOnce() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( 1272508903 ) );

		$provider[] = [
			[
				'smwgPageSpecialProperties' => [ DIProperty::TYPE_MODIFICATION_DATE ],
				'smwgDVFeatures' => ''
			],
			$title,
			$editInfoProvider,
			$pageInfoProvider,
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => [ '2010-04-29T02:41:43' ],
			]
		];

		#2 on schema page
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo_schema' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->setMethods( [ 'getOutput' ] )
			->getMock();

		$editInfoProvider->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( new ParserOutput() ) );

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$data = json_encode(
			[ 'description' => 'Foobar', 'type' => 'FOO_ROLE' ]
		);

		$pageInfoProvider->expects( $this->atLeastOnce() )
			->method( 'getNativeData' )
			->will( $this->returnValue( $data ) );

		$provider[] =[
			[
				'smwgPageSpecialProperties' => [],
				'smwgDVFeatures' => '',
				'smwgSchemaTypes' => [ 'FOO_ROLE' => [] ]
			],
			$title,
			$editInfoProvider,
			$pageInfoProvider,
			[
				'propertyCount'  => 3,
				'propertyKeys'   => [ '_SCHEMA_DESC', '_SCHEMA_TYPE', '_SCHEMA_DEF' ],
				'propertyValues' => [ 'Foobar', 'FOO_ROLE', '{"description":"Foobar","type":"FOO_ROLE"}' ],
			]
		];

		return $provider;
	}

}
