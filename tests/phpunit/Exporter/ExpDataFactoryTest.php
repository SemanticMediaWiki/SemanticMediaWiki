<?php

namespace SMW\Tests\Exporter;

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
class ExpDataFactoryTest extends \PHPUnit\Framework\TestCase {

	private $exporter;

	protected function setUp(): void {
		parent::setUp();

		$this->exporter = $this->getMockBuilder( '\SMWExporter' )
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
		$expNsResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpNsResource' )
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
			'\SMWExpData',
			$instance->newSiteExpData()
		);
	}

	public function testNewDefinedExpData() {
		$expNsResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpNsResource' )
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
			'\SMWExpData',
			$instance->newDefinedExpData()
		);
	}

	public function testNewOntologyExpData() {
		$expNsResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpNsResource' )
			->disableOriginalConstructor()
			->getMock();

		$this->exporter->expects( $this->atLeastOnce() )
			->method( 'newExpNsResourceById' )
			->willReturn( $expNsResource );

		$instance = new ExpDataFactory(
			$this->exporter
		);

		$this->assertInstanceOf(
			'\SMWExpData',
			$instance->newOntologyExpData( '' )
		);
	}

}
