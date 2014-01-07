<?php

namespace SMW\Test;

use SMW\Api\BrowseBySubject;
use SMW\SerializerFactory;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\Subobject;

/**
 * @covers \SMW\Api\BrowseBySubject
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BrowseBySubjectSerializationRoundtripTest extends ApiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Api\BrowseBySubject';
	}

	/**
	 * @since 1.9
	 */
	private function newSemanticData( $text ) {
		return new SemanticData(
			DIWikiPage::newFromTitle( $this->newTitle( NS_MAIN, $text ) )
		);
	}

	/**
	 * @dataProvider semanticDataProvider
	 * @see Bugzilla 55826
	 *
	 * @since 1.9
	 */
	public function testExecuteOnRawModeAndMockStore( $setup ) {

		$apiMain  = $this->getApiMain( array( 'subject' => $setup['subject'] ) );
		$instance = new BrowseBySubject( $apiMain, 'browsebysubject' );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $setup['store'] );

		$instance->getMain()->getResult()->setRawMode();
		$instance->execute();

		$this->assertStructuralIntegrity( $setup, $instance->getResultData() );

	}

	/**
	 * @dataProvider semanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnMockStore( $setup ) {

		$apiMain  = $this->getApiMain( array( 'subject' => $setup['subject'] ) );
		$instance = new BrowseBySubject( $apiMain, 'browsebysubject' );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $setup['store'] );

		$instance->execute();

		$result = $instance->getResultData();

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
	 * @since 1.9
	 */
	public function assertStructuralIntegrity( $setup, $result ) {

		$this->assertInternalArrayStructure( $setup, $result, 'hasError',   'array',  function( $r ) { return $r['error']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'hasResult',  'array',  function( $r ) { return $r['query']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'hasSubject', 'string', function( $r ) { return $r['query']['subject']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'hasData',    'array',  function( $r ) { return $r['query']['data']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'hasSobj',    'array',  function( $r ) { return $r['query']['sobj']; } );

	}

	protected function assertInternalArrayStructure( $setup, $result, $field, $internalType, $definition ) {
		if ( isset( $setup[$field] ) && $setup[$field] ) {
			$this->assertInternalType( $internalType, is_callable( $definition ) ? $definition( $result ) : $definition );
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
		$data->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

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
		$data->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo-sub' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has subobjects', 'Bam' ) );

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
		$data->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo-sub' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has subobjects', 'Bam' ) );

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
