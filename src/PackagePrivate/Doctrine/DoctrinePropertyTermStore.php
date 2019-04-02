<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\TermStore\PropertyTermStore;

class DoctrinePropertyTermStore implements PropertyTermStore {

	/* private */ const TYPE_LABEL = 1;
	/* private */ const TYPE_DESCRIPTION = 2;
	/* private */ const TYPE_ALIAS = 3;

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		foreach ( $terms->getLabels() as $term ) {
			$this->insertTerm( $propertyId, $term, self::TYPE_LABEL );
		}

		foreach ( $terms->getDescriptions() as $term ) {
			$this->insertTerm( $propertyId, $term, self::TYPE_DESCRIPTION );
		}

		foreach ( $terms->getAliasGroups() as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->insertTerm(
					$propertyId,
					new Term( $aliasGroup->getLanguageCode(), $alias ),
					self::TYPE_ALIAS
				);
			}
		}
	}

	private function insertTerm( PropertyId $propertyId, Term $term, $termType ) {
		$textId = $this->connection->insert(
			Tables::TEXT,
			[
				'text' => $term->getText(),
			]
		);

		$textInLangId = $this->connection->insert(
			Tables::TEXT_IN_LANGUAGE,
			[
				'language' => $term->getLanguageCode(),
				'text_id' => $textId,
			]
		);

		$termInLangId = $this->connection->insert(
			Tables::TERM_IN_LANGUAGE,
			[
				'type_id' => $termType,
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
EOT;

		$term = $this->connection->executeQuery(
			$sql,
			[
			]
		)->fetch( \PDO::FETCH_OBJ );

		$fingerprint = new Fingerprint();

		if ( $term ) {
			switch ( $term->type_id ) {
				case self::TYPE_LABEL:
					$fingerprint->setLabel( $term->language, $term->text );
					break;
				case self::TYPE_DESCRIPTION:
					$fingerprint->setDescription( $term->language, $term->text );
					break;
				case self::TYPE_ALIAS:
					$fingerprint->setAliasGroup( $term->language, [ $term->text ] );
					break;
			}
		}

		return $fingerprint;
	}
}
