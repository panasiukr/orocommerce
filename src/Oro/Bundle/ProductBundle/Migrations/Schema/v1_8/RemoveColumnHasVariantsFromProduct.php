<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class RemoveColumnHasVariantsFromProduct implements Migration
{
    const PRODUCT_TABLE_NAME = 'oro_product';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->dropColumn('has_variants');

        $queries->addPostQuery(
            new RemoveEntityConfigEntityValueQuery('Oro\Bundle\ProductBundle\Entity\Product', 'hasVariants')
        );
    }
}