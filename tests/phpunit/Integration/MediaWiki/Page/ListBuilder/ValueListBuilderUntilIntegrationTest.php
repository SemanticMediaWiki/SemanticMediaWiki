<?php

namespace SMW\Tests\Integration\MediaWiki\Page\ListBuilder;

use DOMDocument;
use DOMXPath;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Page\ListBuilder\ValueListBuilder;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Stores the subjects in the database, since the order of the list comes from the query the store builds.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\Formatters\PageLister
 * @covers \SMW\MediaWiki\Page\ListBuilder\ValueListBuilder
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class ValueListBuilderUntilIntegrationTest extends SMWIntegrationTestCase {

	private string $subjectPrefix;
	private Property $property;

	protected function setUp(): void {
		parent::setUp();

		$runId = bin2hex( random_bytes( 4 ) );
		$this->subjectPrefix = "UntilPaging{$runId}Subject";
		$this->property = new Property( "UntilPaging{$runId}Property" );

		foreach ( range( 'A', 'F' ) as $letter ) {
			$this->storeSubject( $letter, $letter );
		}
	}

	public function testUntilListsTheSubjectsJustBeforeTheBoundary(): void {
		$this->assertSame(
			[ 'C', 'D', 'E' ],
			$this->listedSubjects( [ 'limit' => 3, 'until' => $this->subjectPrefix . 'F' ] )
		);
	}

	public function testUntilListsEverySubjectBeforeABoundaryNearTheStart(): void {
		$this->assertSame(
			[ 'A', 'B', 'C' ],
			$this->listedSubjects( [ 'limit' => 3, 'until' => $this->subjectPrefix . 'D' ] )
		);
	}

	public function testUntilListsSubjectsThatShareASortKeyInTheOrderOfTheFullList(): void {
		$this->storeSubject( 'C', 'B' );

		$this->assertSame(
			[ 'C', 'D', 'E' ],
			$this->listedSubjects( [ 'limit' => 3, 'until' => $this->subjectPrefix . 'F' ] )
		);
	}

	public function testNextLinkOfAnUntilPageListsTheSubjectsAfterTheShownOnes(): void {
		$this->assertSame(
			[ 'F' ],
			$this->subjectsBehindLink( [ 'limit' => 3, 'until' => $this->subjectPrefix . 'F' ], 'nextn-title' )
		);
	}

	public function testPreviousLinkOfAnUntilPageListsTheSubjectsBeforeTheShownOnes(): void {
		$this->assertSame(
			[ 'A', 'B' ],
			$this->subjectsBehindLink( [ 'limit' => 3, 'until' => $this->subjectPrefix . 'F' ], 'prevn-title' )
		);
	}

	public function testUntilPageNearTheStartHasNoPreviousLink(): void {
		$query = [ 'limit' => 3, 'until' => $this->subjectPrefix . 'D' ];

		$this->assertNotNull( $this->linkQuery( $query, 'nextn-title' ), 'The page has no pager' );
		$this->assertNull( $this->linkQuery( $query, 'prevn-title' ) );
	}

	public function testUntilPagePastTheLastSubjectHasNoNextLink(): void {
		$query = [ 'limit' => 3, 'until' => $this->subjectPrefix . 'Z' ];

		$this->assertNotNull( $this->linkQuery( $query, 'prevn-title' ), 'The page has no pager' );
		$this->assertNull( $this->linkQuery( $query, 'nextn-title' ) );
	}

	private function storeSubject( string $letter, string $sortedAs ): void {
		$semanticData = UtilityFactory::getInstance()->newSemanticDataFactory()->newEmptySemanticData(
			$this->subjectPrefix . $letter
		);

		$semanticData->addPropertyObjectValue( $this->property, new WikiPage( 'Target', NS_MAIN ) );
		$semanticData->addPropertyObjectValue( new Property( '_SKEY' ), new Blob( $this->subjectPrefix . $sortedAs ) );

		$this->getStore()->updateData( $semanticData );
	}

	/**
	 * @return string[] The letters of the subjects the page shows, in the order shown
	 */
	private function listedSubjects( array $query ): array {
		preg_match_all( '/' . $this->subjectPrefix . '(\w)/', strip_tags( $this->render( $query ) ), $matches );

		return $matches[1];
	}

	/**
	 * @return string[] The letters of the subjects on the page that the link opens
	 */
	private function subjectsBehindLink( array $query, string $tooltipMessage ): array {
		$linkQuery = $this->linkQuery( $query, $tooltipMessage );
		$this->assertNotNull( $linkQuery, "The page has no link titled by the message $tooltipMessage" );

		return $this->listedSubjects( $linkQuery );
	}

	/**
	 * The pager links carry the message as their tooltip.
	 */
	private function linkQuery( array $query, string $tooltipMessage ): ?array {
		$tooltip = wfMessage( $tooltipMessage )->numParams( $query['limit'] )->text();

		$document = new DOMDocument();
		$document->loadHTML( '<?xml encoding="utf-8"?>' . $this->render( $query ), LIBXML_NOERROR | LIBXML_NOWARNING );
		$link = ( new DOMXPath( $document ) )->query( "//a[@href][@title='$tooltip']" )->item( 0 );

		if ( $link === null ) {
			return null;
		}

		parse_str( (string)parse_url( $link->getAttribute( 'href' ), PHP_URL_QUERY ), $linkQuery );

		return $linkQuery;
	}

	private function render( array $query ): string {
		return ( new ValueListBuilder( $this->getStore() ) )->createHtml(
			$this->property,
			$this->property->getDiWikiPage(),
			$query
		);
	}

}
