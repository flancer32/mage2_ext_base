<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Flancer32\Base\App\Repo\Query;


interface Grid
{

    /**
     * Map SELECT aliases to "table.column" or "expression" to process UI clauses in .
     *
     * @return array
     */
    public function getAliasMap();

    /**
     * Base query to select total count for the grid.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getCountQuery();

    /**
     * Base query to select data for the grid.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectQuery();
}
