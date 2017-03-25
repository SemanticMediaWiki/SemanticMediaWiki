<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\InfoLinksProvider;
use SMW\Message;
use SMW\Tests\TestEnvironment;
use SMWNumberValue as NumberValue;
use SMWStringValue as StringValue;
use SMWTimeValue as TimeValue;

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
	private $dataValueServiceFactory;
	private $cachedPropertyValuesPrefetcher;
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );

		$this->cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'CachedPropertyValuesPrefetcher', $this->cachedPropertyValuesPrefetcher );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
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

		$numberValue = $this->dataValueFactory->newDataValueByType( NumberValue::TYPE_ID );

		$numberValue->setOption( 'user.language', 'en' );
		$numberValue->setOption( 'content.language', 'en' );

		$numberValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$numberValue->setUserValue( '1000.42' );

		$instance = new InfoLinksProvider( $numberValue );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'newInfoLinksProvider' )
			->will( $this->returnValue( $instance ) );

		$this->assertContains(
			'/:Foo/1000.42|+]]</span>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'/:Foo/1000.42">+</a></span>',
			$instance->getInfolinkText( SMW_OUTPUT_HTML )
		);
	}

	public function testGetInfolinkTextOnStringValue() {

		$this->cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$stringValue = $this->dataValueFactory->newDataValueByType( StringValue::TYPE_ID );

		$stringValue->setOption( 'user.language', 'en' );
		$stringValue->setOption( 'content.language', 'en' );

		$stringValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$stringValue->setUserValue( 'Text with :: content' );

		$instance = new InfoLinksProvider( $stringValue );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'newInfoLinksProvider' )
			->will( $this->returnValue( $instance ) );

		$this->assertContains(
			'/:Foo/Text-20with-20-2D3A-2D3A-20content|+]]</span>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'/:Foo/Text-20with-20-2D3A-2D3A-20content">+</a></span>',
			$instance->getInfolinkText( SMW_OUTPUT_HTML )
		);
	}

	public function testGetInfolinkTextOnTimeValueWithoutLocalizedOutput() {

		$timeValue = $this->dataValueFactory->newDataValueByType( '_dat' );

		$timeValue->setOption( $timeValue::OPT_USER_LANGUAGE, 'fr' );
		$timeValue->setOption( $timeValue::OPT_CONTENT_LANGUAGE, 'en' );

		// Forcibly set an output
		$timeValue->setOutputFormat( 'LOCL' );

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_dat' );

		$timeValue->setProperty(
			$property
		);

		$timeValue->setDataItem(
			$this->dataItemFactory->newDITime( 1, 1970, 12, 12 )
		);

		$instance = new InfoLinksProvider( $timeValue );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'newInfoLinksProvider' )
			->will( $this->returnValue( $instance ) );

		$this->assertContains(
			'/:Foo/12-20December-201970|+]]</span>',
			$instance->getInfolinkText( SMW_OUTPUT_WIKI )
		);

		$this->assertContains(
			'/:Foo/12-20December-201970">+</a></span>',
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

		$stringValue = $this->dataValueFactory->newDataValueByType( StringValue::TYPE_ID );

		$stringValue->setOption( StringValue::OPT_USER_LANGUAGE, 'en' );
		$stringValue->setOption( StringValue::OPT_CONTENT_LANGUAGE, 'en' );

		$stringValue->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$stringValue->setUserValue( 'Bar' );

		$instance = new InfoLinksProvider( $stringValue );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'newInfoLinksProvider' )
			->will( $this->returnValue( $instance ) );

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
