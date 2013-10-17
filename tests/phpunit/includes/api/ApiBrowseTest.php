<?php

namespace SMW\Test;

use SMW\ApiBrowse;
use SMW\SemanticData;
use SMW\DIWikiPage;

/**
 * @covers \SMW\ApiBrowse
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
class ApiBrowseTest extends ApiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiBrowse';
	}

	/**
	 * @since 1.9
	 */
	private function newSemanticData( $text ) {
		return $data = new SemanticData( DIWikiPage::newFromTitle( $this->newTitle( NS_MAIN, $text ) ) );
	}

	/**
	 * @dataProvider subjectDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnSQLStore( $setup ) {

		$this->runOnlyOnSQLStore();

		$result = $this->doApiRequest( array(
			'action'  => 'browse',
			'subject' => $setup['subject']
		) );

		$this->assertStructuralIntegrity( $setup, $result );

	}

	/**
	 * @dataProvider invalidTitleDataProvider
	 *
	 * @since 1.9
	 */
	public function testInvalidTitleExecuteOnSQLStore( $setup ) {

		$this->runOnlyOnSQLStore();

		$this->setExpectedException( 'Exception' );

		$result = $this->doApiRequest( array(
			'action'  => 'browse',
			'subject' => $setup['subject']
		) );

		$this->assertStructuralIntegrity( $setup, $result );

	}

	/**
	 * The Serializer enforces a specific output format therefore expected
	 * elements are verified
	 *
	 * @since 1.9
	 */
	public function assertStructuralIntegrity( $type, $result ) {

		if ( isset( $type['hasError'] ) && $type['hasError'] ) {
			$this->assertInternalType( 'array', $result['error'] );
		}

		if ( isset( $type['hasResult'] ) && $type['hasResult'] ) {
			$this->assertInternalType( 'array', $result['query'] );
		}

		if ( isset( $type['hasSubject'] ) && $type['hasSubject'] ) {
			$this->assertInternalType( 'string', $result['query']['subject'] );
		}

		if ( isset( $type['hasData'] ) && $type['hasData'] ) {
			$this->assertInternalType( 'array', $result['query']['data'] );
		}

		if ( isset( $type['hasSobj'] ) && $type['hasSobj'] ) {
			$this->assertInternalType( 'array', $result['query']['sobj'] );
		}

	}

	/**
	 * @return array
	 */
	public function subjectDataProvider() {

		$provider = array();

		// #0 Valid
		$provider[] = array(
			array(
				'subject'    => 'Main_Page',
				'hasSubject' => true,
				'hasResult'  => true
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function invalidTitleDataProvider() {

		$provider = array();

		$provider[] = array(
			array(
				'subject'   => '{}',
				'hasError'  => true,
				'hasResult' => false
			)
		);

		return $provider;
	}

}
