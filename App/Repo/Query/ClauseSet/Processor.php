<?php
/**
 * Apply set of clauses on query.
 *
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Flancer32\Base\App\Repo\Query\ClauseSet;

/**
 * Apply set of clauses on query.
 */
class Processor
{
    /** @var  \Flancer32\Base\App\Repo\Query\ClauseSet\Processor\FilterParser */
    private $ownFilterParser;

    public function __construct(
        \Flancer32\Base\App\Repo\Query\ClauseSet\Processor\FilterParser $ownFilterParser
    ) {
        $this->ownFilterParser = $ownFilterParser;
    }


    /**
     * @param \Magento\Framework\DB\Select $query
     * @param \Flancer32\Base\App\Repo\Data\ClauseSet $clauses
     * @param array $aliasMap
     * @param bool $filterOnly 'true' - apply only filter clauses (for totals)
     */
    public function exec(
        \Magento\Framework\DB\Select $query,
        \Flancer32\Base\App\Repo\Data\ClauseSet $clauses,
        $aliasMap = null,
        $filterOnly = false
    ) {
        $aliases = is_null($aliasMap) ? $this->mapAliases($query) : $aliasMap;
        $filter = $clauses->filter;
        $order = $clauses->order;
        $pagination = $clauses->pagination;
        $this->processFilter($query, $filter, $aliases);
        if (!$filterOnly) {
            $this->processOrder($query, $order, $aliases);
            $this->processPagination($query, $pagination);
        }
    }

    /**
     * @param \Magento\Framework\DB\Select $query
     * @return array
     */
    private function mapAliases(\Magento\Framework\DB\Select $query)
    {
        $result = [];
        try {
            $columns = $query->getPart(\Zend_Db_Select::COLUMNS);
            foreach ($columns as $one) {
                $table = $one[0];
                $expression = $one[1];
                $alias = $one[2];
                $data = new \Flancer32\Base\App\Repo\Query\ClauseSet\Processor\AliasMapEntry();
                $data->alias = $alias;
                $data->expression = $expression;
                $data->table = $table;
                $result[$alias] = $data;
            }
        } catch (\Zend_Db_Select_Exception $e) {
            // just stealth the exception
        }
        return $result;
    }

    private function processFilter($query, $filter, $aliases)
    {
        $where = $this->ownFilterParser->parse($filter, $aliases);
        if ($where) {
            $query->where($where);
        }
    }

    private function processOrder($query, $order, $aliases)
    {
        if ($order) {
            $entries = $order->entries;
            if (is_array($entries)) {
                $sqlOrder = [];
                /** @var \Flancer32\Lib\Repo\Data\ClauseSet\Order\Entry $entry */
                foreach ($entries as $entry) {
                    $alias = $entry->alias;
                    $dir = ($entry->desc) ? \Zend_Db_Select::SQL_DESC : \Zend_Db_Select::SQL_ASC;
                    $sqlOrder[] = "$alias $dir";
                }
                $query->order($sqlOrder);
            }
        }
    }

    private function processPagination($query, $pagination)
    {
        if ($pagination) {
            $offset = $pagination->offset;
            $limit = $pagination->limit;
            if ($limit && $offset) {
                $query->limit($limit, $offset);
            } elseif ($limit) {
                $query->limit($limit);
            }
        }
    }
}
