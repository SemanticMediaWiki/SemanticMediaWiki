<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\Tests\Util\MwApiFactory;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Mock\MockTitle;

use SMW\Api\BrowseBySubject;
use SMW\SemanticData;
use SMW\DIWikiPage;

use ReflectionClass;
use Title;

/**
 * @covers \SMW\Api\BrowseBySubject
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-api
 * @group mediawiki-api
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BrowseBySubjectTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->semanticDataFactory = new SemanticDataFactory();
	}

	public function testCanConstruct() {

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( array('subject' => 'Foo' ) ),
			'browsebysubject'
		);

		$this->assertInstanceOf( 'SMW\Api\BrowseBySubject', $instance );
	}

	public function testExecuteOnValidSubject() {

		$expectedResultToContainArrayKeys = array( 'error'  => false, 'result' => true );

		$result = $this->apiFactory->doApiRequest( array(
			'action'  => 'browsebysubject',
			'subject' => 'Main_Page'
		) );

		$this->assertToContainArrayKeys(
			$expectedResultToContainArrayKeys,
			$result
		);
	}

	public function testExecuteOnInvalidSubjectThrowsException() {

		$this->setExpectedException( 'Exception' );

		$result = $this->apiFactory->doApiRequest( array(
			'action'  => 'browsebysubject',
			'subject' => '{}'
		) );
	}

	public function testValidTitleRedirect() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$title = MockTitle::getMock( $this );

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( Title::newFromText( 'Ooooooo' ) ) );

		$parameters = array(
			'store'      => $store,
			'wikiPage'   => $wikiPage,
			'title'      => $title
		);

		$expectedResultToContainArrayKeys = array( 'subject'  => true, 'result' => true );

		$result = $this->reflectOnInstanceToMockRedirect( $parameters );

		$this->assertToContainArrayKeys(
			$expectedResultToContainArrayKeys,
			$result
		);
	}

	public function testInValidTitleRedirectThrowsException() {

		$title = MockTitle::getMock( $this );

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( null ) );

		$parameters = array(
			'store'      => null,
			'wikiPage'   => $wikiPage,
			'title'      => $title
		);

		$this->setExpectedException( 'UsageException' );

		$this->reflectOnInstanceToMockRedirect( $parameters );
	}

	public function reflectOnInstanceToMockRedirect( $setup ) {

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( array('subject' => 'Foo' ) ),
			'browsebysubject'
		);

		$container = $instance->withContext()->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $setup['store'] );
		$container->registerObject( 'WikiPage', $setup['wikiPage'] );

		$reflector = new ReflectionClass( 'SMW\Api\BrowseBySubject' );
		$constructValidTitle = $reflector->getMethod( 'constructValidTitle' );
		$constructValidTitle->setAccessible( true );

		$constructValidTitle->invoke( $instance, $setup['title'] );
		$instance->getMain()->getResult()->setRawMode();
		$instance->execute();

		return $instance->getResultData();
	}

	public function assertToContainArrayKeys( $setup, $result ) {
		$this->assertInternalArrayStructure( $setup, $result, 'error',   'array',  function( $r ) { return $r['error']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'result',  'array',  function( $r ) { return $r['query']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'subject', 'string', function( $r ) { return $r['query']['subject']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'data',    'array',  function( $r ) { return $r['query']['data']; } );
		$this->assertInternalArrayStructure( $setup, $result, 'sobj',    'array',  function( $r ) { return $r['query']['sobj']; } );
	}

	protected function assertInternalArrayStructure( $setup, $result, $field, $internalType, $definition ) {
		if ( isset( $setup[$field] ) && $setup[$field] ) {
			$this->assertInternalType( $internalType, is_callable( $definition ) ? $definition( $result ) : $definition );
		}
	}

}
