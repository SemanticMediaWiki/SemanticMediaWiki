<?php

namespace SMW\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentIterator;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\Services\ImporterServiceFactory;
use SMW\Services\ServicesContainer;

/**
 * @covers \SMW\Services\ImporterServiceFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterServiceFactoryTest extends TestCase {

	private ServicesContainer $servicesContainer;

	protected function setUp(): void {
		parent::setUp();

		$importStringSource = $this->getMockBuilder( '\ImportStringSource' )
			->disableOriginalConstructor()
			->getMock();

		$wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$importer = $this->getMockBuilder( Importer::class )
			->disableOriginalConstructor()
			->getMock();

		$jsonContentIterator = $this->getMockBuilder( JsonContentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->servicesContainer = new ServicesContainer( [
			'ImportStringSource' => static fn () => $importStringSource,
			'WikiImporter' => static fn () => $wikiImporter,
			'Importer' => static fn () => $importer,
			'JsonContentIterator' => static fn () => $jsonContentIterator,
		] );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ImporterServiceFactory::class,
			new ImporterServiceFactory( $this->servicesContainer )
		);
	}

	public function testCanConstructImportStringSource() {
		$instance = new ImporterServiceFactory(
			$this->servicesContainer
		);

		$this->assertInstanceOf(
			'\ImportStringSource',
			$instance->newImportStringSource( 'Foo' )
		);
	}

	public function testCanConstructWikiImporter() {
		$importSource = $this->getMockBuilder( '\ImportSource' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImporterServiceFactory(
			$this->servicesContainer
		);

		$this->assertInstanceOf(
			'\WikiImporter',
			$instance->newWikiImporter( $importSource )
		);
	}

	public function testCanConstructImporter() {
		$contentIterator = $this->getMockBuilder( ContentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImporterServiceFactory(
			$this->servicesContainer
		);

		$this->assertInstanceOf(
			Importer::class,
			$instance->newImporter( $contentIterator )
		);
	}

	public function testCanConstructJsonContentIterator() {
		$instance = new ImporterServiceFactory(
			$this->servicesContainer
		);

		$this->assertInstanceOf(
			JsonContentIterator::class,
			$instance->newJsonContentIterator( 'Foo' )
		);
	}

}
