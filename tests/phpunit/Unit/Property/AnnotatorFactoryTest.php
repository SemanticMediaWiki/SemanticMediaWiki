<?php

namespace SMW\Tests\Unit\Property;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\PageInfo;
use SMW\Property\Annotator;
use SMW\Property\AnnotatorFactory;
use SMW\Property\Annotators\AttachmentLinkPropertyAnnotator;
use SMW\Property\Annotators\CategoryPropertyAnnotator;
use SMW\Property\Annotators\MandatoryTypePropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Property\Annotators\RedirectPropertyAnnotator;
use SMW\Property\Annotators\SchemaPropertyAnnotator;
use SMW\Property\Annotators\SortKeyPropertyAnnotator;
use SMW\Property\Annotators\TranslationPropertyAnnotator;
use SMW\Store;

/**
 * @covers \SMW\Property\AnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class AnnotatorFactoryTest extends TestCase {

	private Store $store;

	protected function setUp(): void {
		parent::setUp();
		$this->store = $this->createMock( Store::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AnnotatorFactory::class,
			new AnnotatorFactory( $this->store )
		);
	}

	public function testNewNullPropertyAnnotator() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			NullPropertyAnnotator::class,
			$instance->newNullPropertyAnnotator( $semanticData )
		);
	}

	public function testNewRedirectPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( RedirectTargetFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			RedirectPropertyAnnotator::class,
			$instance->newRedirectPropertyAnnotator( $propertyAnnotator, $redirectTargetFinder )
		);
	}

	public function testNewPredefinedPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageInfo = $this->getMockBuilder( PageInfo::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			PredefinedPropertyAnnotator::class,
			$instance->newPredefinedPropertyAnnotator( $propertyAnnotator, $pageInfo )
		);
	}

	public function testNewSortKeyPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			SortKeyPropertyAnnotator::class,
			$instance->newSortKeyPropertyAnnotator( $propertyAnnotator, 'Foo' )
		);
	}

	public function testNewTranslationPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			TranslationPropertyAnnotator::class,
			$instance->newTranslationPropertyAnnotator( $propertyAnnotator, [] )
		);
	}

	public function testNewCategoryPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			CategoryPropertyAnnotator::class,
			$instance->newCategoryPropertyAnnotator( $propertyAnnotator, [] )
		);
	}

	public function testCanConstructMandatoryTypePropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			MandatoryTypePropertyAnnotator::class,
			$instance->newMandatoryTypePropertyAnnotator( $propertyAnnotator )
		);
	}

	public function testCanConstructSchemaPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			SchemaPropertyAnnotator::class,
			$instance->newSchemaPropertyAnnotator( $propertyAnnotator )
		);
	}

	public function testCanConstructAttachmentLinkPropertyAnnotator() {
		$propertyAnnotator = $this->getMockBuilder( Annotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory( $this->store );

		$this->assertInstanceOf(
			AttachmentLinkPropertyAnnotator::class,
			$instance->newAttachmentLinkPropertyAnnotator( $propertyAnnotator )
		);
	}

}
