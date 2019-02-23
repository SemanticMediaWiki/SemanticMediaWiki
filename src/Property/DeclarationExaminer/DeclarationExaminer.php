<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
abstract class DeclarationExaminer implements IDeclarationExaminer {

	/**
	 * @var DeclarationExaminer
	 */
	protected $declarationExaminer;

	/**
	 * @var []
	 */
	protected $messages = [];

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 */
	public function __construct( DeclarationExaminer $declarationExaminer ) {
		$this->declarationExaminer = $declarationExaminer;
	}

	/**
	 * @see DeclarationExaminer::getSemanticData
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData() {
		return $this->declarationExaminer->getSemanticData();
	}

	/**
	 * @see DeclarationExaminer::getMessages
	 *
	 * {@inheritDoc}
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getMessagesAsString() {
		return json_encode( $this->messages );
	}

	/**
	 * @see DeclarationExaminer::isLocked
	 *
	 * {@inheritDoc}
	 */
	public function isLocked() {
		return $this->declarationExaminer->isLocked();
	}

	/**
	 * @see PropertyAnnotator::check
	 *
	 * {@inheritDoc}
	 */
	public function check( DIProperty $property ) {

		$this->declarationExaminer->check( $property );
		$this->messages = array_merge( $this->messages, $this->declarationExaminer->getMessages() );
		$this->validate( $property );
	}

	/**
	 * @since 3.1
	 */
	protected abstract function validate( DIProperty $property );

}
