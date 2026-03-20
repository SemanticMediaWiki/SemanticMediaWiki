<?php

namespace SMW\Tests;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DIConcept;
use SMW\DIProperty;
use SMWDITime as DITime;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\DataItemFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DataItemFactory::class,
			new DataItemFactory()
		);
	}

	public function testCanConstructDIError() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIError',
			$instance->newDIError( 'Foo' )
		);
	}

	public function testCanConstructDIProperty() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			DIProperty::class,
			$instance->newDIProperty( 'Foo bar' )
		);
	}

	public function testCanConstructWikiPage() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->newDIWikiPage( 'Foo' )
		);
	}

	public function testCanConstructWikiPageFromTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->newDIWikiPage( $title )
		);
	}

	public function testCanConstructDIContainer() {
		$containerSemanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->newDIContainer( $containerSemanticData )
		);
	}

	public function testCanConstructContainerSemanticData() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			ContainerSemanticData::class,
			$instance->newContainerSemanticData( $subject )
		);
	}

	public function testCanConstructDINumber() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDINumber',
			$instance->newDINumber( 42 )
		);
	}

	public function testCanConstructDIBlob() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIBlob',
			$instance->newDIBlob( 'Foo' )
		);
	}

	public function testCanConstructDIBoolean() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIBoolean',
			$instance->newDIBoolean( true )
		);
	}

	public function testCanConstructDIConcept() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			DIConcept::class,
			$instance->newDIConcept( 'Foo' )
		);
	}

	public function testCanConstructDIUri() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			DIUri::class,
			$instance->newDIUri( 'http', 'example.org' )
		);
	}

	public function testCanConstructDITime() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			DITime::class,
			$instance->newDITime( 1, '1900' )
		);
	}

}
