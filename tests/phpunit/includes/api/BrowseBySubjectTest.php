<?php

namespace SMW\Test;

use SMW\Api\BrowseBySubject;
use SMW\SemanticData;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Api\BrowseBySubject
 * @covers \SMW\Api\Base
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
class BrowseBySubjectTest extends ApiTestCase {

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
		return $data = new SemanticData( DIWikiPage::newFromTitle( $this->newTitle( NS_MAIN, $text ) ) );
	}

	/**
	 * @since 1.9
	 */
	public function testInvokeContext() {

		$instance = new BrowseBySubject( $this->getApiMain( array( 'subject' => 'Foo' ) ), 'browsebysubject' );
		$context  = $instance->withContext();

		$this->assertInstanceOf( '\SMW\ContextResource', $context );

		$instance->invokeContext( $instance->withContext() );
		$this->assertTrue( $context === $instance->withContext() );

	}

	/**
	 * @dataProvider subjectDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnSQLStore( $setup ) {

		$this->runOnlyOnSQLStore();

		$result = $this->doApiRequest( array(
			'action'  => 'browsebysubject',
			'subject' => $setup['subject']
		) );

		$this->assertStructuralIntegrity( $setup, $result );

	}

	/**
	 * @dataProvider invalidTitleDataProvider
	 *
	 * @since 1.9
	 */
	public function testInvalidTitleExecuteOnSQLStore( $setup ) {

		$this->runOnlyOnSQLStore();

		$this->setExpectedException( 'Exception' );

		$result = $this->doApiRequest( array(
			'action'  => 'browsebysubject',
			'subject' => $setup['subject']
		) );

		$this->assertStructuralIntegrity( $setup, $result );

	}

	/**
	 * @dataProvider redirectDataProvider
	 *
	 * @since 1.9
	 */
	public function testRedirectTitleOnMockStore( $setup ) {

		$apiMain  = $this->getApiMain( array('subject' => 'Foo' ) );
		$instance = new BrowseBySubject( $apiMain, 'browsebysubject' );

		$container = $instance->withContext()->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $setup['store'] );
		$container->registerObject( 'WikiPage', $setup['wikiPage'] );

		$reflector = $this->newReflector();
		$assertValidTitle = $reflector->getMethod( 'assertValidTitle' );
		$assertValidTitle->setAccessible( true );

		try {

			$assertValidTitle->invoke( $instance, $setup['title'] );
			$instance->getMain()->getResult()->setRawMode();
			$instance->execute();

		} catch ( \UsageException $e ) {
			$this->assertTrue( true );
		};

		$this->assertStructuralIntegrity( $setup, $instance->getResultData() );

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
	public function subjectDataProvider() {

		$provider = array();

		// #0 Valid
		$provider[] = array(
			array(
				'subject'    => 'Main_Page',
				'hasSubject' => true,
				'hasResult'  => true
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function redirectDataProvider() {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $this->newSemanticData( 'Foo-redirect' )
		) );

		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'isRedirect' => true
		) );

		$mockWikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getRedirectTarget' => $this->newTitle()
		) );

		$provider = array();

		// #0 Valid
		$provider[] = array(
			array(
				'store'      => $mockStore,
				'wikiPage'   => $mockWikiPage,
				'title'      => $mockTitle,
				'hasSubject' => true,
				'hasResult'  => true
			)
		);

		// #1 Invalid, throws UsageException
		$mockWikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getRedirectTarget'     => null,
			'isValidRedirectTarget' => false
		) );

		$provider[] = array(
			array(
				'store'     => $mockStore,
				'wikiPage'  => $mockWikiPage,
				'title'     => $mockTitle,
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function invalidTitleDataProvider() {

		$provider = array();

		$provider[] = array(
			array(
				'subject'   => '{}',
				'hasError'  => true,
				'hasResult' => false
			)
		);

		return $provider;
	}

}
