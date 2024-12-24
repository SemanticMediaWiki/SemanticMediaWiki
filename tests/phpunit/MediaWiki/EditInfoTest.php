<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use ParserOutput;
use SMW\MediaWiki\EditInfo;
use SMW\ParserData;
use SMW\SemanticData;
use Title;
use User;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\EditInfo
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class EditInfoTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EditInfo::class,
			new EditInfo( $wikiPage, $revision, $user )
		);
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 */
	public function testFetchContentInfo( $parameters, $expected ) {
		$instance = new EditInfo(
			$parameters['wikiPage'],
			$parameters['revision'],
			$parameters['user']
		);

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	public function testFetchSemanticData() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$editInfo = (object)[];
		$editInfo->output = new ParserOutput();
		$editInfo->output->setExtensionData( ParserData::DATA_ID, $semanticData );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->willReturn( $editInfo );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditInfo(
			$wikiPage,
			$this->newRevisionStub(),
			$user
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
		$instance = new EditInfo(
			$parameters['wikiPage'],
			$parameters['revision'],
			$parameters['user']
		);

		$this->assertEquals(
			$expected,
			$instance->fetchEditInfo()->getOutput()
		);
	}

	public function wikiPageDataProvider() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			  ->method( 'canExist' )
			  ->willReturn( true );

		# 0 No parserOutput object
		$editInfo = (object)[];
		$editInfo->output = null;

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->willReturn( $editInfo );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub(),
				'user' => $user
			],
			null
		];

		$editInfo = (object)[];
		$editInfo->output = new ParserOutput();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->willReturn( $editInfo );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub(),
				'user' => $user
			],
			$editInfo->output
		];

		$editInfo = (object)[];

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->setConstructorArgs( [ $title ] )
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->willReturn( $editInfo );

		$provider[] = [
			[
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $this->newRevisionStub(),
				'user' => $user
			],
			null
		];

		return $provider;
	}

	private function newRevisionStub() {
		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		// Needed for the abstract class
		$revision->expects( $this->any() )
			->method( 'getSize' )
			->willReturn( strlen( 'Foo' ) );

		// Needed for the abstract class
		$revision->expects( $this->any() )
			->method( 'getSha1' )
			->willReturn( \Wikimedia\base_convert( sha1( 'Foo' ), 16, 36 ) );

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->willReturnMap( [
				[ SlotRecord::MAIN, RevisionRecord::RAW, null, 'Foo' ],
				[ SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC, null, $this->newContentStub() ],
			] );

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
			->willReturn( 'Foo' );

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->atLeastOnce() )
			->method( 'getContentHandler' )
			->willReturn( $contentHandler );

		return $content;
	}

}
