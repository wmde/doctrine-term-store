<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;

class DoctrinePropertyTermStore implements PropertyTermStore {

	/* private */ const TYPE_LABEL = 1;
	/* private */ const TYPE_DESCRIPTION = 2;
	/* private */ const TYPE_ALIAS = 3;

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		try {
			// TODO: optimize by doing a select and a diff to see what to insert and what to delete
			$this->deleteTerms( $propertyId );
			$this->insertTerms( $propertyId, $terms );
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function insertTerms( PropertyId $propertyId, Fingerprint $terms ) {
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
		$this->connection->insert(
			Tables::PROPERTY_TERMS,
			[
				'property_id' => $propertyId->getNumericId(),
				'term_in_lang_id' => $this->acquireTermInLanguageId( $term, $termType ),
			]
		);
	}

	private function acquireTermInLanguageId( Term $term, $termType ) {
		$textInLanguageId = $this->acquireTextInLanguageId( $term );

		$id = $this->findExistingTermInLanguageId( $termType, $textInLanguageId );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTermInLanguageRecord( $termType, $textInLanguageId );

		return $this->connection->lastInsertId();
	}

	private function findExistingTermInLanguageId( $termType, $textInLanguageId ) {
		$record = $this->connection->executeQuery(
			'SELECT id FROM wbt_term_in_lang WHERE type_id = ? AND text_in_lang_id = ?',
			[ $termType, $textInLanguageId ],
			[ \PDO::PARAM_INT, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['id'] : false;
	}

	private function insertTermInLanguageRecord( $termType, $textInLanguageId ) {
		$this->connection->insert(
			Tables::TERM_IN_LANGUAGE,
			[
				'type_id' => $termType,
				'text_in_lang_id ' => $textInLanguageId,
			]
		);
	}

	private function acquireTextInLanguageId( Term $term ) {
		$textId = $this->acquireTextId( $term );

		$id = $this->findExistingTextInLanguageId( $term, $textId );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTextInLanguageRecord( $term, $textId );

		return $this->connection->lastInsertId();
	}

	private function findExistingTextInLanguageId( Term $term, $textId ) {
		$record = $this->connection->executeQuery(
			'SELECT id FROM wbt_text_in_lang WHERE language = ? AND text_id = ?',
			[ $term->getLanguageCode(), $textId ],
			[ \PDO::PARAM_STR, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['id'] : false;
	}

	private function insertTextInLanguageRecord( Term $term, $textId ) {
		$this->connection->insert(
			Tables::TEXT_IN_LANGUAGE,
			[
				'language' => $term->getLanguageCode(),
				'text_id' => $textId,
			]
		);
	}

	private function acquireTextId( Term $term ) {
		$id = $this->findExistingTextId( $term );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTextRecord( $term );

		return $this->connection->lastInsertId();
	}

	private function findExistingTextId( Term $term ) {
		$record = $this->connection->executeQuery(
			'SELECT id FROM wbt_text WHERE text = ?',
			[ $term->getText() ],
			[ \PDO::PARAM_STR ]
		)->fetch();

		return is_array( $record ) ? $record['id'] : false;
	}

	private function insertTextRecord( Term $term ) {
		$this->connection->insert(
			Tables::TEXT,
			[
				'text' => $term->getText(),
			]
		);
	}

	public function deleteTerms( PropertyId $propertyId ) {
		try {
			$this->connection->delete(
				Tables::PROPERTY_TERMS,
				[ 'property_id' => $propertyId->getNumericId() ],
				[ \PDO::PARAM_INT ]
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		try {
			return $this->recordsToFingerprint(
				$this->newGetTermsStatement( $propertyId )->fetchAll( \PDO::FETCH_OBJ )
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function newGetTermsStatement( PropertyId $propertyId ): Statement {
		$sql = <<<EOT
SELECT text, language, type_id FROM wbt_property_terms
INNER JOIN wbt_term_in_lang ON wbt_property_terms.term_in_lang_id = wbt_term_in_lang.id
INNER JOIN wbt_text_in_lang ON wbt_term_in_lang.text_in_lang_id = wbt_text_in_lang.id
INNER JOIN wbt_text ON wbt_text_in_lang.text_id = wbt_text.id
WHERE property_id = ?
EOT;

		return $this->connection->executeQuery(
			$sql,
			[
				$propertyId->getNumericId()
			],
			[
				\PDO::PARAM_INT
			]
		);
	}

	private function recordsToFingerprint( array $termRecords ): Fingerprint {
		$fingerprint = new Fingerprint();

		$aliasGroups = [];

		foreach ( $termRecords as $term ) {
			switch ( $term->type_id ) {
				case self::TYPE_LABEL:
					$fingerprint->setLabel( $term->language, $term->text );
					break;
				case self::TYPE_DESCRIPTION:
					$fingerprint->setDescription( $term->language, $term->text );
					break;
				case self::TYPE_ALIAS:
					if ( !array_key_exists( $term->language, $aliasGroups ) ) {
						$aliasGroups[$term->language] = [];
					}

					$aliasGroups[$term->language][] = $term->text;

					break;
			}
		}

		foreach ( $aliasGroups as $language => $aliases ) {
			$fingerprint->setAliasGroup( $language, $aliases );
		}

		return $fingerprint;
	}

}
