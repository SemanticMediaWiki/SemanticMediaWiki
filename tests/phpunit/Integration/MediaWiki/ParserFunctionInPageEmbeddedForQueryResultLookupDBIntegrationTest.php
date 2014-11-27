<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\Validators\QueryResultValidator;

use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWQueryParser as QueryParser;
use SMWQuery as Query;
use SMW\Query\Language\SomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMW\Query\Language\ThingDescription as ThingDescription;
use SMW\Query\Language\ValueDescription as ValueDescription;

use Title;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ParserFunctionInPageEmbeddedForQueryResultLookupDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $titles = array();

	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = new QueryResultValidator();
		$this->queryParser = new QueryParser();
	}

	protected function tearDown() {
		$pageDeleter= new PageDeleter();

		foreach ( $this->titles as $title ) {
			$pageDeleter->deletePage( $title );
		}

		parent::tearDown();
	}

	public function testCreatePageWithSetParserFunctionForQueryResultLookup() {

		$this->titles[] = Title::newFromText( 'CreatePageWithSetParserFunction-1' );
		$this->titles[] = Title::newFromText( 'CreatePageWithSetParserFunction-2' );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( Title::newFromText( 'Has set parser function test', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$property = DIProperty::newFromUserLabel( 'Has set parser function test' );

		$pageCreator
			->createPage( $this->titles[0] )
			->doEdit( '{{#set:|Has set parser function test=Foo}}' );

		$pageCreator
			->createPage( $this->titles[1] )
			->doEdit( '{{#set:|Has set parser function test=Bar}}' );

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_COUNT;

		$result = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			2,
			$result instanceOf \SMWQueryResult ? $result->getCountValue() : $result
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertCount(
			2,
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

	public function testCreatePageWithSubobjectParserFunctionForQueryResultLookup() {

		$this->titles[] = Title::newFromText( 'CreatePageWithSubobjectParserFunction' );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( Title::newFromText( 'Has subobject parser function test', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$property = DIProperty::newFromUserLabel( 'Has subobject parser function test' );

		$pageCreator
			->createPage( $this->titles[0] )
			->doEdit(
				'{{#subobject:|Has subobject parser function test=WXYZ|@sortkey=B}}' .
				'{{#subobject:|Has subobject parser function test=ABCD|@sortkey=A}}' .
				'{{#subobject:|Has subobject parser function test=ABCD|@sortkey=A}}' .
				'{{#subobject:|Has subobject parser function test=ABCD|@sortkey=C}}' );

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_COUNT;

		$result = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			3,
			$result instanceOf \SMWQueryResult ? $result->getCountValue() : $result
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertCount(
			3,
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

	public function testCreatePageWithPropertyChainQueryResultLookup() {

		$pageCreator = new PageCreator();

		$this->titles[] = Title::newFromText( 'Dreamland' );

		$pageCreator
			->createPage( $this->titles[0] )
			->doEdit( '{{#set:|Located in=Fairyland}}' );

		$this->titles[] = Title::newFromText( 'Fairyland' );

		$pageCreator
			->createPage( $this->titles[1] )
			->doEdit( '{{#set:|Member of=Wonderland}}' );

		$description = $this->queryParser->getQueryDescription(
			'[[Located in.Member of::Wonderland]]'
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_COUNT;

		$result = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			1,
			$result instanceOf \SMWQueryResult ? $result->getCountValue() : $result
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			new DIWikiPage( 'Dreamland', NS_MAIN, '' ),
			$this->getStore()->getQueryResult( $query )
		);
	}

	public function testCreatePageWithSubobjectPropertyChainQueryResultLookup() {

		$pageCreator = new PageCreator();

		$this->titles[] = Title::newFromText( 'Dreamland' );

		$pageCreator
			->createPage( $this->titles[0] )
			->doEdit( '{{#set:|Located in=Fairyland}}' );

		$this->titles[] = Title::newFromText( 'Fairyland' );

		$pageCreator
			->createPage( $this->titles[1] )
			->doEdit( '{{#subobject:|Member of=Wonderland}}' );

		$description = $this->queryParser->getQueryDescription(
			'[[Located in.Has subobject.Member of::Wonderland]]'
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_COUNT;

		$result = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			1,
			$result instanceOf \SMWQueryResult ? $result->getCountValue() : $result
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			new DIWikiPage( 'Dreamland', NS_MAIN, '' ),
			$this->getStore()->getQueryResult( $query )
		);
	}

}
