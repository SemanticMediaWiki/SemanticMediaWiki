<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\InfoLinksProvider;
use SMW\Message;
use SMW\Tests\TestEnvironment;
use SMWNumberValue as NumberValue;
use SMWStringValue as StringValue;

/**
 * @covers \SMW\DataValues\InfoLinksProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class InfoLinksProviderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $cachedPropertyValuesPrefetcher;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'CachedPropertyValuesPrefetcher', $this->cachedPropertyValuesPrefetcher );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\DataValues\InfoLinksProvider',
			new InfoLinksProvider( $dataValue )
		);
	}

	public function testGetInfolinkTextOnNumberValue() {

		$this->cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$numberValue = new NumberValue();

		$numberValue->setOption( 'user.language', 'en' );
		$numberValue->setOption( 'content.language', 'en' );

		$numberValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$numberValue->setUserValue( '1000.42' );

		$instance = new InfoLinksProvider( $numberValue );

		$this->assertContains(
			'/Foo/1000.42|+]]</span>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'/Foo/1000.42">+</a></span>',
			$instance->getInfolinkText( SMW_OUTPUT_HTML )
		);
	}

	public function testGetInfolinkTextOnStringValue() {

		$this->cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$stringValue = new StringValue( '_txt' );

		$stringValue->setOption( 'user.language', 'en' );
		$stringValue->setOption( 'content.language', 'en' );

		$stringValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$stringValue->setUserValue( 'Text with :: content' );

		$instance = new InfoLinksProvider( $stringValue );

		$this->assertContains(
			'/Foo/Text-20with-20-2D3A-2D3A-20content|+]]</span>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'/Foo/Text-20with-20-2D3A-2D3A-20content">+</a></span>',
			$instance->getInfolinkText( SMW_OUTPUT_HTML )
		);
	}

	public function testGetInfolinkTextOnStringValueWithServiceLinks() {

		$service = 'testGetInfolinkTextOnStringValueWithServiceLinks';

		$this->cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array(
				$this->dataItemFactory->newDIBlob( $service ) ) ) );

		// Manipulating the Message cache is a hack!!
		$parameters = array(
			"smw_service_" . $service,
			'Bar'
		);

		Message::getCache()->save(
			Message::getHash( $parameters, Message::TEXT, Message::CONTENT_LANGUAGE ),
			'SERVICELINK-A|SERVICELINK-B'
		);

		$stringValue = new StringValue( '_txt' );

		$stringValue->setOption( 'user.language', 'en' );
		$stringValue->setOption( 'content.language', 'en' );

		$stringValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$stringValue->setUserValue( 'Bar' );

		$instance = new InfoLinksProvider( $stringValue );

		$this->assertContains(
			'<div class="smwttcontent">[SERVICELINK-B SERVICELINK-A]</div>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'<div class="smwttcontent"><a href="SERVICELINK-B">SERVICELINK-A</a></div>',
			$instance->getInfolinkText( SMW_OUTPUT_HTML )
		);
	}

}
