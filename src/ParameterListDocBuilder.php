<?php

namespace SMW;

use ParamProcessor\ParamDefinition;

/**
 * @since 2.4
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ParameterListDocBuilder {

	/**
	 * @var callable
	 */
	private $msg;

	/**
	 * @param callable $messageFunction
	 */
	public function __construct( $messageFunction ) {
		$this->msg = $messageFunction;
	}

	/**
	 * Returns the wikitext for a table listing the provided parameters.
	 *
	 * @param ParamDefinition[] $paramDefinitions
	 *
	 * @return string
	 */
	public function getParameterTable( array $paramDefinitions ) {
		$tableRows = array();
		$hasAliases = false;

		foreach ( $paramDefinitions as $parameter ) {
			$hasAliases = count( $parameter->getAliases() ) != 0;
			if ( $hasAliases ) {
				break;
			}
		}

		foreach ( $paramDefinitions as $parameter ) {
			if ( $parameter->getName() != 'format' ) {
				$tableRows[] = $this->getDescriptionRow( $parameter, $hasAliases );
			}
		}

		$table = '';

		if ( count( $tableRows ) > 0 ) {
			$tableRows = array_merge( array(
				'!' . $this->msg( 'validator-describe-header-parameter' ) ."\n" .
				( $hasAliases ? '!' . $this->msg( 'validator-describe-header-aliases' ) ."\n" : '' ) .
				'!' . $this->msg( 'validator-describe-header-type' ) ."\n" .
				'!' . $this->msg( 'validator-describe-header-default' ) ."\n" .
				'!' . $this->msg( 'validator-describe-header-description' )
			), $tableRows );

			$table = implode( "\n|-\n", $tableRows );

			$table =
				'{| class="wikitable sortable"' . "\n" .
				$table .
				"\n|}";
		}

		return $table;
	}

	/**
	 * Returns the wikitext for a table row describing a single parameter.
	 *
	 * @param ParamDefinition $parameter
	 * @param boolean $hasAliases
	 *
	 * @return string
	 */
	private function getDescriptionRow( ParamDefinition $parameter, $hasAliases ) {
		if ( $hasAliases ) {
			$aliases = $parameter->getAliases();
			$aliases = count( $aliases ) > 0 ? implode( ', ', $aliases ) : '-';
		}

		$description = $this->msg( $parameter->getMessage() );

		$type = $this->msg( $parameter->getTypeMessage() );

		$default = $parameter->isRequired() ? "''" . $this->msg( 'validator-describe-required' ) . "''" : $parameter->getDefault();
		if ( is_array( $default ) ) {
			$default = implode( ', ', $default );
		}
		elseif ( is_bool( $default ) ) {
			$default = $default ? 'yes' : 'no';
		}

		if ( $default === '' ) {
			$default = "''" . $this->msg( 'validator-describe-empty' ) . "''";
		}

		return "|{$parameter->getName()}\n"
		. ( $hasAliases ? '|' . $aliases . "\n" : '' ) .
		<<<EOT
|{$type}
|{$default}
|{$description}
EOT;
	}

	private function msg() {
		return call_user_func_array( $this->msg, func_get_args() );
	}

}
