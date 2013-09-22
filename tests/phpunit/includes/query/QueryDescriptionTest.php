<?php

namespace SMW\Test;

/**
 * Tests for the QueryData class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMWDescription
 * @covers \SMWThingDescription
 * @covers \SMWClassDescription
 * @covers \SMWConceptDescription
 * @covers \SMWNamespaceDescription
 * @covers \SMWValueDescription
 * @covers \SMWConjunction
 * @covers \SMWDisjunction
 * @covers \SMWSomeProperty
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class QueryDescriptionTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @dataProvider descriptionDataProvider
	 *
	 * @param $setup
	 * @param $expectedSet
	 */
	public function testGenericInterface( $setup, $expectedSet ) {

		$this->assertInstanceOf( $setup['class'] ,$setup['description'] );

		foreach ( $expectedSet as $method => $value ) {

			$caller     = array( $setup['description'], $method );
			$parameters = array();
			$expected   = $value;

			// pass-by-reference
			if ( $method === 'prune' ) {
				$maxsize  = $value['parameters'][0];
				$maxDepth = $value['parameters'][1];
				$log      = $value['parameters'][2];

				$parameters = array( &$maxsize, &$maxDepth, &$log );
				$expected = $value['result'];
			}

			$result = call_user_func_array( $caller, $parameters );

			$this->assertEquals(
				$expected,
				$result,
				"Asserts that {$method} returns a result"
			);

		}

	}

	/**
	 * @return array
	 */
	public function descriptionDataProvider() {

		$provider = array();

		// #0 SMWThingDescription
		$description = new \SMWThingDescription();

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWThingDescription',
			),
			array(
				'getQueryString'   => '',
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 0,
				'getDepth'         => 0,
				'getQueryFeatures' => 0,
				'prune'            => array( 'parameters' => array( 1, 1, 1 ), 'result' => $description )
			)
		);

		// #1 SMWClassDescription
		$content = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getDBkey' => 'Lula'
		) );

		$description = new \SMWClassDescription( $content );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWClassDescription',
			),
			array(
				'getQueryString'   => '[[::Lula]]',
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 0,
				'getCategories'    => array( $content ),
				'getQueryFeatures' => 2,
				'prune'            => array( 'parameters' => array( 1, 1, 1 ), 'result' => $description )
			)
		);

		// #2 SMWConceptDescription
		$content = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getDBkey' => 'Lila'
		) );

		$description = new \SMWConceptDescription( $content );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWConceptDescription',
			),
			array(
				'getQueryString'   => '[[::Lila]]',
				'getConcept'       => $content,
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 0,
				'getQueryFeatures' => 4,
				'prune'            => array( 'parameters' => array( 1, 1, 1 ), 'result' => $description )
			)
		);

		// #3 SMWNamespaceDescription
		$description = new \SMWNamespaceDescription( NS_MAIN );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWNamespaceDescription',
			),
			array(
				'getQueryString'   => "[[:+]]",
				'getNamespace'     => NS_MAIN,
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 0,
				'getQueryFeatures' => 8,
				'prune'            => array( 'parameters' => array( 1, 1, 1 ), 'result' => $description )
			)
		);

		// #4 SMWValueDescription
		$dataItem = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getDBkey'     => 'Lamba',
		) );

		$description = new \SMWValueDescription( $dataItem, null, SMW_CMP_EQ );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWValueDescription',
			),
			array(
				'getQueryString'   => "[[:::Lamba]]",
				'isSingleton'      => true,
				'getDataItem'      => $dataItem,
				'getComparator'    => SMW_CMP_EQ,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 0,
				'getQueryFeatures' => 0,
				'prune'            => array( 'parameters' => array( 1, 1, 1 ), 'result' => $description )
			)
		);

		// #5 SMWConjunction
		$nsDescription = new \SMWNamespaceDescription( NS_MAIN );
		$description   = new \SMWConjunction( array( new \SMWThingDescription(), $nsDescription ) );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWConjunction',
			),
			array(
				'getQueryString'   => '[[:+]]',
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 0,
				'getQueryFeatures' => 24,
				'prune'            => array( 'parameters' => array( 1, 1, array() ), 'result' => $nsDescription )
			)
		);

		// #6 SMWDisjunction
		$nsDescription    = new \SMWNamespaceDescription( NS_MAIN );
		$thingDescription = new \SMWThingDescription();
		$description      = new \SMWDisjunction( array( $thingDescription, $nsDescription ) );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWDisjunction',
			),
			array(
				'getQueryString'   => '+',
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 0,
				'getDepth'         => 0,
				'getQueryFeatures' => 32,
				'prune'            => array( 'parameters' => array( 1, 1, array() ), 'result' => $thingDescription )
			)
		);

		// #7 SMWSomeProperty
		$thingDescription = new \SMWThingDescription();

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'getDBkey' => 'Property',
		) );

		$description = new \SMWSomeProperty( $property, $thingDescription );

		$provider[] = array(
			array(
				'description' => $description,
				'class'       => 'SMWSomeProperty',
			),
			array(
				'getQueryString'   => '[[::+]]',
				'isSingleton'      => false,
				'getPrintRequests' => array(),
				'getSize'          => 1,
				'getDepth'         => 1,
				'getQueryFeatures' => 1,
				'prune'            => array( 'parameters' => array( 1, 1, array() ), 'result' => $description )
			)
		);

		return $provider;
	}


}