<?php

namespace SMW\Test;

/**
 * @covers \SMW\Api\Ask
 * @covers \SMW\Api\Base
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AskTest extends ApiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Api\Ask';
	}

	/**
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array(
				// #0 Standard query
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
						'format' => false
					),
					array(
						'label'=> 'Modification date',
						'typeid' => '_dat',
						'mode' => 1,
						'format' => ''
					)
				)
			),

			// #1 Query that produces an error
			// Don't mind the error content as it depends on the language
			array(
				array(
					'[[Modification date::+!]]',
					'limit=3'
				),
				array(
					array(
						'error'=> 'foo',
					)
				)
			)
		);
	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecute( array $query, array $expected ) {

		$results = $this->doApiRequest( array(
				'action' => 'ask',
				'query' => implode( '|', $query )
		) );

		$this->assertInternalType( 'array', $results );

		// If their is no printrequests array we expect an error array
		if ( isset( $results['query']['printrequests'] ) ) {
			$this->assertEquals( $expected, $results['query']['printrequests'] );
		} else {
			$this->assertArrayHasKey( 'error', $results );
		}

	}
}
