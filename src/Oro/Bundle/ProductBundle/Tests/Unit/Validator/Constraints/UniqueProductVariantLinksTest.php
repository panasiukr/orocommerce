<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Symfony\Component\Validator\Constraint;

class UniqueProductVariantLinksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueProductVariantLinks
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new UniqueProductVariantLinks();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->constraint);
    }

    public function testValidatedBy()
    {
        $this->assertEquals(UniqueProductVariantLinksValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
