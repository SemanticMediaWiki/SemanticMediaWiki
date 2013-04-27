<?php

namespace SMW\Test;

use SMW\RecurringEvents;
use SMW\ParserParameterFormatter;

/**
 * Tests for the SMW\RecurringEvents class.
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMW\RecurringEvents class
 *
 * @ingroup SMW
 */
class RecurringEventsTest extends SemanticMediaWikiTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\RecurringEvents';
	}

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getParametersDataProvider() {
		return array(
			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |has title= Birthday
			// |unit=year
			// |period=12
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1970',
					'has title=Birthday',
					'unit=month',
					'period=12',
					'limit=3'
				),
				array(
					'errors' => 0,
					'dates' => array( '1 February 1970', '1 February 1971 00:00:00', '1 February 1972 00:00:00', '1 February 1973 00:00:00' ),
					'property' => 'Has birthday',
					'parameters' => array( 'has title' => array( 'Birthday' ) )
				)
			),

			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1972 02:00
			// |has title=Test 12
			// |unit=week
			// |period=4
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1972 02:00',
					'has title=Test 2',
					'unit=week',
					'period=4',
					'limit=3'
				),
				array(
					'errors' => 0,
					'dates' => array( '1 February 1972 02:00:00', '29 February 1972 02:00:00', '28 March 1972 02:00:00', '25 April 1972 02:00:00' ),
					'property' => 'Has birthday',
					'parameters' => array( 'has title' => array( 'Test 2' ) )
				)
			),

			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => 0,
					'dates' => array( '4 January 2010', '11 January 2010 00:00:00', '1 February 2010 00:00:00', 'March 16, 2010', 'March 23, 2010' ),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			array(
				array(
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;',
					'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				),
				array(
					'errors' => 0,
					'dates' => array( '4 January 2010', '11 January 2010 00:00:00', '1 February 2010 00:00:00', 'March 16, 2010', 'March 23, 2010' ),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// Simulate start date has wrong type

			// {{#set_recurring_event:property=Has date
			// |start=???
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=???',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => 1,
					'dates' => array(),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// Simulate missing start date

			// {{#set_recurring_event:property=Has date
			// |start=
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => 1,
					'dates' => array(),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// Simulate missing property

			// {{#set_recurring_event:property=
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			array(
				array(
					'property=',
					'start=January 4, 2010',
					'unit=week', 'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;',
					'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				),
				array(
					'errors' => 1,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

		);
	}

	/**
	 * Helper method that returns IParameterFormatter object
	 *
	 * @return  ParserParameterFormatter
	 */
	private function getParameters( array $params ) {
		$parameters = new ParserParameterFormatter( $params );
		return $parameters->toArray();
	}

	/**
	 * Helper method that returns Settings object
	 *
	 * @return Settings
	 */
	protected function getRecurringEventsSettings() {
		return $this->getSettings( array(
			'smwgDefaultNumRecurringEvents' => 10,
			'smwgMaxNumRecurringEvents' => 50
		) );
	}

	/**
	 * Helper method that returns an RecurringEvents object
	 *
	 * @return RecurringEvents
	 */
	private function getInstance( array $params ) {
		return new RecurringEvents(
			$this->getParameters( $params ),
			$this->getRecurringEventsSettings()
		);
	}

	/**
	 * Test RecurringEvents::__construct (parameters exceptions)
	 *
	 * @since  1.9
	 */
	public function testMissingParametersExceptions() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new RecurringEvents( '' , '' );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Test RecurringEvents::__construct (options exceptions)
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 */
	public function testMissingOptionsExceptions( array $params ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$parameters = $this->getParameters( $params );

		$instance = new RecurringEvents( $parameters, '' );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Test RecurringEvents::__construct
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 */
	public function testInstance( array $params ) {
		$instance = $this->getInstance( $params );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Test RecurringEvents::getErrors
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetErrors( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$this->assertCount( $expected['errors'], $instance->getErrors() );
	}

	/**
	 * Test RecurringEvents::getProperty
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetProperty( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$this->assertEquals( $expected['property'], $instance->getProperty() );
	}

	/**
	 * Test RecurringEvents::getParameters
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetParameters( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$this->assertEquals( $expected['parameters'], $instance->getParameters() );
	}

	/**
	 * Test RecurringEvents::getDates
	 *
	 * @since 1.9
	 *
	 * @dataProvider getParametersDataProvider
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetDates( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$this->assertEquals( $expected['dates'], $instance->getDates() );
	}

	/**
	 * DataProvider (Mass insert)
	 *
	 * @return array
	 */
	public function getMassInsertDataProvider() {
		return array(
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1970',
					'Has title=Birthday',
					'unit=month', 'period=12',
					'limit=500',
				),
				array(
					'errors' => 0,
					'count' => 501,
					'property' => '',
					'parameters' => array()
				)
			)
		);
	}

	/**
	 * Test RecurringEvents::getDates (mass insert)
	 *
	 * @since 1.9
	 *
	 * @dataProvider getMassInsertDataProvider
	 * @param array $params
	 * @param array $expected
	 */
	public function testMassInsert( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$this->assertCount( $expected['count'], $instance->getDates() );
	}
}
