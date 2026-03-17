<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Services\DataValueServiceFactory;

/**
 * @covers \SMW\DataValues\ImportValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueTest extends TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new ImportValueParser( $mediaWikiNsContentReader ) );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ImportValue::class,
			new ImportValue()
		);
	}

	public function testErrorForInvalidUserValue() {
		$instance = new ImportValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'FooBar' );

		$this->assertEquals(
			'FooBar',
			$instance->getWikiValue()
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}
