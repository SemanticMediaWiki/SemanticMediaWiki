<?php

namespace SMW\Tests\Exporter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\DataItemToExpResourceEncoder;
use SMW\Exporter\Element;
use SMW\Exporter\Escaper;
use SMW\InMemoryPoolCache;

/**
 * @covers \SMW\Exporter\DataItemToExpResourceEncoder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class DataItemToExpResourceEncoderTest extends \PHPUnit_Framework_TestCase {

	private $inMemoryPoolCache;

	protected function setUp() {
		$this->inMemoryPoolCache = InMemoryPoolCache::getInstance();
	}

	protected function tearDown() {
		$this->inMemoryPoolCache->clear();
	}

	public function testResetCache() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( 'exporter.dataitem.resource.encoder' );

		$poolCache->save(
			$subject->getHash(),
			true
		);

		$poolCache->save(
			$subject->getHash() . DataItemToExpResourceEncoder::AUX_MARKER,
			true
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemToExpResourceEncoder(
			$store
		);

		$instance->resetCacheFor(
			$subject
		);

		$this->assertFalse(
			$poolCache->contains( $subject->getHash() )
		);
	}

	public function testMapPropertyToResourceElement() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemToExpResourceEncoder(
			$store
		);

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpNsResource',
			$instance->mapPropertyToResourceElement( new DIProperty( 'Foo' ) )
		);
	}

	/**
	 * @dataProvider diWikiPageProvider
	 */
	public function testMapWikiPageToResourceElement( $dataItem, $modifier, $expected ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemToExpResourceEncoder(
			$store
		);

		$resource = $instance->mapWikiPageToResourceElement( $dataItem, $modifier );

		$this->assertSame(
			$expected,
			$resource->getSerialization()
		);
	}

	/**
	 * @dataProvider importDataProvider
	 */
	public function testMapWikiPageToResourceElementForImportMatch( $dataItem, $expected ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will(
				$this->returnValue( array( new \SMWDIBlob( 'foo:bar:fom:fuz' ) ) ) );

		$instance = new DataItemToExpResourceEncoder(
			$store
		);

		$resource = $instance->mapWikiPageToResourceElement(
			$dataItem
		);

		$this->assertTrue(
			$resource->wasMatchedToImportVocab
		);

		$this->assertSame(
			$expected,
			$resource->getSerialization()
		);
	}

	public function diWikiPageProvider() {

		// Constant
		$wiki = \SMWExporter::getInstance()->getNamespaceUri( 'wiki' );
		$property = \SMWExporter::getInstance()->getNamespaceUri( 'property' );

		#0
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, '', '' ),
			'',
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "Foo|{$wiki}|wiki",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#0#' )
			)
		);

		#1
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', '' ),
			'',
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "bar-3AFoo|{$wiki}|wiki",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#0#bar' )
			)
		);

		#2
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', '1234' ),
			'',
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "bar-3AFoo-231234|{$wiki}|wiki",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#0#bar#1234' )
			)
		);

		#3 Extra modififer doesn't not alter the object when a subobject is used
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', '1234' ),
			'abc',
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "bar-3AFoo-231234|{$wiki}|wiki",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#0#bar#1234' )
			)
		);

		#4
		$provider[] = array(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ),
			'',
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "Foo|{$property}|property",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#102#' )
			)
		);

		#5
		$provider[] = array(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ),
			true,
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "Foo-23aux|{$property}|property",
				'dataitem' => array( 'type' => 9, 'item' => 'Foo#102#' )
			)
		);

		#6
		$name = Escaper::encodePage(
			new DIWikiPage( '-Foo', SMW_NS_PROPERTY, '', '' )
		);

		$provider[] = array(
			new DIWikiPage( '-Foo', SMW_NS_PROPERTY, '', '' ),
			true,
			array(
				'type' => Element::TYPE_NSRESOURCE,
				'uri'  => "$name-23aux|{$wiki}|wiki",
				'dataitem' => array( 'type' => 9, 'item' => '-Foo#102#' )
			)
		);

		return $provider;
	}

	public function importDataProvider() {

		// || is not the result we normally would expect but mocking the
		// dataValueFactory at this point is not worth the hassle therefore
		// we live with || output
		$expected =	array(
			'type' => Element::TYPE_NSRESOURCE,
			'uri'  => "||",
			'dataitem' => array( 'type' => 9, 'item' => 'Foo#102#' )
		);

		$provider[] = array(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ),
			$expected
		);

		$expected =	array(
			'type' => Element::TYPE_NSRESOURCE,
			'uri'  => "||",
			'dataitem' => array( 'type' => 9, 'item' => 'Foo#14#' )
		);

		$provider[] = array(
			new DIWikiPage( 'Foo', NS_CATEGORY, '', '' ),
			$expected
		);

		return $provider;
	}

}
