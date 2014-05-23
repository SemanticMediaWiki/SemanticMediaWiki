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

	protected $title = null;

	protected function tearDown() {
		$pageDeleter= new PageDeleter();
		$pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	public function testCreatePageWithSubobjectParserFunctionForQueryResultLookup() {

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();

		$this->title = Title::newFromText( 'CreatePageWithSubobjectParserFunction' );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( Title::newFromText( 'Has subobject parser function test', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$property = DIProperty::newFromUserLabel( 'Has subobject parser function test' );

		$pageCreator
			->createPage( $this->title )
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
