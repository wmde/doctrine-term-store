<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\ProductionClass;

/**
 * @covers \Wikibase\TermStore\ProductionClass
 */
class ProductionClassTest extends TestCase {

	public function testGetTrue() {
		$this->assertTrue( ProductionClass::getTrue() );
	}

}
