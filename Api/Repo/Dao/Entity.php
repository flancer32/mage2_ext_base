<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Flancer32\Base\Api\Repo\Dao;

/**
 * Base interface for entity's DAO.
 *
 * Descendants should override methods and define used data types.
 *
 * 'DataEntity' is a fake type to be used in this interface.
 */
interface Entity
{
    /**
     * All these constants should be defined in descendants.
     * The constants are used in the base implementation of this interface.
     * (@see \Flancer32\Base\App\Repo\Dao\Base)
     *
     * const ENTITY_CLASS = '\Vendor\Module\Api\Repo\Data\Entity'; // absolute classname for related Entity
     * const ENTITY_PK = ['key1', 'key2'];   // array with primary key attributes
     * const ENTITY_NAME = 'vnd_mod_entity'; // table name
     */

    /**
     * Create new entity.
     *
     * @param DataEntity $data
     * @return mixed
     */
    public function create($data);

    /**
     * Delete one entity using primary key (or entity itself - PK will be extracted).
     *
     * @param DataEntity|array|int|string $pk
     * @return int
     */
    public function deleteOne($pk);

    /**
     * Delete set of entities using $where condition.
     *
     * @param $where
     * @return mixed
     */
    public function deleteSet($where);

    /**
     * Get one entity using primary key.
     *
     * @param $pk
     * @return DataEntity|null
     */
    public function getOne($pk);

    /**
     * Get entities according to given conditions.
     *
     * @param string|array $where
     * @param array $bind
     * @param string|array $order
     * @param string $limit
     * @param string $offset
     * @return DataEntity[]
     */
    public function getSet(
        $where = null,
        $bind = null,
        $order = null,
        $limit = null,
        $offset = null
    );

    /**
     * Update one entity (primary key will be extracted from $data).
     *
     * @param DataEntity $data
     * @return int
     */
    public function updateOne($data);

    /**
     * Update entities according to given conditions.
     * Only whose $data attributes that are set directly will be processed.
     *
     * @param DataEntity|array $data
     * @param mixed $where
     * @return int
     */
    public function updateSet($data, $where);
}