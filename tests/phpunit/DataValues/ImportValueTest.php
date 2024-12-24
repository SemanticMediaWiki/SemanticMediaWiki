<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;

/**
 * @covers \SMW\DataValues\ImportValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueTest extends \PHPUnit\Framework\TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new ImportValueParser( $mediaWikiNsContentReader ) );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\DataValues\ImportValue',
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
