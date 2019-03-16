<?php

namespace SMW\Property\Annotators;

use Html;
use ParserOutput;
use SMW\Message;
use SMW\PropertyAnnotator;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectedPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * Indicates whether the annotation was maintained by
	 * the system or not.
	 */
	const SYSTEM_ANNOTATION = 'editprotectedpropertyannotator.system.annotation';

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var boolean
	 */
	private $editProtectionRight = false;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param Title $title
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, Title $title ) {
		parent::__construct( $propertyAnnotator );
		$this->title = $title;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
	}

	/**
	 * @since 2.5
	 *
	 * @param ParserOutput
	 */
	public function addTopIndicatorTo( ParserOutput $parserOutput ) {

		if ( $this->editProtectionRight === false ) {
			return false;
		}

		// FIXME 3.0; Only MW 1.25+ (ParserOutput::setIndicator)
		if ( !method_exists( $parserOutput, 'setIndicator' ) ) {
			return false;
		}

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		if ( !$this->isEnabledProtection( $property ) && !$this->hasEditProtection() ) {
			return;
		}

		$html = Html::rawElement(
			'div',
			[
				'class' => 'smw-edit-protection',
				'title' => Message::get( 'smw-edit-protection-enabled', Message::TEXT, Message::USER_LANGUAGE )
			], ''
		);

		$parserOutput->setIndicator(
			'smw-protection-indicator',
			Html::rawElement( 'div', [ 'class' => 'smw-protection-indicator' ], $html )
		);
	}

	/**
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {

		if ( $this->editProtectionRight === false ) {
			return false;
		}

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		if ( $this->getSemanticData()->hasProperty( $property ) || !$this->hasEditProtection() ) {
			return;
		}

		// Notify preceding processes that this property is set as part of the
		// protection restriction detection in order to decide whether this
		// property was added manually or by the system
		$dataItem = $this->dataItemFactory->newDIBoolean( true );
		$dataItem->setOption( self::SYSTEM_ANNOTATION, true );

		// Since edit protection is active, add the property as indicator this is
		// especially to retain the status when purging a page
		$this->getSemanticData()->addPropertyObjectValue(
			$property,
			$dataItem
		);
	}

	private function hasEditProtection() {

		//$this->title->flushRestrictions();

		if ( !$this->title->isProtected( 'edit' ) ) {
			return false;
		}

		$restrictions = array_flip( $this->title->getRestrictions( 'edit' ) );

		// There could by any edit protections but the `Is edit protected` is
		// bound to the `smwgEditProtectionRight` setting
		return isset( $restrictions[$this->editProtectionRight] );
	}

	private function isEnabledProtection( $property ) {

		if ( !$this->getSemanticData()->hasProperty( $property ) ) {
			return false;
		}

		$semanticData = $this->getSemanticData();

		$dataItems = $semanticData->getPropertyValues( $property );
		$isEnabledProtection = false;

		// In case of two competing values, true always wins
		foreach ( $dataItems as $dataItem ) {

			$isEnabledProtection = $dataItem->getBoolean();

			if ( $isEnabledProtection ) {
				break;
			}
		}

		return $isEnabledProtection;
	}

}
