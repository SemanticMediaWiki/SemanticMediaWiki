<?php

namespace SMW\Tests\Unit\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\Export\ExpData;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ExpDataFactory;

/**
 * @covers \SMW\Exporter\ExpDataFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExpDataFactoryTest extends TestCase {

	private $exporter;

	protected function setUp(): void {
		parent::setUp();

		$this->exporter = $this->getMockBuilder( Exporter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExpDataFactory::class,
			new ExpDataFactory( $this->exporter )
		);
	}

	public function testNewSiteExpData() {
		$expNsResource = $this->getMockBuilder( ExpNsResource::class )
			->disableOriginalConstructor()
			->getMock();

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'newExpNsResourceById' )
			->willReturn( $expNsResource );

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'expandURI' )
			->willReturn( '' );

		$instance = new ExpDataFactory(
			$this->exporter
		);

		$this->assertInstanceOf(
			ExpData::class,
			$instance->newSiteExpData()
		);
	}

	public function testNewDefinedExpData() {
		$expNsResource = $this->getMockBuilder( ExpNsResource::class )
			->disableOriginalConstructor()
			->getMock();

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'newExpNsResourceById' )
			->willReturn( $expNsResource );

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'expandURI' )
			->willReturn( '' );

		$instance = new ExpDataFactory(
			$this->exporter
		);

		$this->assertInstanceOf(
			ExpData::class,
			$instance->newDefinedExpData()
		);
	}

	public function testNewOntologyExpData() {
		$expNsResource = $this->getMockBuilder( ExpNsResource::class )
			->disableOriginalConstructor()
			->getMock();

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'newExpNsResourceById' )
			->willReturn( $expNsResource );

		$instance = new ExpDataFactory(
			$this->exporter
		);

		$this->assertInstanceOf(
			ExpData::class,
			$instance->newOntologyExpData( '' )
		);
	}

}
