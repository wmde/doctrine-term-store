<?php

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

	private $connection;
	private $tableNames;
	private $normalizedStore;

	public function __construct( Connection $connection, TableNames $tableNames ) {
		$this->connection = $connection;
		$this->tableNames = $tableNames;
		$this->normalizedStore = new NormalizedStore( $connection, $tableNames );
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		try {
			$this->deleteTerms( $propertyId );
			$this->insertTerms( $propertyId, $terms );
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function insertTerms( PropertyId $propertyId, Fingerprint $terms ) {
		foreach ( $terms->getLabels() as $term ) {
			$this->insertTerm( $propertyId, $term, NormalizedStore::TYPE_LABEL );
		}

		foreach ( $terms->getDescriptions() as $term ) {
			$this->insertTerm( $propertyId, $term, NormalizedStore::TYPE_DESCRIPTION );
		}

		foreach ( $terms->getAliasGroups() as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->insertTerm(
					$propertyId,
					new Term( $aliasGroup->getLanguageCode(), $alias ),
					NormalizedStore::TYPE_ALIAS
				);
			}
		}
	}

	private function insertTerm( PropertyId $propertyId, Term $term, $termType ) {
		$this->connection->insert(
			$this->tableNames->propertyTerms(),
			[
				'wbpt_property_id' => $propertyId->getNumericId(),
				'wbpt_term_in_lang_id' => $this->normalizedStore->acquireTermInLanguageId( $term, $termType ),
			]
		);
	}

	public function deleteTerms( PropertyId $propertyId ) {
		try {
			$this->connection->delete(
				$this->tableNames->propertyTerms(),
				[ 'wbpt_property_id' => $propertyId->getNumericId() ],
				[ \PDO::PARAM_INT ]
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		try {
			return $this->normalizedStore->getFingerprint(
				$this->newGetTermsStatement( $propertyId )
			);
		}
		catch ( DBALException $ex ) {
			throw new TermStoreException( $ex->getMessage(), $ex->getCode() );
		}
	}

	private function newGetTermsStatement( PropertyId $propertyId ): Statement {
		$sql = <<<EOT
SELECT wbx_text, wbxl_language, wbtl_type_id FROM {$this->tableNames->propertyTerms()}
INNER JOIN {$this->tableNames->termInLanguage()} ON {$this->tableNames->propertyTerms()}.wbpt_term_in_lang_id = {$this->tableNames->termInLanguage()}.wbtl_id
INNER JOIN {$this->tableNames->textInLanguage()} ON {$this->tableNames->termInLanguage()}.wbtl_text_in_lang_id = {$this->tableNames->textInLanguage()}.wbxl_id
INNER JOIN {$this->tableNames->text()} ON {$this->tableNames->textInLanguage()}.wbxl_text_id = {$this->tableNames->text()}.wbx_id
WHERE wbpt_property_id = ?
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

}
