<?php

namespace SMW\Tests;

use SMW\ControlledVocabularyImportFetcher;

/**
 * @covers \SMW\ControlledVocabularyImportFetcher
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ControlledVocabularyImportFetcherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ControlledVocabularyImportFetcher',
			new ControlledVocabularyImportFetcher()
		);
	}

	public function testContainsForNonExistingImportNamespace() {

		$instance = new ControlledVocabularyImportFetcher();

		$this->assertFalse(
			$instance->contains( 'Foo' )
		);
	}

	public function testFetchForNonExistingImportNamespace() {

		$instance = new ControlledVocabularyImportFetcher();

		$this->assertEmpty(
			$instance->fetch( 'Foo' )
		);
	}

	public function testForImportedNamespace() {

		$importedVocabulary = array( 'Foo' => array( 'Bar|Type:Page' ) );

		$instance = new ControlledVocabularyImportFetcher( $importedVocabulary );

		$this->assertTrue(
			$instance->contains( 'Foo' )
		);

		$this->assertEquals(
			array( 'Bar|Type:Page' ),
			$instance->fetch( 'Foo' )
		);
	}

}
