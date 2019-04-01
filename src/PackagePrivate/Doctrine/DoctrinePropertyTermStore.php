<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\TermStore\PropertyTermStore;

class DoctrinePropertyTermStore implements PropertyTermStore {

	public function storeTerms( Property $property ) {

	}

	public function deleteTerms( PropertyId $propertyId ) {

	}

}
