<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\PropertyTermStore;

class DoctrinePropertyTermStore implements PropertyTermStore {

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {

	}

	public function deleteTerms( PropertyId $propertyId ) {

	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {

	}
}
