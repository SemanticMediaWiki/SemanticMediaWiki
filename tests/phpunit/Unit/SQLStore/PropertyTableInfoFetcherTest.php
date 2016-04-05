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

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableInfoFetcher',
			new PropertyTableInfoFetcher()
		);
	}

	public function testGetPropertyTableDefinitions() {

		$instance = new PropertyTableInfoFetcher();

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

		$instance = new PropertyTableInfoFetcher();

		$instance->setCustomSpecialPropertyList(
			array( '_MDAT', '_MEDIA', '_MIME' )
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

		$instance = new PropertyTableInfoFetcher();

		$instance->setCustomSpecialPropertyList(
			array( '_MDAT', '_MEDIA', '_MIME' )
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

		$instance = new PropertyTableInfoFetcher();

		$instance->setCustomSpecialPropertyList(
			array( '_MDAT', '_MEDIA', '_MIME' )
		);

		$this->assertEquals(
			$expected,
			$instance->findTableIdForDataTypeTypeId( $dataType )
		);
	}

	public function propertyProvider() {

		$provider = array();

		// Pre-defined property
		$provider = array(
			array( '_MDAT',  'smw_fpt_mdat' ),
			array( '_CDAT',  'smw_di_time' ),
			array( '_NEWP',  'smw_di_bool' ),
			array( '_LEDT',  'smw_di_wikipage' ),
			array( '_MIME',  'smw_fpt_mime' ),
			array( '_MEDIA', 'smw_fpt_media' ),
			array( '_TYPE',  'smw_fpt_type' ),
			array( '_UNIT',  'smw_fpt_unit' ),
			array( '_CONV',  'smw_fpt_conv' ),
			array( '_PVAL',  'smw_fpt_pval' ),
			array( '_LIST',  'smw_fpt_list' ),
			array( '_SERV',  'smw_fpt_serv' ),
			array( '_ASK',   'smw_fpt_ask' ),
			array( '_ASKDE', 'smw_fpt_askde' ),
			array( '_ASKSI', 'smw_fpt_asksi' ),
			array( '_ASKFO', 'smw_fpt_askfo' ),
			array( '_ASKST', 'smw_fpt_askst' ),
			array( '_ASKDU', 'smw_fpt_askdu' ),
			array( '_SUBP',  'smw_fpt_subp' ),
			array( '_SUBC',  'smw_fpt_subc' ),
			array( '_INST',  'smw_fpt_inst' ),
			array( '_REDI',  'smw_fpt_redi' ),
			array( '_SOBJ',  'smw_fpt_sobj' ),
			array( '_IMPO',  'smw_fpt_impo' ),
			array( '_URI',   'smw_fpt_uri' ),
			array( '_CONC',  'smw_fpt_conc' ),
		);

		$provider[] = array(
			'Modification date',
			'smw_fpt_mdat'
		);

		// User-defined property
		$provider[] = array(
			'Foo',
			'smw_di_wikipage'
		);

		return $provider;
	}

	public function defaultDiTypeProvider() {

		$provider = array();

		// Known
		$provider = array(
			array( DataItem::TYPE_NUMBER, 'smw_di_number' ),
			array( DataItem::TYPE_BLOB,'smw_di_blob' ),
			array( DataItem::TYPE_BOOLEAN, 'smw_di_bool' ),
			array( DataItem::TYPE_URI, 'smw_di_uri' ),
			array( DataItem::TYPE_TIME, 'smw_di_time'),
			array( DataItem::TYPE_GEO, 'smw_di_coords' ),
			array( DataItem::TYPE_WIKIPAGE, 'smw_di_wikipage' ),
			array( DataItem::TYPE_CONCEPT, '' ),
		);

		// Unknown
		$provider[] = array(
			'Foo',
			''
		);

		return $provider;
	}

	public function dataTypeProvider() {

		$provider = array();

		// Known
		$provider = array(
			array( '_num', 'smw_di_number' ),
			array( '_txt','smw_di_blob' ),
			array( '_boo', 'smw_di_bool' ),
			array( '_uri', 'smw_di_uri' ),
			array( '_dat', 'smw_di_time'),
			array( '_geo', 'smw_di_coords' ),
			array( '_wpg', 'smw_di_wikipage' ),
		);

		// Unknown
		$provider[] = array(
			'Foo',
			''
		);

		return $provider;
	}

}
