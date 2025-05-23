<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
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
class DataItemFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\DataItemFactory',
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
			'\SMW\DIProperty',
			$instance->newDIProperty( 'Foo bar' )
		);
	}

	public function testCanConstructDIWikiPage() {
		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->newDIWikiPage( 'Foo' )
		);
	}

	public function testCanConstructDIWikiPageFromTitle() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->newDIWikiPage( $title )
		);
	}

	public function testCanConstructDIContainer() {
		$containerSemanticData = $this->getMockBuilder( '\SMW\DataModel\ContainerSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->newDIContainer( $containerSemanticData )
		);
	}

	public function testCanConstructContainerSemanticData() {
		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMW\DataModel\ContainerSemanticData',
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
			'\SMW\DIConcept',
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
