<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Validators\SemanticDataValidator;

use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\DIProperty;
use SMW\ApplicationFactory;
use SMW\Settings;

use ParserOutput;
use WikiPage;
use Revision;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\NewRevisionFromEditComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NewRevisionFromEditCompleteTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->semanticDataValidator = new SemanticDataValidator();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\NewRevisionFromEditComplete',
			 new NewRevisionFromEditComplete( $wikiPage, $revision, 0, $user )
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testProcess( $parameters, $expected ) {

		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $parameters['settings'] )
		);

		$instance = new NewRevisionFromEditComplete(
			$parameters['wikiPage'],
			$parameters['revision'],
			0
		);

		$this->assertTrue( $instance->process() );

		$editInfo = $parameters['editInfo'];

		if ( $editInfo && $editInfo->output instanceof ParserOutput ) {

			$parserData = $this->applicationFactory->newParserData(
				$parameters['wikiPage']->getTitle(),
				$editInfo->output
			);

			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$parserData->getSemanticData()
			);
		}
	}

	public function wikiPageDataProvider() {

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getRawText' )
			->will( $this->returnValue( 'Foo' ) );

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $this->newContent() ) );

		#0 No parserOutput object
		$editInfo = (object)array();
		$editInfo->output = null;

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$wikiPage->expects( $this->any() )
			->method( 'prepareTextForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$provider[] = array(
			array(
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array()
			),
			array()
		);

		#1 With annotation
		$editInfo = (object)array();
		$editInfo->output = new ParserOutput();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$wikiPage->expects( $this->any() )
			->method( 'prepareTextForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( 1272508903 ) );

		$provider[] = array(
			array(
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		return $provider;
	}

	private function newContent() {

		if ( !class_exists( 'ContentHandler' ) ) {
			return null;
		}

		$contentHandler = $this->getMockBuilder( '\ContentHandler' )
			->disableOriginalConstructor()
			->getMock();

		$contentHandler->expects( $this->atLeastOnce() )
			->method( 'getDefaultFormat' )
			->will( $this->returnValue( 'Foo' ) );

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->atLeastOnce() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $contentHandler ) );

		return $content;
	}

}
