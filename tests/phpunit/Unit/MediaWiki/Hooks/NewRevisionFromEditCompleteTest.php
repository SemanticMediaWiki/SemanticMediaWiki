<?php

namespace SMW\Tests\MediaWiki\Hooks;

use ParserOutput;
use Revision;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\Tests\TestEnvironment;
use Title;
use WikiPage;

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

	private $semanticDataValidator;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
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

		$this->testEnvironment->withConfiguration(
			$parameters['settings']
		);

		$instance = new NewRevisionFromEditComplete(
			$parameters['wikiPage'],
			$parameters['revision'],
			0
		);

		$this->assertTrue( $instance->process() );

		$editInfo = $parameters['editInfo'];

		if ( $editInfo && $editInfo->output instanceof ParserOutput ) {

			$parserData = ApplicationFactory::getInstance()->newParserData(
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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		#0 No parserOutput object
		$editInfo = (object)array();
		$editInfo->output = null;

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( array( $title ) )
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
				'revision' => $this->newRevisionStub(),
				'settings' => array()
			),
			array()
		);

		#1 With annotation
		$editInfo = (object)array();
		$editInfo->output = new ParserOutput();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( array( $title ) )
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
				'revision' => $this->newRevisionStub(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE ),
					'smwgDVFeatures' => ''
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

	private function newRevisionStub() {

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->setMethods( array( 'getRawText', 'getContent' ) )
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getRawText' )
			->will( $this->returnValue( 'Foo' ) );

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValueMap( array(
				array( \Revision::RAW, null, 'Foo' ),
				array( \Revision::FOR_PUBLIC, null, $this->newContentStub() ),
			) ) );

		return $revision;
	}

	private function newContentStub() {

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
