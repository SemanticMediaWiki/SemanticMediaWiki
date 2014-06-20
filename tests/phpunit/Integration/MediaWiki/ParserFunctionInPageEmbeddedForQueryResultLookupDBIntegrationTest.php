<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;

use SMW\DIProperty;

use SMWQuery as Query;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class ParserFunctionInPageEmbeddedForQueryResultLookupDBIntegrationTest extends MwDBaseUnitTestCase {

	/**
	 * FIXME SQLStore QueryEngine is in shambles when it comes to unit testability
	 * on sqlite. It would require considerable effort to get the QueryEngine
	 * testable therefore we exclude sqlite from running.
	 */
	protected $databaseToBeExcluded = array( 'sqlite' );

	protected $titles = array();

	protected function tearDown() {
		$pageDeleter= new PageDeleter();

		foreach ( $this->titles as $title ) {
			$pageDeleter->deletePage( $title );
		}

		parent::tearDown();
	}

	public function testCreatePageWithSetParserFunctionForQueryResultLookup() {

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();

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

		$this->assertEquals(
			2,
			$this->getStore()->getQueryResult( $query )
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertCount(
			2,
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

	public function testCreatePageWithSubobjectParserFunctionForQueryResultLookup() {

		if ( $this->getStore() instanceof \SMWSparqlStore ) {
			$this->markTestSkipped( "SMWSparqlStore currently does not support subobjects" );
		}

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();

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

		$this->assertEquals(
			3,
			$this->getStore()->getQueryResult( $query )
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertCount(
			3,
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

}
