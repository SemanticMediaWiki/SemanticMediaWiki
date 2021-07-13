<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\EditInfo;

/**
 * @covers \SMW\MediaWiki\EditInfo
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class EditInfoTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\EditInfo',
			 new EditInfo( $wikiPage, $revision, $user )
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testFetchContentInfo( $parameters, $expected ) {
		$this->markTestSkipped( "FIXME -- Error: Call to a member function getContentHandler() on null" );
		$instance = new EditInfo(
			$parameters['wikiPage'],
			$parameters['revision']
		);

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	public function testFetchSemanticData() {
		$this->markTestSkipped( "FIXME -- Error: Call to a member function getContentHandler() on null" );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$editInfo = (object)[];
		$editInfo->output = new ParserOutput();
		$editInfo->output->setExtensionData( \SMW\ParserData::DATA_ID, $semanticData );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$instance = new EditInfo(
			$wikiPage,
			$this->newRevisionStub()
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance->fetchSemanticData()
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testFetchContentInfoWithDisabledContentHandler( $parameters, $expected ) {
		$this->markTestSkipped( "FIXME -- Error: Call to a member function getContentHandler() on null" );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\EditInfo' )
			->setConstructorArgs( [
				$parameters['wikiPage'],
				$parameters['revision'],
				null
			] )
			->setMethods( [ 'hasContentForEditMethod' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasContentForEditMethod' )
			->will( $this->returnValue( false ) );

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	public function wikiPageDataProvider() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		#0 No parserOutput object
		$editInfo = (object)[];
		$editInfo->output = null;

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			],
			null
		];

		#1
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( false ) );

		$provider[] = [
			[
				'editInfo' => false,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			],
			null
		];

		#2
		$editInfo = (object)[];
		$editInfo->output = new ParserOutput();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			],
			$editInfo->output
		];

		#3
		$editInfo = (object)[];

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->will( $this->returnValue( $editInfo ) );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub()
			],
			null
		];

		return $provider;
	}

	private function newRevisionStub() {

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRawText', 'getContent', 'getSize', 'getSha1' ] )
			->getMock();

		// Needed for the abstract class
		$revision->expects( $this->any() )
			->method( 'getSize' )
			->will( $this->returnValue( 0 ) );

		// Needed for the abstract class
		$revision->expects( $this->any() )
			->method( 'getSha1' )
			->will( $this->returnValue( 0 ) );

		$revision->expects( $this->any() )
			->method( 'getRawText' )
			->will( $this->returnValue( 'Foo' ) );

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValueMap( [
				[ \MediaWiki\Revision\SlotRecord::MAIN, \MediaWiki\Revision\RevisionRecord::RAW, null, 'Foo' ],
				[ \MediaWiki\Revision\SlotRecord::MAIN, \MediaWiki\Revision\RevisionRecord::FOR_PUBLIC, null, $this->newContentStub() ],
			] ) );

		$revision->expects( $this->any() )
			->method( 'getSize' )
			->will( $this->returnValue( strlen( 'Foo' ) ) );

		$revision->expects( $this->any() )
			->method( 'getSha1' )
			->will( $this->returnValue( \Wikimedia\base_convert( sha1( 'Foo' ), 16, 36 ) ) );

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
