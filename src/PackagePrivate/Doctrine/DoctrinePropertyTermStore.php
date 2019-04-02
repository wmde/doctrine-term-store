<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\PropertyTermStore;

class DoctrinePropertyTermStore implements PropertyTermStore {

	/* private */ const TABLE_PROPERTY_TERMS = 'wbt_property_terms';

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		$label = $terms->getLabels()->toTextArray();

		$this->connection->insert(
			self::TABLE_PROPERTY_TERMS,
			[
				'property_id' => $propertyId->getNumericId(),
				'term_in_lang_id' => 42,
			]
		);
	}

	public function deleteTerms( PropertyId $propertyId ) {

	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		return new Fingerprint();
	}
}
