<?php

namespace SMW\Tests\Property;

use SMW\Property\AnnotatorFactory;

/**
 * @covers \SMW\Property\AnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class AnnotatorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AnnotatorFactory::class,
			new AnnotatorFactory()
		);
	}

	public function testNewNullPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\NullPropertyAnnotator',
			$instance->newNullPropertyAnnotator( $semanticData )
		);
	}

	public function testNewRedirectPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\RedirectPropertyAnnotator',
			$instance->newRedirectPropertyAnnotator( $propertyAnnotator, $redirectTargetFinder )
		);
	}

	public function testNewPredefinedPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfo = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\PredefinedPropertyAnnotator',
			$instance->newPredefinedPropertyAnnotator( $propertyAnnotator, $pageInfo )
		);
	}

	public function testNewSortKeyPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\SortKeyPropertyAnnotator',
			$instance->newSortKeyPropertyAnnotator( $propertyAnnotator, 'Foo' )
		);
	}

	public function testNewTranslationPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\TranslationPropertyAnnotator',
			$instance->newTranslationPropertyAnnotator( $propertyAnnotator, [] )
		);
	}

	public function testNewCategoryPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\CategoryPropertyAnnotator',
			$instance->newCategoryPropertyAnnotator( $propertyAnnotator, [] )
		);
	}

	public function testCanConstructMandatoryTypePropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\MandatoryTypePropertyAnnotator',
			$instance->newMandatoryTypePropertyAnnotator( $propertyAnnotator )
		);
	}

	public function testCanConstructSchemaPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\SchemaPropertyAnnotator',
			$instance->newSchemaPropertyAnnotator( $propertyAnnotator )
		);
	}

	public function testCanConstructAttachmentLinkPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\AttachmentLinkPropertyAnnotator',
			$instance->newAttachmentLinkPropertyAnnotator( $propertyAnnotator )
		);
	}

}
