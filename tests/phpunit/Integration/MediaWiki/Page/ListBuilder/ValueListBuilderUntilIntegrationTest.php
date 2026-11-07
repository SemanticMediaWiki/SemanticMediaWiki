<?php

namespace SMW\Tests\Integration\MediaWiki\Page\ListBuilder;

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
		$html = ( new ValueListBuilder( $this->getStore() ) )->createHtml(
			$this->property,
			$this->property->getDiWikiPage(),
			$query
		);

		preg_match_all( '/' . $this->subjectPrefix . '(\w)/', strip_tags( $html ), $matches );

		return $matches[1];
	}

}
