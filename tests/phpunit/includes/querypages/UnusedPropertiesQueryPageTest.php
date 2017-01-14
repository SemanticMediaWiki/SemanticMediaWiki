<?php

namespace SMW\Test;

use SMW\DataItemFactory;
use SMW\Settings;
use SMW\UnusedPropertiesQueryPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\UnusedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UnusedPropertiesQueryPageTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->any() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->setConstructorArgs( array( $this->store, $blobStore ) )
			->setMethods( null )
			->getMock();

		$this->testEnvironment->registerObject( 'CachedPropertyValuesPrefetcher', $cachedPropertyValuesPrefetcher );

		$this->settings = Settings::newFromArray( array() );

		$this->dataItemFactory = new DataItemFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\UnusedPropertiesQueryPage',
			new UnusedPropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {

		$error = $this->dataItemFactory->newDIError( 'Foo');

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$error
		);

		$this->assertInternalType(
			'string',
			$result
		);

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testInvalidResultThrowsException() {

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->setExpectedException( '\SMW\Exception\PropertyNotFoundExeption' );
		$instance->formatResult( $this->skin, null );
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$property
		);

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testFormatPropertyItemOnPredefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_MDAT' );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$property
		);

		$this->assertContains(
			'Help:Special_properties',
			$result
		);
	}

}
