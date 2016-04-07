<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\EditInfoProvider;

/**
 * @covers \SMW\MediaWiki\EditInfoProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class EditInfoProviderTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\MediaWiki\EditInfoProvider',
			 new EditInfoProvider( $wikiPage, $revision, $user )
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testFetchContentInfo( $parameters, $expected ) {

		$instance = new EditInfoProvider(
			$parameters['wikiPage'],
			$parameters['revision']
		);

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testFetchContentInfoWithDisabledContentHandler( $parameters, $expected ) {

		$instance = $this->getMock( '\SMW\MediaWiki\EditInfoProvider',
			array( 'hasContentForEditMethod' ),
			array(
				$parameters['wikiPage'],
				$parameters['revision'],
				null
			)
		);

		$instance->expects( $this->any() )
			->method( 'hasContentForEditMethod' )
			->will( $this->returnValue( false ) );

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	public function wikiPageDataProvider() {

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
				'revision' => $this->newRevisionStub()
			),
			null
		);

		#1
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( false ) );

		$wikiPage->expects( $this->any() )
			->method( 'prepareTextForEdit' )
			->will( $this->returnValue( false ) );

		$provider[] = array(
			array(
				'editInfo' => false,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			),
			null
		);

		#2
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

		$provider[] = array(
			array(
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			),
			$editInfo->output
		);

		#3
		$editInfo = (object)array();

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
				'revision' => $this->newRevisionStub()
			),
			null
		);

		return $provider;
	}

	private function newRevisionStub() {

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
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
