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

	private $connection;
	private $tableNames;
	private $normalizedStore;

	public function __construct( Connection $connection, TableNames $tableNames ) {
		$this->connection = $connection;
		$this->tableNames = $tableNames;
		$this->normalizedStore = new NormalizedStore( $connection, $tableNames );
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		try {
			$this->deleteTerms( $itemId );
			$this->insertTerms( $itemId, $terms );
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function insertTerms( ItemId $itemId, Fingerprint $terms ) {
		foreach ( $terms->getLabels() as $term ) {
			$this->insertTerm( $itemId, $term, NormalizedStore::TYPE_LABEL );
		}

		foreach ( $terms->getDescriptions() as $term ) {
			$this->insertTerm( $itemId, $term, NormalizedStore::TYPE_DESCRIPTION );
		}

		foreach ( $terms->getAliasGroups() as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->insertTerm(
					$itemId,
					new Term( $aliasGroup->getLanguageCode(), $alias ),
					NormalizedStore::TYPE_ALIAS
				);
			}
		}
	}

	private function insertTerm( ItemId $itemId, Term $term, $termType ) {
		$this->connection->insert(
			$this->tableNames->itemTerms(),
			[
				'wbit_item_id' => $itemId->getNumericId(),
				'wbit_term_in_lang_id' => $this->normalizedStore->acquireTermInLanguageId( $term, $termType ),
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
			return $this->normalizedStore->getFingerprint(
				$this->newGetTermsStatement( $itemId )
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

}
