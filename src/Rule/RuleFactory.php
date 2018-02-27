<?php

namespace SMW\Rule;

use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Rule\Exception\RuleTypeNotFoundException;
use SMW\Rule\Exception\RuleDefinitionNotFoundException;
use SMW\Rule\Exception\RuleDefinitionClassNotFoundException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleFactory {

	/**
	 * @var []
	 */
	private $ruleTypes = [];

	/**
	 * @since 3.0
	 *
	 * @param array $ruleTypes
	 */
	public function __construct( array $ruleTypes = [] ) {
		$this->ruleTypes = $ruleTypes;

		if ( $this->ruleTypes === [] ) {
			$this->ruleTypes = $GLOBALS['smwgRuleTypes'];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isRegisteredType( $type ) {
		return isset( $this->ruleTypes[$type] );
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getRegisteredTypes() {
		return array_keys( $this->ruleTypes );
	}

	/**
	 * @since 3.0
	 *
	 * @param string|array $group
	 *
	 * @return []
	 */
	public function getRegisteredTypesByGroup( $group ) {

		$registeredTypes = [];
		$groups = (array)$group;

		foreach ( $this->ruleTypes as $type => $val ) {
			if ( isset( $val['group'] ) && in_array( $val['group'], $groups ) ) {
				$registeredTypes[] = $type;
			}
		}

		return $registeredTypes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param array|string $data
	 *
	 * @return RuleDefinition
	 * @throws RuntimeException
	 */
	public function newRuleDefinition( $name, $data ) {

		if ( is_string( $data ) ) {
			if ( ( $data = json_decode( $data, true ) ) === null || json_last_error() !== JSON_ERROR_NONE ) {
				throw new RuntimeException( "Invalid JSON format." );
			}
		}

		$type = null;
		$schema = null;

		if ( isset( $data['type'] ) ) {
			$type = $data['type'];
		}

		if ( !isset( $this->ruleTypes[$type] ) ) {
			throw new RuleTypeNotFoundException( "$type is an unrecognized rule type." );
		}

		if ( isset( $this->ruleTypes[$type]['schema'] ) ) {
			$schema = $this->ruleTypes[$type]['schema'];
		}

		return new RuleDefinition( $name, $data, $schema );
	}

	/**
	 * @since 3.0
	 *
	 * @return JsonSchemaValidator
	 */
	public function newJsonSchemaValidator() {
		return ApplicationFactory::getInstance()->create( 'JsonSchemaValidator' );
	}

}
