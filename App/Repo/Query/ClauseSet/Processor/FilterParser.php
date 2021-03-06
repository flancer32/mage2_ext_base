<?php
/**
 * Parser to convert UI filter data into 'where' clause for DB select.
 *
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Flancer32\Base\App\Repo\Query\ClauseSet\Processor;

use Flancer32\Base\App\Repo\Data\ClauseSet\Filter as AFilter;
use Flancer32\Base\App\Repo\Data\ClauseSet\Filter\Condition as AFilterCond;
use Flancer32\Base\App\Repo\Data\ClauseSet\Filter\Group as AFilterGroup;
use Magento\Rule\Model\Condition\Sql\Expression as AnExpression;

/**
 * Parser to convert \Flancer32\Base\App\Repo\Data\ClauseSet\Filter data into 'where' clause for
 * \Magento\Framework\DB\Select object.
 */
class FilterParser
{
    /** Allowed comparison operators. */
    const FN_EQ = 'eq';
    const FN_GT = 'gt';
    const FN_GTE = 'gte';
    const FN_IS_NOT_NULL = 'is_not_null';
    const FN_IS_NULL = 'is_null';
    const FN_LIKE = 'like';
    const FN_LT = 'lt';
    const FN_LTE = 'lte';
    const FN_NEQ = 'neq';

    /** Placeholders for parts of the condition. */
    const TMPL_ALIAS = '${ALIAS}';
    const TMPL_VALUE = '${VAL}';

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $conn;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        /* I need to get whole resource to use 'quote' function only. */
        $conn = $resource->getConnection();
        $this->conn = $conn;
    }

    /**
     * @param string $alias
     * @param [] $aliases @see \Flancer32\Base\App\Repo\Query\ClauseSet\Processor::mapAliases
     */
    private function mapAlias($alias, $aliases)
    {
        /** @var \Flancer32\Base\App\Repo\Query\ClauseSet\Processor\AliasMapEntry $data */
        $data = $aliases[$alias];
        $table = $data->table;
        $expression = $data->expression;
        if ($expression instanceof AnExpression) {
            $result = $expression;
        } else {
            /* $expression is a column's name */
            $result = "`$table`.`$expression`";
        }
        return $result;
    }

    public function parse(AFilter $filter, $aliases)
    {
        $result = '';
        /* Filter can be a single 'condition' or single 'group' */
        $clause = $filter->condition;
        $group = $filter->group;
        if ($clause) {
            /* there is single filtering condition in the filter */
            $alias = $clause->alias;
            $func = $clause->func;
            $value = $clause->value;
            /* compose clause */
            $mapped = $this->mapAlias($alias, $aliases);
            $quoted = $this->conn->quote($value);
            $template = $this->parseFunc($func);
            $template = str_replace(self::TMPL_ALIAS, $mapped, $template);
            $template = str_replace(self::TMPL_VALUE, $quoted, $template);
            $result = $template;
        } elseif ($group) {
            /* there is single group of the filtering conditions in the filter */
            $with = $group->with;
            $entries = $group->entries;
            /* collect group parts as SQL */
            $parts = [];
            foreach ($entries as $entry) {
                /* place data into 'filter' container */
                $inner = new AFilter();
                if ($entry instanceof AFilterCond) {
                    $inner->condition = $entry;
                } elseif ($entry instanceof AFilterGroup) {
                    $inner->group = $entry;
                }
                $sql = $this->parse($inner, $aliases);
                $parts[] = $sql;
            }
            /* concatenate group parts using $with */
            foreach ($parts as $part) {
                if ($part) $result .= "($part) $with ";
            }
            /* cut the last $with */
            $tail = substr($result, -1 * (strlen($with) + 2));
            if ($tail == " $with ") {
                $result = substr($result, 0, strlen($result) - strlen($tail));
            }
        }
        return $result;
    }

    /**
     * Convert function name into template with placeholders fro function arguments.
     *
     * @param string $func
     * @return string template with placeholders.
     */
    protected function parseFunc($func)
    {
        $result = '';
        switch (strtolower(trim($func))) {
            case 'eq':
                $result = self::TMPL_ALIAS . ' = ' . self::TMPL_VALUE;
                break;
            case 'neq':
                $result = self::TMPL_ALIAS . ' <> ' . self::TMPL_VALUE;
                break;
            case 'gt':
                $result = self::TMPL_ALIAS . ' > ' . self::TMPL_VALUE;
                break;
            case 'gte':
                $result = self::TMPL_ALIAS . ' >= ' . self::TMPL_VALUE;
                break;
            case 'lt':
                $result = self::TMPL_ALIAS . ' < ' . self::TMPL_VALUE;
                break;
            case 'lte':
                $result = self::TMPL_ALIAS . ' <= ' . self::TMPL_VALUE;
                break;
            case 'like':
                $result = self::TMPL_ALIAS . ' LIKE ' . self::TMPL_VALUE;
                break;
            case 'is_null':
                $result = self::TMPL_ALIAS . ' IS NULL';
                break;
            case 'is_not_null':
                $result = self::TMPL_ALIAS . ' IS NOT NULL';
                break;
        }
        return $result;
    }
}