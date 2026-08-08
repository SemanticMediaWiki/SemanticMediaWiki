<?php

namespace SMW\Tests\Unit\Elastic\Indexer;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Elastic\Indexer\DocumentCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DocumentCreatorTest extends TestCase {

	private $store;
	private string $entityCollation;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		// The indexed sort key depends on the collation, so pin it instead of
		// inheriting whatever the wiki running the suite happens to configure
		$this->entityCollation = $GLOBALS['smwgEntityCollation'];
		$GLOBALS['smwgEntityCollation'] = 'identity';
	}

	protected function tearDown(): void {
		$GLOBALS['smwgEntityCollation'] = $this->entityCollation;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DocumentCreator::class,
			new DocumentCreator( $this->store )
		);
	}

	public function testGetDocumentCreationDuration() {
		$instance = new DocumentCreator( $this->store );

		$this->assertIsInt(

			$instance->getDocumentCreationDuration()
		);
	}

	public function testNewFromSemanticData_RedirectDelete() {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'findIdAndSortKey' )
			->willReturn( [ 42, 'Duck, Donald' ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->with( new Property( '_REDI' ) )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$instance = new DocumentCreator( $this->store );
		$document = $instance->newFromSemanticData( $semanticData );

		$this->assertInstanceOf(
			Document::class,
			$document
		);

		$this->assertTrue(
			$document->isType( 'type/delete' )
		);
	}

	/**
	 * @dataProvider dataItemsProvider
	 */
	public function testNewFromSemanticData( $dataItems ) {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'findIdAndSortKey' )
			->willReturn( [ 42, 'Duck, Donald' ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( $dataItems );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [ $semanticData ] );

		$instance = new DocumentCreator( $this->store );

		$this->assertInstanceOf(
			Document::class,
			$instance->newFromSemanticData( $semanticData )
		);
	}

	/**
	 * @dataProvider dataItemsProvider
	 */
	public function testNewFromSemanticData_SubDataType( $dataItems ) {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( '_SOBJ' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'findIdAndSortKey' )
			->willReturn( [ 42, 'Duck, Donald' ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( $dataItems );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [ $semanticData ] );

		$instance = new DocumentCreator( $this->store );

		$this->assertInstanceOf(
			Document::class,
			$instance->newFromSemanticData( $semanticData )
		);
	}

	/**
	 * The parse path (page save) leaves a page value's sort key unset, so it
	 * falls back to the DB key, while the SQLStore read path replaces it with
	 * the collated `smw_sort` value. The indexed field has to be the same
	 * either way, otherwise `sort=<Page property>` orders one half of the index
	 * against the other. See #7079.
	 */
	public function testPageValueFieldDoesNotDependOnHowSemanticDataWasProduced() {
		$fromParse = WikiPage::newFromText( 'Donald Duck 1998 01' );

		$fromStore = WikiPage::newFromText( 'Donald Duck 1998 01' );
		$fromStore->setSortKey( 'CKT5Aq4n135JBpy445EK11040HC1tC3S2m' );

		$this->assertSame(
			[ 'Donald Duck 1998 01' ],
			$this->newDocument( $fromParse )->getData()['P:1001']['wpgField']
		);

		$this->assertSame(
			$this->newDocument( $fromParse )->getData()['P:1001']['wpgField'],
			$this->newDocument( $fromStore )->getData()['P:1001']['wpgField']
		);
	}

	/**
	 * `IdEntityFinder` carries the already collated `smw_sort` value as a `sort`
	 * option, so preferring it produced a different encoding from the freshly
	 * collated one used on the parse path. See #7079.
	 */
	public function testSubjectSortKeyDoesNotDependOnHowSemanticDataWasProduced() {
		$fromParse = WikiPage::newFromText( 'Foo' );
		$fromParse->setSortKey( 'Duck, Donald' );

		$fromStore = WikiPage::newFromText( 'Foo' );
		$fromStore->setSortKey( 'Duck, Donald' );
		$fromStore->setOption( 'sort', 'CKT5Aq4n135JBpy445EK11040HC1tC3S2m' );

		$value = WikiPage::newFromText( 'Bar' );

		$this->assertSame(
			'Duck, Donald',
			$this->newDocument( $value, $fromParse )->getData()['subject']['sortkey']
		);

		$this->assertSame(
			$this->newDocument( $value, $fromParse )->getData()['subject']['sortkey'],
			$this->newDocument( $value, $fromStore )->getData()['subject']['sortkey']
		);
	}

	/**
	 * A `uca-*` collation emits a binary sort key whose bytes are not valid
	 * UTF-8 on their own. Passing it through `mb_convert_encoding` replaced
	 * those bytes with `?` and collapsed distinct keys onto each other, which
	 * silently dropped the numeric component from the ordering. See #7079.
	 */
	public function testSubjectSortKeysStayDistinctAndOrderedUnderAUcaCollation() {
		if ( !extension_loaded( 'intl' ) ) {
			$this->markTestSkipped( 'Skipping because intl (ICU) is not available.' );
		}

		$GLOBALS['smwgEntityCollation'] = 'uca-default-u-kn';
		$sortKeys = [];

		foreach ( [ '2000', '1998', '1999' ] as $year ) {
			$sortKeys[$year] = $this->newDocument(
				WikiPage::newFromText( 'Bar' ),
				WikiPage::newFromText( "Donald Duck 01 ($year)" )
			)->getData()['subject']['sortkey'];
		}

		// Guards the collapse itself: mangling made all three byte-identical,
		// and an ordering assertion alone passes on three equal values
		$this->assertCount( 3, array_unique( $sortKeys ) );

		$sorted = array_values( $sortKeys );
		sort( $sorted, SORT_STRING );

		$this->assertSame(
			[ $sortKeys['1998'], $sortKeys['1999'], $sortKeys['2000'] ],
			$sorted
		);
	}

	/**
	 * The stub document is merged into the referenced page's own document, so
	 * the two have to agree on the sort key rather than merely happening to
	 * produce the same value from different sources. See #7079.
	 */
	public function testUpsertHeadAndOwnDocumentAgreeOnTheSortKey() {
		$subject = WikiPage::newFromText( 'Bar' );
		$subject->setSortKey( 'Duck, Donald' );

		$ownDocument = $this->newDocument( WikiPage::newFromText( 'Irrelevant' ), $subject );
		$head = $this->newDocument( WikiPage::newFromText( 'Bar' ) )->getSubDocumentById( 42 );

		$this->assertSame(
			$ownDocument->getData()['subject']['sortkey'],
			$head->getData()['subject']['sortkey']
		);
	}

	/**
	 * Without a recorded sort key the stub must not fall back to the sort key of
	 * the item that referenced it, which is the page title on the parse path and
	 * the collated value on the SQLStore read path. See #7079.
	 */
	public function testUpsertHeadFallsBackToThePageNameWhenTheStoreHasNoSortKey() {
		$value = WikiPage::newFromText( 'Bar' );
		$value->setSortKey( 'CKT5Aq4n135JBpy445EK11040HC1tC3S2m' );

		$document = $this->newDocument( $value, null, [ 42, '' ] );

		$this->assertSame(
			'Bar',
			$document->getSubDocumentById( 42 )->getData()['subject']['sortkey']
		);
	}

	/**
	 * The stub document created for a page that is only referenced as a value
	 * is merged into that page's own document, so it has to carry the sort key
	 * the store recorded for that page rather than one derived from the
	 * referencing annotation. See #7079.
	 */
	public function testUpsertHeadCarriesTheStoredSortKeyOfTheReferencedPage() {
		$value = WikiPage::newFromText( 'Bar' );
		$value->setSortKey( 'CKT5Aq4n135JBpy445EK11040HC1tC3S2m' );

		$document = $this->newDocument( $value );

		$this->assertSame(
			'Duck, Donald',
			$document->getSubDocumentById( 42 )->getData()['subject']['sortkey']
		);
	}

	private function newDocument( WikiPage $value, ?WikiPage $subject = null, ?array $idAndSortKey = null ): Document {
		$subject ??= WikiPage::newFromText( 'Foo' );
		$property = new Property( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'findIdAndSortKey' )
			->willReturn( $idAndSortKey ?? [ 42, 'Duck, Donald' ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ $subject ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( [ $value ] );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		return ( new DocumentCreator( $this->store ) )->newFromSemanticData( $semanticData );
	}

	public function dataItemsProvider() {
		yield 'page_type' => [
			[ WikiPage::newFromText( 'Bar' ) ]
		];

		yield 'text_type' => [
			[ new Blob( 'test' ) ]
		];

		yield 'num_type' => [
			[ new Number( 9999 ) ]
		];

		yield 'bool_type' => [
			[ new Boolean( true ) ]
		];

		yield 'uri_type' => [
			[ Uri::doUnserialize( 'http://example.org' ) ]
		];

		yield 'dat_type' => [
			[ Time::newFromTimestamp( '1362200400' ) ]
		];
	}

}
