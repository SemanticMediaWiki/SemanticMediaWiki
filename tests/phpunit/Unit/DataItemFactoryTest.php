<?php

namespace SMW\Tests;

use SMW\DataItemFactory;

/**
 * @covers \SMW\DataItemFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemFactoryTest extends \PHPUnit_Framework_TestCase {

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

	public function testCanConstructDIContainer() {

		$containerSemanticData = $this->getMockBuilder( '\SMWContainerSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemFactory();

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->newDIContainer( $containerSemanticData )
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

}
