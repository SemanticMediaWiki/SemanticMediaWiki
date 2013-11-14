<?php

namespace SMW\Test;

use SMW\DescriptionProfiler;
use SMW\FormatProfiler;
use SMW\HashIdGenerator;
use SMW\NullProfiler;
use SMW\Subobject;

use SMWQueryProcessor;
use Title;

/**
 * @covers \SMWQueryProcessor
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
class QueryProfilerQueryProcessorIntegrationTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	private function runQueryProcessor( array $rawParams ) {
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
	private function newInstance( $rawparams, $description, $format ) {

		$instance = new NullProfiler(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( $rawparams )
		);

		$instance = new DescriptionProfiler( $instance, $description );
		$instance = new FormatProfiler( $instance, $format );

		return $instance;
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testCreateProfile( array $rawparams, array $expected ) {

		list( $query, $formattedParams ) = $this->runQueryProcessor( $rawparams );

		$instance = $this->newInstance(
			$rawparams,
			$query->getDescription(),
			$formattedParams['format']->getValue()
		);

		$instance->createProfile();

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getContainer()->getSemanticData() );
		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );
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
