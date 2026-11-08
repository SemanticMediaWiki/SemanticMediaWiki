<?php

namespace SMW\Tests\Integration\SQLStore\EntityStore;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Stores the subjects in the database, since the duplicates come from the query the store builds.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class PropertySubjectsListedOnceIntegrationTest extends SMWIntegrationTestCase {

	private string $subjectPrefix;
	private Property $property;

	protected function setUp(): void {
		parent::setUp();

		$runId = bin2hex( random_bytes( 4 ) );
		$this->subjectPrefix = "ListedOnce{$runId}Subject";
		$this->property = new Property( "ListedOnce{$runId}Property" );

		$this->storeSubject( 'A', 'First value' );
		$this->storeSubject( 'B', 'First value', 'Second value' );
		$this->storeSubject( 'C', 'Second value' );
	}

	public function testSubjectWithTwoValuesIsListedOnce(): void {
		$this->assertSame( [ 'A', 'B', 'C' ], $this->listedSubjects( $this->newRequestOptions() ) );
	}

	public function testSubjectWithTwoValuesIsListedOnceOnACursorPage(): void {
		$requestOptions = $this->newRequestOptions();
		$requestOptions->setOption( RequestOptions::CURSOR_MODE, true );

		$this->assertSame( [ 'A', 'B', 'C' ], $this->listedSubjects( $requestOptions ) );
	}

	private function storeSubject( string $letter, string ...$values ): void {
		$semanticData = UtilityFactory::getInstance()->newSemanticDataFactory()->newEmptySemanticData(
			$this->subjectPrefix . $letter
		);

		foreach ( $values as $value ) {
			$semanticData->addPropertyObjectValue( $this->property, new WikiPage( $value, NS_MAIN ) );
		}

		$this->getStore()->updateData( $semanticData );
	}

	private function newRequestOptions(): RequestOptions {
		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );

		return $requestOptions;
	}

	/**
	 * @return string[] The letters of the listed subjects, in the order listed
	 */
	private function listedSubjects( RequestOptions $requestOptions ): array {
		$letters = [];

		foreach ( $this->getStore()->getAllPropertySubjects( $this->property, $requestOptions ) as $subject ) {
			$letters[] = substr( $subject->getDBkey(), strlen( $this->subjectPrefix ) );
		}

		return $letters;
	}

}
