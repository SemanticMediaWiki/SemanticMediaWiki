<?php

namespace SMW\Test;

use SMW\ParserParameterFormatter;
use SMW\RecurringEvents;

/**
 * @covers \SMW\RecurringEvents
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RecurringEventsTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RecurringEvents';
	}

	/**
	 * @since  1.9
	 *
	 * @return RecurringEvents
	 */
	private function newInstance( array $params ) {

		$parameters = new ParserParameterFormatter( $params );

		$settings = $this->newSettings( array(
			'smwgDefaultNumRecurringEvents' => 10,
			'smwgMaxNumRecurringEvents' => 50
		) );

		return new RecurringEvents( $parameters->toArray(), $settings );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testConstructor( array $params ) {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance( $params ) );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetErrors( array $params, array $expected ) {
		$this->assertCount( $expected['errors'], $this->newInstance( $params )->getErrors() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetProperty( array $params, array $expected ) {
		$this->assertEquals( $expected['property'], $this->newInstance( $params )->getProperty() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetParameters( array $params, array $expected ) {
		$this->assertEquals( $expected['parameters'], $this->newInstance( $params )->getParameters() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetDates( array $params, array $expected ) {
		$this->assertEquals( $expected['dates'], $this->newInstance( $params )->getDates() );
	}

	/**
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
	 * @dataProvider getMassInsertDataProvider
	 *
	 * @since 1.
	 */
	public function testMassInsert( array $params, array $expected ) {
		$this->assertCount( $expected['count'], $this->newInstance( $params )->getDates() );
	}

	/**
	 * @test RecurringEvents::getJulianDay
	 *
	 * @since 1.9
	 */
	public function testGetJulianDay() {
		$instance = $this->newInstance( array() );

		// SMWDIWikiPage stub object
		$dataValue = $this->getMockBuilder( 'SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( null ) );

		$this->assertEquals( null, $instance->getJulianDay( $dataValue ) );
	}

	/**
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
			// |start=01 Feb 1970
			// |end=01 Feb 1972
			// |has title= Birthday
			// |unit=year
			// |period=12
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1970',
					'end=01 Feb 1972',
					'has title=Birthday',
					'unit=month',
					'period=12',
					'limit=3'
				),
				array(
					'errors' => 0,
					'dates' => array( '1 February 1970', '1 February 1971 00:00:00', '1 February 1972 00:00:00' ),
					'property' => 'Has birthday',
					'parameters' => array( 'has title' => array( 'Birthday' ) )
				)
			),

			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |end=01 Feb 1972
			// |has title= Birthday
			// |unit=year
			// |week number=2
			// |period=12
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1970',
					'end=01 Feb 1972',
					'has title=Birthday',
					'unit=month',
					'week number=2',
					'period=12',
					'limit=3'
				),
				array(
					'errors' => 0,
					'dates' => array( '1 February 1970', '14 February 1971 00:00:00' ),
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
}
