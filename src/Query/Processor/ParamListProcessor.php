<?php

namespace SMW\Query\Processor;

use SMW\Query\PrintRequestFactory;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParamListProcessor {

	/**
	 * Format type
	 */
	const FORMAT_LEGACY = 'format.legacy';

	/**
	 * Identify the PrintThis instance
	 */
	const PRINT_THIS = 'print.this';

	/**
	 * @var PrintRequestFactory
	 */
	private $printRequestFactory;

	/**
	 * @since 3.0
	 *
	 * @param PrintRequestFactory|null $printRequestFactory
	 */
	public function __construct( PrintRequestFactory $printRequestFactory = null ) {
		$this->printRequestFactory = $printRequestFactory;

		if ( $this->printRequestFactory === null ) {
			$this->printRequestFactory = new PrintRequestFactory();
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param array $paramList
	 * @param string $type
	 *
	 * @return array
	 */
	public function format( array $paramList, $type ) {

		if ( $type === self::FORMAT_LEGACY ) {
			return $this->legacy_format( $paramList );
		}

		return $paramList;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 * @param boolean $showMode
	 *
	 * @return array
	 */
	public function preprocess( array $parameters, $showMode = false ) {

		$previousPrintout = null;

		$serialization = [
			'showMode'   => $showMode,
			'templateArgs' => false,
			'query'      => '',
			'this'       => [],
			'printouts'  => [],
			'parameters' => []
		];

		foreach ( $parameters as $name => $param ) {

			// special handling for arrays - this can happen if the
			// parameter came from a checkboxes input in Special:Ask:
			if ( is_array( $param ) ) {
				$param = implode( ',', array_keys( $param ) );
			}

			$param = $this->encodeEq( $param );

			// #1258 (named_args -> named args)
			// accept 'name' => 'value' just as '' => 'name=value':
			if ( is_string( $name ) && ( $name !== '' ) ) {
				$param = str_replace( "_", " ", $name ) . '=' . $param;
			}

			// Find out whether this is a mainlabel, and if so store related
			// parameters separate since QueryProcessor::addThisPrintout is
			// added in isolation !!??!!
			// $isMainlabel = strpos( $param, 'mainlabel=' ) !== false;

			// mainlable=Foo |+with=200 ... is currently not support
			// use
			// |?=Foo |+width=200 ...
			// |mainlabel=-
			$isMainlabel = false;

			if ( $param === '' ) {
			} elseif ( $isMainlabel ) {
				$this->addThisPrintRequest( $name, $param, $previousPrintout, $serialization );
			} elseif ( $param[0] == '?' ) {
				$this->addPrintRequest( $name, $param, $previousPrintout, $serialization );
			} elseif ( $param[0] == '+' ) {
				$this->addPrintRequestParameter( $name, $param, $previousPrintout, $serialization );
			} else {
				$this->addOtherParameters( $name, $param, $serialization, $showMode );
			}
		}

		$serialization['query'] = str_replace(
			[ '&lt;', '&gt;', '-3D' ],
			['<', '>', '=' ],
			$serialization['query']
		);

		if ( $showMode ) {
			$serialization['query'] = '[[:' . $serialization['query'] . ']]';
		}

		return $serialization;
	}

	private function legacy_format( array $paramList ) {

		$printouts = [];

		foreach ( $paramList['printouts'] as $k => $request ) {

			if ( !isset( $request['label'] ) ) {
				continue;
			}

			// #502
			// In case of template arguments suppress the showMode to allow for
			// labels to be generated and to be transfered to the invoked template
			// otherwise labels will be empty and not be accessible in a template
			$showMode = $paramList['templateArgs'] ? false : $paramList['showMode'];

			$printRequest = $this->printRequestFactory->newFromText(
				$request['label'],
				$showMode
			);

			if ( $printRequest === null ) {
				continue;
			}

			foreach ( $request['params'] as $key => $value ) {
				$printRequest->setParameter( $key, $value );
			}

			$printouts[] = $printRequest;
		}

		return [
			$paramList['query'],
			$paramList['parameters'],
			$printouts
		];
	}

	private function encodeEq ( $param ) {
		// Bug 32955 / #640
		// Modify (e.g. replace `=`) a condition string only if enclosed by [[ ... ]]
		return preg_replace_callback(
			'/\[\[([^\[\]]*)\]\]/xu',
			function( array $matches ) {
				return str_replace( [ '=' ], [ '-3D' ], $matches[0] );
			},
			$param
		);
	}

	private function addPrintRequest( $name, $param, &$previousPrintout, array &$serialization ) {

		$param = substr( $param, 1 );

		// Currently we don't filter any duplicates hence the additional
		// $name is added to distinguish printouts with the same configuration
		$hash = md5( json_encode( $param ) . $name );
		$previousPrintout = $hash;

		$serialization['printouts'][$hash] = [
			'label' => $param,
			'params'  => []
		];
	}

	private function addThisPrintRequest( $name, $param, &$previousPrintout, array &$serialization ) {

		$param = substr( $param, 1 );

		$parts = explode( '=', $param, 2 );
		$serialization['parameters']['mainlabel'] = count( $parts ) >= 2 ? $parts[1] : null;
		$previousPrintout = self::PRINT_THIS;
	}

	private function addPrintRequestParameter( $name, $param, $previousPrintout, array &$serialization ) {

		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		$parts = explode( '=', $param, 2 );

		if ( $previousPrintout === self::PRINT_THIS ) {
			if ( count( $parts ) == 2 ) {
				$serialization['this'] = [ trim( $parts[0] ) => $parts[1] ];
			} else {
				$serialization['this'] = [ trim( $parts[0] ) => null ];
			}
		} else {
			if ( count( $parts ) == 2 ) {
				$serialization['printouts'][$previousPrintout]['params'][trim( $parts[0] )] = $parts[1];
			} else {
				$serialization['printouts'][$previousPrintout]['params'][trim( $parts[0] )] = null;
			}
		}
	}

	private function addOtherParameters( $name, $param, array &$serialization, $showMode ) {

		// #1645
		$parts = $showMode && $name == 0 ? $param : explode( '=', $param, 2 );

		if ( is_array( $parts ) && count( $parts ) >= 2 ) {
			$p = strtolower( trim( $parts[0] ) );

			if ( $p === 'template' ) {
				$serialization['templateArgs'] = true;
			}

			// Don't trim here, some parameters care for " "
			//
			// #3196
			// Ensure to decode `-3D` from encodeEq to support things like
			// `|intro=[[File:Foo.png|link=Bar]]`
			$serialization['parameters'][$p] = str_replace( [ '-3D' ], [ '=' ], $parts[1] );
		} else {
			$serialization['query'] .= $param;
		}
	}

}
