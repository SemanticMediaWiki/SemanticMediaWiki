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
			'\SMW\PropertyAnnotator\NullPropertyAnnotator',
			$instance->newNullPropertyAnnotator( $semanticData )
		);
	}

	public function testNewRedirectPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\RedirectPropertyAnnotator',
			$instance->newRedirectPropertyAnnotator( $semanticData, $redirectTargetFinder )
		);
	}

	public function testNewPredefinedPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfo = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\PredefinedPropertyAnnotator',
			$instance->newPredefinedPropertyAnnotator( $semanticData, $pageInfo )
		);
	}

	public function testNewSortKeyPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\SortKeyPropertyAnnotator',
			$instance->newSortKeyPropertyAnnotator( $semanticData, 'Foo' )
		);
	}

	public function testNewCategoryPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\CategoryPropertyAnnotator',
			$instance->newCategoryPropertyAnnotator( $semanticData, array() )
		);
	}

	public function testCanConstructMandatoryTypePropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\MandatoryTypePropertyAnnotator',
			$instance->newMandatoryTypePropertyAnnotator( $semanticData )
		);
	}

}
