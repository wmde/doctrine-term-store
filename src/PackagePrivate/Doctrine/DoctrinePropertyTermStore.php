<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\PropertyTermStore;

class DoctrinePropertyTermStore implements PropertyTermStore {

	/* private */ const TYPE_LABEL = 1;

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		$label = $terms->getLabels()->getByLanguage( 'en' );

		$textId = $this->connection->insert(
			Tables::TEXT,
			[
				'text' => $label->getText(),
			]
		);

		$textInLangId = $this->connection->insert(
			Tables::TEXT_IN_LANGUAGE,
			[
				'language' => $label->getLanguageCode(),
				'text_id' => $textId,
			]
		);

		$termInLangId = $this->connection->insert(
			Tables::TERM_IN_LANGUAGE,
			[
				'type_id' => self::TYPE_LABEL,
				'text_in_lang_id ' => $textInLangId,
			]
		);

		$this->connection->insert(
			Tables::PROPERTY_TERMS,
			[
				'property_id' => $propertyId->getNumericId(),
				'term_in_lang_id' => $termInLangId,
			]
		);
	}

	public function deleteTerms( PropertyId $propertyId ) {

	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		$sql = <<<EOT
SELECT text, language, type_id FROM wbt_property_terms
INNER JOIN wbt_term_in_lang ON wbt_property_terms.term_in_lang_id = wbt_term_in_lang.id
INNER JOIN wbt_text_in_lang ON wbt_term_in_lang.text_in_lang_id = wbt_text_in_lang.id
INNER JOIN wbt_text ON wbt_text_in_lang.text_id = wbt_text.id
WHERE property_id = ?
EOT;

		$term = $this->connection->executeQuery(
			$sql,
			[
				$propertyId->getNumericId()
			]
		)->fetch( \PDO::FETCH_OBJ );

		$fingerprint = new Fingerprint();

		if ( $term ) {
			$fingerprint->setLabel( $term->language, $term->text );
		}

		return $fingerprint;
	}
}
