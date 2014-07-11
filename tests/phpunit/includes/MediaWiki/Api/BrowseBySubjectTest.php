<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\Tests\Util\MwApiFactory;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Mock\MockTitle;

use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\Application;

use Title;

/**
 * @covers \SMW\MediaWiki\Api\BrowseBySubject
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
	private $application;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->application = Application::getInstance();
	}

	protected function tearDown() {
		Application::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( array('subject' => 'Foo' ) ),
			'browsebysubject'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\BrowseBySubject',
			$instance
		);
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

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->setMethods( array( 'getTitle', 'findRedirect' ) )
			->getMock();

		$titleCreator->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( 'Ooooooo' ) ) );

		$titleCreator->expects( $this->atLeastOnce() )
			->method( 'findRedirect' )
			->will( $this->returnValue( $titleCreator ) );

		$this->application->registerObject( 'Store', $store );
		$this->application->registerObject( 'TitleCreator', $titleCreator );

		$expectedResultToContainArrayKeys = array( 'subject'  => true, 'result' => true );

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( array('subject' => 'Foo' ) ),
			'browsebysubject'
		);

		$instance->getMain()->getResult()->setRawMode();
		$instance->execute();

		$result = $instance->getResultData();

		$this->assertToContainArrayKeys(
			$expectedResultToContainArrayKeys,
			$result
		);
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
