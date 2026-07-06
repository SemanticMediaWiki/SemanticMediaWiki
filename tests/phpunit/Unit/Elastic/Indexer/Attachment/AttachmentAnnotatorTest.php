<?php

namespace SMW\Tests\Unit\Elastic\Indexer\Attachment;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataModel\ContainerSemanticData;
use SMW\Elastic\Indexer\Attachment\AttachmentAnnotator;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\AttachmentAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AttachmentAnnotatorTest extends TestCase {

	private $containerSemanticData;

	protected function setUp(): void {
		$this->containerSemanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AttachmentAnnotator::class,
			new AttachmentAnnotator( $this->containerSemanticData )
		);
	}

	public function testGetProperty() {
		$instance = new AttachmentAnnotator(
			$this->containerSemanticData
		);

		$this->assertInstanceOf(
			Property::class,
			$instance->getProperty()
		);
	}

	public function testGetContainer() {
		$instance = new AttachmentAnnotator(
			$this->containerSemanticData
		);

		$this->assertInstanceOf(
			Container::class,
			$instance->getContainer()
		);
	}

	public function testGetSemanticData() {
		$instance = new AttachmentAnnotator(
			$this->containerSemanticData
		);

		$this->assertEquals(
			$this->containerSemanticData,
			$instance->getSemanticData()
		);
	}

	/**
	 * @dataProvider documentProvider
	 */
	public function testAddAnnotation( $document, $expected ) {
		$this->containerSemanticData->expects( $this->once() )
			->method( 'addPropertyObjectValue' )
			->with( new Property( $expected ) );

		$instance = new AttachmentAnnotator(
			$this->containerSemanticData,
			$document
		);

		$this->assertEquals(
			$instance,
			$instance->addAnnotation()
		);
	}

	public function documentProvider() {
		yield 'date' => [
			[ '_source' => [ 'attachment' => [ 'date' => '1362200400' ] ] ],
			'_CONT_DATE'
		];

		yield 'content_type' => [
			[ '_source' => [ 'attachment' => [ 'content_type' => 'Foo' ] ] ],
			'_CONT_TYPE'
		];

		yield 'author' => [
			[ '_source' => [ 'attachment' => [ 'author' => 'Bar' ] ] ],
			'_CONT_AUTHOR'
		];

		yield 'title' => [
			[ '_source' => [ 'attachment' => [ 'title' => 'Foobar' ] ] ],
			'_CONT_TITLE'
		];

		yield 'language' => [
			[ '_source' => [ 'attachment' => [ 'language' => 'en' ] ] ],
			'_CONT_LANG'
		];

		yield 'content_length' => [
			[ '_source' => [ 'attachment' => [ 'content_length' => '1001' ] ] ],
			'_CONT_LEN'
		];

		yield 'keywords' => [
			[ '_source' => [ 'attachment' => [ 'keywords' => '1001' ] ] ],
			'_CONT_KEYW'
		];
	}

}
