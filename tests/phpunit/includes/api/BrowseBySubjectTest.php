<?php

namespace SMW\Test;

use SMW\Api\BrowseBySubject;
use SMW\DIWikiPage;
use SMW\SemanticData;

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
