<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\PropertyTableInfoFetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class PropertyTableInfoFetcherTest extends \PHPUnit_Framework_TestCase {

	private $propertyTypeFinder;

	protected function setUp() {
		parent::setUp();

		$this->propertyTypeFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTypeFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableInfoFetcher',
			new PropertyTableInfoFetcher( $this->propertyTypeFinder )
		);
	}

	public function testGetPropertyTableDefinitions() {

		$instance = new PropertyTableInfoFetcher(
			$this->propertyTypeFinder
		);

		$this->assertInternalType(
			'array',
			$instance->getPropertyTableDefinitions()
		);

		$instance->clearCache();
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testFindTableIdForProperty( $property, $expected ) {

		$property = DIProperty::newFromUserLabel( $property );

		$instance = new PropertyTableInfoFetcher(
			$this->propertyTypeFinder
		);

		$instance->setCustomSpecialPropertyList(
			[ '_MDAT', '_MEDIA', '_MIME' ]
		);

		$this->assertEquals(
			$expected,
			$instance->findTableIdForProperty( $property )
		);
	}

	/**
	 * @dataProvider defaultDiTypeProvider
	 */
	public function testFindTableIdForDataItemTypeId( $diType, $expected ) {

		$instance = new PropertyTableInfoFetcher(
			$this->propertyTypeFinder
		);

		$instance->setCustomSpecialPropertyList(
			[ '_MDAT', '_MEDIA', '_MIME' ]
		);

		$this->assertEquals(
			$expected,
			$instance->findTableIdForDataItemTypeId( $diType )
		);
	}

	/**
	 * @dataProvider dataTypeProvider
	 */
	public function testFindTableIdForDataTypeTypeId( $dataType, $expected ) {

		$instance = new PropertyTableInfoFetcher(
			$this->propertyTypeFinder
		);

		$instance->setCustomSpecialPropertyList(
			[ '_MDAT', '_MEDIA', '_MIME' ]
		);

		$this->assertEquals(
			$expected,
			$instance->findTableIdForDataTypeTypeId( $dataType )
		);
	}

	public function propertyProvider() {

		$provider = [];

		// Pre-defined property
		$provider = [
			[ '_MDAT',  'smw_fpt_mdat' ],
			[ '_CDAT',  'smw_di_time' ],
			[ '_NEWP',  'smw_di_bool' ],
			[ '_LEDT',  'smw_di_wikipage' ],
			[ '_MIME',  'smw_fpt_mime' ],
			[ '_MEDIA', 'smw_fpt_media' ],
			[ '_TYPE',  'smw_fpt_type' ],
			[ '_UNIT',  'smw_fpt_unit' ],
			[ '_CONV',  'smw_fpt_conv' ],
			[ '_PVAL',  'smw_fpt_pval' ],
			[ '_LIST',  'smw_fpt_list' ],
			[ '_SERV',  'smw_fpt_serv' ],
			[ '_ASK',   'smw_fpt_ask' ],
			[ '_ASKDE', 'smw_fpt_askde' ],
			[ '_ASKSI', 'smw_fpt_asksi' ],
			[ '_ASKFO', 'smw_fpt_askfo' ],
			[ '_ASKST', 'smw_fpt_askst' ],
			[ '_ASKDU', 'smw_fpt_askdu' ],
			[ '_SUBP',  'smw_fpt_subp' ],
			[ '_SUBC',  'smw_fpt_subc' ],
			[ '_INST',  'smw_fpt_inst' ],
			[ '_REDI',  'smw_fpt_redi' ],
			[ '_SOBJ',  'smw_fpt_sobj' ],
			[ '_IMPO',  'smw_fpt_impo' ],
			[ '_URI',   'smw_fpt_uri' ],
			[ '_CONC',  'smw_fpt_conc' ],
		];

		$provider[] = [
			'Modification date',
			'smw_fpt_mdat'
		];

		// User-defined property
		$provider[] = [
			'Foo',
			'smw_di_wikipage'
		];

		return $provider;
	}

	public function defaultDiTypeProvider() {

		$provider = [];

		// Known
		$provider = [
			[ DataItem::TYPE_NUMBER, 'smw_di_number' ],
			[ DataItem::TYPE_BLOB,'smw_di_blob' ],
			[ DataItem::TYPE_BOOLEAN, 'smw_di_bool' ],
			[ DataItem::TYPE_URI, 'smw_di_uri' ],
			[ DataItem::TYPE_TIME, 'smw_di_time'],
			[ DataItem::TYPE_GEO, 'smw_di_coords' ],
			[ DataItem::TYPE_WIKIPAGE, 'smw_di_wikipage' ],
			[ DataItem::TYPE_CONCEPT, '' ],
		];

		// Unknown
		$provider[] = [
			'Foo',
			''
		];

		return $provider;
	}

	public function dataTypeProvider() {

		$provider = [];

		// Known
		$provider = [
			[ '_num', 'smw_di_number' ],
			[ '_txt','smw_di_blob' ],
			[ '_boo', 'smw_di_bool' ],
			[ '_uri', 'smw_di_uri' ],
			[ '_dat', 'smw_di_time'],
			[ '_geo', 'smw_di_coords' ],
			[ '_wpg', 'smw_di_wikipage' ],
		];

		// Unknown
		$provider[] = [
			'Foo',
			''
		];

		return $provider;
	}

}
