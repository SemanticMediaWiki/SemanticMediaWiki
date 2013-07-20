<?php

namespace SMW\Test;

use SMW\PropertyAnnotationComplementor;
use SMW\SemanticData;
use SMW\DIProperty;

/**
 * Tests for the PropertyAnnotationComplementor class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\PropertyAnnotationComplementor
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertyAnnotationComplementorTest extends ParserTestCase {

	/** boolean */
	protected $observerStatus = null;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertyAnnotationComplementor';
	}

	/**
	 * Returns a Observer object
	 *
	 * @note We use a callback to set the observerStatus
	 * in order to verify if an approriate notifcation
	 * was sent from the Publisher class
	 *
	 * @since 1.9
	 *
	 * @return Observer
	 */
	public function getMockObsever() {

		$observer = $this->getMockBuilder( 'SMW\Observer' )
			->setMethods( array( 'updateOutput' ) )
			->getMock();

		$observer->expects( $this->any() )
			->method( 'updateOutput' )
			->will( $this->returnCallback( array( $this, 'mockObserverCallback') ) );

		return $observer;
	}

	/**
	 * Helper method that returns a PropertyAnnotationComplementor object
	 *
	 * @param SemanticData $semanticData
	 * @param array $settings
	 *
	 * @return PropertyAnnotationComplementor
	 */
	private function getInstance( SemanticData $semanticData = null, $settings = array() ) {
		return new PropertyAnnotationComplementor(
			$semanticData === null ? $this->newMockObject()->getMockSemanticData() : $semanticData,
			$this->getSettings( $settings )
		);
	}

	/**
	 * @test PropertyAnnotationComplementor::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test PropertyAnnotationComplementor::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategories( array $setup, array $expected ) {

		$this->observerStatus = null;

		$semanticData = new SemanticData(
			$this->newSubject( $this->getTitle( $setup['namespace'] ) )
		);

		$instance = $this->getInstance( $semanticData, $setup['settings'] );
		$instance->addCategories( $setup['categories'] );

		$this->assertSemanticData( $semanticData, $expected );

	}

	/**
	 * @test PropertyAnnotationComplementor::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategoriesMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		$semanticData = new SemanticData(
			$this->newSubject( $this->getTitle( $setup['namespace'] ) )
		);

		// Create instance and attach mock Observer
		$instance = $this->getInstance( $semanticData, $setup['settings'] );
		$instance->attach( $this->getMockObsever() );
		$instance->addCategories( $setup['categories'] );

		$this->assertSemanticData( $semanticData, $expected );

		// Just verify that the Publisher was able to reach the Observer
		$this->assertTrue(
			$this->observerStatus,
			'Failed asserting that the invoked Observer received a notification from the Publisher (Subject)'
		);

	}

	/**
	 * @test PropertyAnnotationComplementor::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategoriesObserverIntegration( array $setup, array $expected ) {

		$subject  = $this->newSubject( $this->getTitle( $setup['namespace'] ) );
		$semanticData = new SemanticData( $subject );

		// Test "real" observer integration
		$title        = $subject->getTitle();
		$parserOutput = $this->newParserOutput();
		$parserData   = $this->getParserData( $title, $parserOutput );

		// Create instance and attach mock Observer
		$instance = $this->getInstance( $parserData->getData(), $setup['settings'] );
		$instance->attach( $parserData );
		$instance->addCategories( $setup['categories'] );

		// Re-read data from the $parserOutput object
		$newParserData = $this->getParserData( $title, $parserOutput );
		$this->assertSemanticData( $newParserData->getData(), $expected );

	}

	/**
	 * @test PropertyAnnotationComplementor::addDefaultSort
	 * @dataProvider defaultSortDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddDefaultSortMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		// Simple add and verify
		// Test update notification using a Observer mock

		$subject  = $this->newSubject( $setup['title'] );
		$semanticData = new SemanticData( $subject );

		// Create instance and attach mock Observer
		$instance = $this->getInstance( $semanticData );
		$instance->attach( $this->getMockObsever() );
		$instance->addDefaultSort( $setup['sort'] );

		$this->assertSemanticData( $semanticData, $expected );

		// Just verify that the Publisher was able to reach the Observer
		$this->assertTrue(
			$this->observerStatus,
			'Failed asserting that the invoked Observer received a notification from the Publisher (Subject)'
		);
	}

	/**
	 * @test PropertyAnnotationComplementor::addSpecialProperties
	 * @dataProvider specialPropertiesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddSpecialPropertiesMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		// Simple add and verify
		// Test update notification using a Observer mock

		$subject  = isset( $setup['subject'] ) ? $setup['subject'] : $this->newSubject( $setup['title'] );
		$semanticData = new SemanticData( $subject );

		// Setup inidivudal mock objects that will be invoked in order to
		// control and support only needed functions
		$wikiPage = $this->newMockObject( $setup['wikipage'] )->getMockWikIPage();
		$revision = $this->newMockObject( $setup['revision'] )->getMockRevision();
		$user     = $this->newMockObject( $setup['user'] )->getMockUser();

		// Create instance and attach mock Observer
		$instance = $this->getInstance( $semanticData, $setup['settings'] );
		$instance->attach( $this->getMockObsever() );
		$instance->addSpecialProperties( $wikiPage, $revision, $user );

		$this->assertSemanticData( $semanticData, $expected );

		// Just verify that the Publisher was able to reach the Observer
		$this->assertTrue(
			$this->observerStatus,
			'Failed asserting that the invoked Observer received a notification from the Publisher (Subject)'
		);

		// Check against pre-existing registered special properties
		$instance->addSpecialProperties( $wikiPage, $revision, $user );
		$this->assertSemanticData( $semanticData, $expected );

	}

	/**
	 * This callback verifies that the Publisher reached the Observer
	 * by comparing the Publisher(Subject) class as its sender
	 */
	public function mockObserverCallback() {
		$this->observerStatus = is_a( func_get_arg( 0 ), $this->getClass() ) ? true : false;
	}

	/**
	 * Provides array of special properties
	 *
	 * @return array
	 */
	public function specialPropertiesDataProvider() {

		$provider = array();

		// Unknown
		$provider[] = array(
			array(
				'title'    => $this->newTitle(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( 'Lala', '_Lula', '-Lila', '' )
				),
				'wikipage' => array(),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 0,
			)
		);

		// TYPE_MODIFICATION_DATE
		$provider[] = array(
			array(
				'title'    => $this->newTitle(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				),
				'wikipage' => array( 'getTimestamp' => 1272508903 ),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_MDAT',
				'propertyValue' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_CREATION_DATE
		$subject = $this->newMockObject( array(
			'getTitle' => $this->newMockObject( array(
				'getDBkey'         => 'Lula',
				'getNamespace'     => NS_MAIN,
				'getFirstRevision' => $this->newMockObject( array(
					'getTimestamp' => 1272508903
				) )->getMockRevision()
			) )->getMockTitle(),
		) )->getMockDIWikiPage();

		$provider[] = array(
			array(
				'subject'    => $subject,
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_CREATION_DATE )
				),
				'wikipage' => array(),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_CDAT',
				'propertyValue' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'title'    => $this->newTitle(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_NEW_PAGE )
				),
				'wikipage' => array(),
				'revision' => array( 'getParentId' => 9001 ),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_NEWP',
				'propertyValue' => array( true ),
			)
		);

		// TYPE_LAST_EDITOR
		$userPage = $this->newMockObject( array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_USER,
		) )->getMockTitle();

		$provider[] = array(
			array(
				'title'    => $this->newTitle(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_LAST_EDITOR )
				),
				'wikipage' => array(),
				'revision' => array(),
				'user'     => array( 'getUserPage' => $userPage )
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_LEDT',
				'propertyValue' => array( 'User:Lula' ),
			)
		);

		// Combine entries
		$provider[] = array(
			array(
				'title'    => $this->newTitle(),
				'settings' => array(
					'smwgPageSpecialProperties' => array( '_MDAT', '_LEDT' )
				),
				'wikipage' => array( 'getTimestamp' => 1272508903 ),
				'revision' => array(),
				'user'     => array( 'getUserPage' => $userPage )
			),
			array(
				'propertyCount' => 2,
				'propertyKey'   => array( '_MDAT', '_LEDT' ),
				'propertyValue' => array( '2010-04-29T02:41:43', 'User:Lula' ),
			)
		);

		return $provider;
	}

	/**
	 * Provides array of defaultSortkeys
	 *
	 * @return array
	 */
	public function defaultSortDataProvider() {

		$provider = array();

		// Sort entry
		$provider[] = array(
			array(
				'title' => $this->newTitle(),
				'sort'  => 'Lala'
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SKEY',
				'propertyValue' => array( 'Lala' ),
			)
		);

		// Empty
		$title = $this->newTitle();
		$provider[] = array(
			array(
				'title' => $title,
				'sort'  => ''
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SKEY',
				'propertyValue' => array( $title->getDBkey() ),
			)
		);

		return $provider;
	}

	/**
	 * Provides array of category names
	 *
	 * @return array
	 */
	public function categoriesDataProvider() {

		$provider = array();

		// Standard category
		$provider[] = array(
			array(
				'namespace'  => NS_MAIN,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_INST',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		// Category hierarchy or Sub-category
		$provider[] = array(
			array(
				'namespace'  => NS_CATEGORY,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SUBC',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		return $provider;
	}
}
