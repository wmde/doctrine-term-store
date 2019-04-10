<?php

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\TermStoreException;

class DoctrineItemTermStore implements ItemTermStore {

	/* private */ const TYPE_LABEL = 1;
	/* private */ const TYPE_DESCRIPTION = 2;
	/* private */ const TYPE_ALIAS = 3;

	private $connection;
	private $tableNames;

	public function __construct( Connection $connection, TableNames $tableNames ) {
		$this->connection = $connection;
		$this->tableNames = $tableNames;
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		try {
			// TODO: optimize by doing a select and a diff to see what to insert and what to delete
			$this->deleteTerms( $itemId );
			$this->insertTerms( $itemId, $terms );
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function insertTerms( ItemId $itemId, Fingerprint $terms ) {
		foreach ( $terms->getLabels() as $term ) {
			$this->insertTerm( $itemId, $term, self::TYPE_LABEL );
		}

		foreach ( $terms->getDescriptions() as $term ) {
			$this->insertTerm( $itemId, $term, self::TYPE_DESCRIPTION );
		}

		foreach ( $terms->getAliasGroups() as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->insertTerm(
					$itemId,
					new Term( $aliasGroup->getLanguageCode(), $alias ),
					self::TYPE_ALIAS
				);
			}
		}
	}

	private function insertTerm( ItemId $itemId, Term $term, $termType ) {
		$this->connection->insert(
			$this->tableNames->itemTerms(),
			[
				'wbit_item_id' => $itemId->getNumericId(),
				'wbit_term_in_lang_id' => $this->acquireTermInLanguageId( $term, $termType ),
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
			'SELECT wbtl_id FROM ' . $this->tableNames->termInLanguage() . ' WHERE wbtl_type_id = ? AND wbtl_text_in_lang_id = ?',
			[ $termType, $textInLanguageId ],
			[ \PDO::PARAM_INT, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['wbtl_id'] : false;
	}

	private function insertTermInLanguageRecord( $termType, $textInLanguageId ) {
		$this->connection->insert(
			$this->tableNames->termInLanguage(),
			[
				'wbtl_type_id' => $termType,
				'wbtl_text_in_lang_id ' => $textInLanguageId,
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
			'SELECT wbxl_id FROM ' . $this->tableNames->textInLanguage() . ' WHERE wbxl_language = ? AND wbxl_text_id = ?',
			[ $term->getLanguageCode(), $textId ],
			[ \PDO::PARAM_STR, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['wbxl_id'] : false;
	}

	private function insertTextInLanguageRecord( Term $term, $textId ) {
		$this->connection->insert(
			$this->tableNames->textInLanguage(),
			[
				'wbxl_language' => $term->getLanguageCode(),
				'wbxl_text_id' => $textId,
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
			'SELECT wbx_id FROM ' . $this->tableNames->text() . ' WHERE wbx_text = ?',
			[ $term->getText() ],
			[ \PDO::PARAM_STR ]
		)->fetch();

		return is_array( $record ) ? $record['wbx_id'] : false;
	}

	private function insertTextRecord( Term $term ) {
		$this->connection->insert(
			$this->tableNames->text(),
			[
				'wbx_text' => $term->getText(),
			]
		);
	}

	public function deleteTerms( ItemId $itemId ) {
		try {
			$this->connection->delete(
				$this->tableNames->itemTerms(),
				[ 'wbit_item_id' => $itemId->getNumericId() ],
				[ \PDO::PARAM_INT ]
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		try {
			return $this->recordsToFingerprint(
				$this->newGetTermsStatement( $itemId )->fetchAll( \PDO::FETCH_OBJ )
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function newGetTermsStatement( ItemId $itemId ): Statement {
		$sql = <<<EOT
SELECT wbx_text, wbxl_language, wbtl_type_id FROM {$this->tableNames->itemTerms()}
INNER JOIN {$this->tableNames->termInLanguage()} ON {$this->tableNames->itemTerms()}.wbit_term_in_lang_id = {$this->tableNames->termInLanguage()}.wbtl_id
INNER JOIN {$this->tableNames->textInLanguage()} ON {$this->tableNames->termInLanguage()}.wbtl_text_in_lang_id = {$this->tableNames->textInLanguage()}.wbxl_id
INNER JOIN {$this->tableNames->text()} ON {$this->tableNames->textInLanguage()}.wbxl_text_id = {$this->tableNames->text()}.wbx_id
WHERE wbit_item_id = ?
EOT;

		return $this->connection->executeQuery(
			$sql,
			[
				$itemId->getNumericId()
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
			switch ( $term->wbtl_type_id ) {
				case self::TYPE_LABEL:
					$fingerprint->setLabel( $term->wbxl_language, $term->wbx_text );
					break;
				case self::TYPE_DESCRIPTION:
					$fingerprint->setDescription( $term->wbxl_language, $term->wbx_text );
					break;
				case self::TYPE_ALIAS:
					if ( !array_key_exists( $term->wbxl_language, $aliasGroups ) ) {
						$aliasGroups[$term->wbxl_language] = [];
					}

					$aliasGroups[$term->wbxl_language][] = $term->wbx_text;

					break;
			}
		}

		foreach ( $aliasGroups as $language => $aliases ) {
			$fingerprint->setAliasGroup( $language, $aliases );
		}

		return $fingerprint;
	}

}
