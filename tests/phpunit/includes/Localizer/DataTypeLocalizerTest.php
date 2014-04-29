<?php

namespace SMW\Tests\Localizer;

use SMW\Localizer\DataTypeLocalizer;

/**
 * @uses \SMW\Localizer\DataTypeLocalizer
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class DataTypeLocalizerTest extends \PHPUnit_Framework_TestCase {

	protected $validContentItem = null;

	protected function setUp() {
		parent::setUp();

		$this->validContentItem = array(
			'_txt' => array(
				'id'    => '_txt',
				'label' => 'smw-datatype-label-txt',
				'alias' => 'smw-datatype-alias-txt',
				'default' => array(
					'Text',
					'String'
				)
			)
		);
	}

	public function testCanConstruct() {

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Localizer\DataTypeLocalizer',
			new DataTypeLocalizer( $fileReader, $messageCache )
		);
	}

	public function testCanConstructFromContext() {

		$this->assertInstanceOf(
			'\SMW\Localizer\DataTypeLocalizer',
			DataTypeLocalizer::newFromContext()
		);
	}

	public function testCanConstructFromContentLanguage() {

		$this->assertInstanceOf(
			'\SMW\Localizer\DataTypeLocalizer',
			DataTypeLocalizer::newFromContentLanguage()
		);
	}

	public function testGetDataTypeLabelsOnValidContentItem() {

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$fileReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( $this->validContentItem ) );

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$messageCache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with( $this->stringContains( 'smw-datatype-label-txt' ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new DataTypeLocalizer( $fileReader, $messageCache );

		$this->assertInternalType( 'array', $instance->getDataTypeLabels() );

		$expectedLabelAssignment = array(
			'_txt' => 'Foo'
		);

		$this->assertEquals(
			$expectedLabelAssignment,
			$instance->getDataTypeLabels()
		);
	}

	public function testGetDataTypeAliasesOnValidContentItem() {

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$fileReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( $this->validContentItem ) );

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$messageCache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with( $this->stringContains( 'smw-datatype-alias-txt' ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new DataTypeLocalizer( $fileReader, $messageCache );

		$this->assertInternalType( 'array', $instance->getDataTypeAliases() );

		$expectedAliasAssignment = array(
			'Foo'    => '_txt',
			'Text'   => '_txt',
			'String' => '_txt'
		);

		$this->assertEquals(
			$expectedAliasAssignment,
			$instance->getDataTypeAliases()
		);
	}

	public function testGetDataTypeAliasesOnContentItemWithMissingDefault() {

		unset( $this->validContentItem['_txt']['default'] );

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$fileReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( $this->validContentItem ) );

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$messageCache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with( $this->stringContains( 'smw-datatype-alias-txt' ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new DataTypeLocalizer( $fileReader, $messageCache );

		$this->assertInternalType( 'array', $instance->getDataTypeAliases() );

		$expectedAliasAssignment = array(
			'Foo'    => '_txt'
		);

		$this->assertEquals(
			$expectedAliasAssignment,
			$instance->getDataTypeAliases()
		);
	}


	public function testGetDataTypeLabelsOnInvalidContentItem() {

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$fileReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( array( 'Foo' => 'Bar' ) ) );

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataTypeLocalizer( $fileReader, $messageCache );

		$this->setExpectedException( 'RuntimeException' );

		$instance->getDataTypeLabels();
	}

	public function testGetDataTypeAliasesOnInvalidContentItem() {

		$fileReader = $this->getMockBuilder( '\SMW\JsonFileReader' )
			->disableOriginalConstructor()
			->getMock();

		$fileReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( array( 'Foo' => 'Bar' ) ) );

		$messageCache = $this->getMockBuilder( '\SMW\Cache\MessageCache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataTypeLocalizer( $fileReader, $messageCache );

		$this->setExpectedException( 'RuntimeException' );

		$instance->getDataTypeAliases();
	}

	// Add integration test to verify that processing of labels and aliases
	// when using a "real" MessageCache/JsonFileReader instance does work
	// as anticipated

}
