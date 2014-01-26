<?php

namespace SMW\Test;

use SMW\PredefinedPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIProperty;

/**
 * @covers \SMW\PredefinedPropertyAnnotator
 * @covers \SMW\PropertyAnnotatorDecorator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PredefinedPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'updateOutputCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $settings = array(), $pageInfo = array() ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		$predefinedProperty = $this->newMockBuilder()->newObject( 'PageInfoProvider', $pageInfo );

		$settings = $this->newSettings( $settings );

		$context  = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		return new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ), $predefinedProperty
		);
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider specialPropertiesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddSpecialPropertiesOnMockObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData( $setup['subject'] );

		$instance = $this->newInstance( $semanticData, $setup['settings'], $setup['pageInfo'] );
		$instance->attach( $this->newObserver() )->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

		$this->assertEquals(
			$instance->verifyCallback,
			'updateOutputCallback',
			'Asserts that the invoked Observer was notified'
		);

	}

	/**
	 * Verify that the Observer is reachable
	 *
	 * @since 1.9
	 */
	public function updateOutputCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyCallback = 'updateOutputCallback';
	}

	/**
	 * @return array
	 */
	public function specialPropertiesDataProvider() {

		$provider = array();

		// Unknown
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( 'Lala', '_Lula', '-Lila', '' )
				),
				'pageInfo' => array(),
			),
			array(
				'propertyCount' => 0,
			)
		);

		// TYPE_MODIFICATION_DATE
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				),
				'pageInfo' => array( 'getModificationDate' => 1272508903 )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_CREATION_DATE
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_CREATION_DATE )
				),
				'pageInfo' => array( 'getCreationDate' => 1272508903 )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_CDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_NEW_PAGE )
				),
				'pageInfo' => array( 'isNewPage' => true )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => array( true ),
			)
		);

		// TYPE_LAST_EDITOR
		$userPage = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_USER,
		) );

		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_LAST_EDITOR )
				),
				'pageInfo' => array( 'getLastEditor' => $userPage )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_LEDT',
				'propertyValues' => array( 'User:Lula' ),
			)
		);

		// Combined entries
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( '_MDAT', '_LEDT' )
				),
				'pageInfo' => array(
					'getModificationDate' => 1272508903,
					'getLastEditor'      => $userPage
				)
			),
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_MDAT', '_LEDT' ),
				'propertyValues' => array( '2010-04-29T02:41:43', 'User:Lula' ),
			)
		);

		return $provider;
	}

}
