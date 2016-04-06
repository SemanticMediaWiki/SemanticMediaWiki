<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Ask;
use SMW\Tests\Utils\MwApiFactory;

/**
 * @covers \SMW\MediaWiki\Api\Ask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AskTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
	}

	public function testCanConstruct() {

		$instance = new Ask(
			$this->apiFactory->newApiMain( array( 'query' => 'Foo' ) ),
			'ask'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\Ask',
			$instance
		);
	}

	/**
	 * @dataProvider sampleQueryProvider
	 */
	public function testExecute( array $query, array $expected ) {

		$results = $this->apiFactory->doApiRequest( array(
			'action' => 'ask',
			'query' => implode( '|', $query )
		) );

		$this->assertInternalType( 'array', $results );

		// If their is no printrequests array we expect an error array
		if ( isset( $results['query']['printrequests'] ) ) {
			return $this->assertEquals( $expected, $results['query']['printrequests'] );
		}

		$this->assertArrayHasKey( 'error', $results );
	}

	public function sampleQueryProvider() {

		// #0 Standard query
		$provider[] = array(
			array(
				'[[Modification date::+]]',
				'?Modification date',
				'limit=10'
			),
			array(
				array(
					'label'=> '',
					'typeid' => '_wpg',
					'mode' => 2,
					'format' => false,
					'key' => '',
					'redi' => ''
				),
				array(
					'label'=> 'Modification date',
					'typeid' => '_dat',
					'mode' => 1,
					'format' => '',
					'key' => '_MDAT',
					'redi' => ''
				)
			)
		);

		$provider[] = array(
			array(
				'[[Modification date::+!]]',
				'limit=3'
			),
			array(
				array(
					'error'=> 'foo',
				)
			)
		);

		return $provider;
	}

}
