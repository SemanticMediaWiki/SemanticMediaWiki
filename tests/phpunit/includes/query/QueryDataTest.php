<?php

namespace SMW\Test;

use SMW\HashIdGenerator;
use SMW\QueryData;

use SMWQueryProcessor;
use Title;

/**
 * @covers \SMW\QueryData
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
class QueryDataTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\QueryData';
	}

	/**
	 * @since 1.9
	 *
	 * @return QueryProcessor
	 */
	private function getQueryProcessor( array $rawParams ) {
		return SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);
	}

	/**
	 * @since 1.9
	 */
	private function newInstance( Title $title = null ) {
		return new QueryData( $title );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->newInstance( $this->newTitle() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @since 1.9
	 */
	public function testGetProperty() {
		$instance = $this->newInstance( $this->newTitle() );
		$this->assertInstanceOf( '\SMWDIProperty', $instance->getProperty() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetErrors() {
		$instance = $this->newInstance( $this->newTitle() );
		$this->assertInternalType( 'array', $instance->getErrors() );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddQueryData( array $params, array $expected ) {
		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->setQueryId( new HashIdGenerator( $params ) );
		$instance->add( $query, $formattedParams );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getContainer()->getSemanticData() );
		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testQueryIdException( array $params, array $expected ) {

		$this->setExpectedException( '\SMW\UnknownIdException' );
		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->add( $query, $formattedParams );

	}

	/**
	 * @return array
	 */
	public function queryDataProvider() {

		$provider = array();

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			),
			array(
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
			)
		);

		// #1
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
			)
		);

		// #2 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=bar'
			),
			array(
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
			)
		);

		return $provider;
	}
}
