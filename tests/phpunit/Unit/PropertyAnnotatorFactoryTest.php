<?php

namespace SMW\Tests;

use SMW\PropertyAnnotatorFactory;

/**
 * @covers \SMW\PropertyAnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class PropertyAnnotatorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotatorFactory',
			new PropertyAnnotatorFactory()
		);
	}

	public function testNewNullPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\NullPropertyAnnotator',
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

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\RedirectPropertyAnnotator',
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

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\PredefinedPropertyAnnotator',
			$instance->newPredefinedPropertyAnnotator( $propertyAnnotator, $pageInfo )
		);
	}

	public function testNewSortKeyPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\SortKeyPropertyAnnotator',
			$instance->newSortKeyPropertyAnnotator( $propertyAnnotator, 'Foo' )
		);
	}

	public function testNewCategoryPropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\CategoryPropertyAnnotator',
			$instance->newCategoryPropertyAnnotator( $propertyAnnotator, array() )
		);
	}

	public function testCanConstructMandatoryTypePropertyAnnotator() {

		$propertyAnnotator = $this->getMockBuilder( '\SMW\PropertyAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\MandatoryTypePropertyAnnotator',
			$instance->newMandatoryTypePropertyAnnotator( $propertyAnnotator )
		);
	}

}
