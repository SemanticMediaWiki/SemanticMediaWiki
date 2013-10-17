<?php

namespace SMW\Test;

use SMW\SerializerFactory;
use SMW\DataValueFactory;
use SMW\ApiBrowse;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\Subobject;

/**
 * @covers \SMW\ApiBrowse
 *
 * @since 1.9
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class ApiBrowseSerializationRoundtripTest extends ApiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiBrowse';
	}

	/**
	 * @since 1.9
	 */
	private function newSemanticData( $text ) {
		return $data = new SemanticData( DIWikiPage::newFromTitle( $this->newTitle( NS_MAIN, $text ) ) );
	}

	/**
	 * @dataProvider semanticDataProvider
	 * @see Bugzilla 55826
	 *
	 * @since 1.9
	 */
	public function testExecuteOnRawModeAndMockStore( $setup ) {

		$api = new ApiBrowse( $this->getApiMain( array( 'subject' => $setup['subject'] ) ), 'browse' );
		$api->setStore( $setup['store'] );
		$api->getMain()->getResult()->setRawMode();

		$api->execute();

		$this->assertStructuralIntegrity( $setup, $api->getResultData() );

	}

	/**
	 * @dataProvider semanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnMockStore( $setup ) {

		$api = new ApiBrowse( $this->getApiMain( array( 'subject' => $setup['subject'] ) ), 'browse' );
		$api->setStore( $setup['store'] );
		$api->execute();

		$result = $api->getResultData();

		$this->assertStructuralIntegrity( $setup, $result );

		// We gimmick a bit here otherwise matching the array will be cumbersome,
		// we'll use the result from the Api and recreate (using the Serializer)
		// the SemanticData container and then compare the hash from the original
		// container with that of the newly created container from the Api
		$this->assertEquals(
			$setup['data']->getHash(),
			SerializerFactory::deserialize( $result['query'] )->getHash(),
			'Asserts that getHash() compares to both SemanticData containers'
		);

	}

	/**
	 * The Serializer enforces a specific output format therefore expected
	 * elements are verified
	 *
	 * @since 1.9
	 */
	public function assertStructuralIntegrity( $type, $result ) {

		if ( isset( $type['hasError'] ) && $type['hasError'] ) {
			$this->assertInternalType( 'array', $result['error'] );
		}

		if ( isset( $type['hasResult'] ) && $type['hasResult'] ) {
			$this->assertInternalType( 'array', $result['query'] );
		}

		if ( isset( $type['hasSubject'] ) && $type['hasSubject'] ) {
			$this->assertInternalType( 'string', $result['query']['subject'] );
		}

		if ( isset( $type['hasData'] ) && $type['hasData'] ) {
			$this->assertInternalType( 'array', $result['query']['data'] );
		}

		if ( isset( $type['hasSobj'] ) && $type['hasSobj'] ) {
			$this->assertInternalType( 'array', $result['query']['sobj'] );
		}

	}

	/**
	 * @return array
	 */
	public function semanticDataProvider() {

		$provider = array();

		// #0 Empty container
		$data = $this->newSemanticData( 'Foo-0' );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $data
		) );

		$provider[] = array(
			array(
				'subject'   => 'Foo-0',
				'store'     => $mockStore,
				'data'      => $data,
				'hasResult' => true,
				'hasData'   => false,
				'hasSobj'   => false
			)
		);

		// #1 Single entry
		$data = $this->newSemanticData( 'Foo-1' );
		$data->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $data
		) );

		$provider[] = array(
			array(
				'subject'   => 'Foo-1',
				'store'     => $mockStore,
				'data'      => $data,
				'hasResult' => true,
				'hasData'   => true,
				'hasSobj'   => false
			)
		);

		// #2 Single + single subobject entry
		$title = $this->newTitle( NS_MAIN, 'Foo-2' );
		$data  = $this->newSemanticData( 'Foo-2' );
		$data->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo-sub' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		// Adding a reference but not the container itself
		$data->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => function ( $subject ) use( $data, $subobject ) {
				return $subject->getSubobjectName() === 'Foo-sub' ? $subobject->getSemanticData() : $data;
			}
		) );

		$provider[] = array(
			array(
				'subject'   => 'Foo-2',
				'store'     => $mockStore,
				'data'      => $data,
				'hasResult' => true,
				'hasData'   => true,
				'hasSobj'   => true
			)
		);

		// #3 Single + single subobject where the subobject already exists
		$title = $this->newTitle( NS_MAIN, 'Foo-3' );
		$data  = $this->newSemanticData( 'Foo-3' );
		$data->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo-sub' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		$data->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $data
		) );

		$provider[] = array(
			array(
				'subject'   => 'Foo-3',
				'store'     => $mockStore,
				'data'      => $data,
				'hasResult' => true,
				'hasData'   => true,
				'hasSobj'   => true
			)
		);

		return $provider;
	}

}
