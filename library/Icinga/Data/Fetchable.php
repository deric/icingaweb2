<?php

namespace Icinga\Data;

interface Fetchable
{
    /**
     * Retrieve an array containing all rows of the result set
     *
     * @param   BaseQuery $query
     *
     * @return  array
     */
    public function fetchAll(BaseQuery $query);

    /**
     * Fetch the first row of the result set
     *
     * @param   BaseQuery $query
     *
     * @return  mixed
     */
    public function fetchRow(BaseQuery $query);

    /**
     * Fetch a column of all rows of the result set as an array
     *
     * @param   BaseQuery   $query
     * @param   int         $columnIndex Index of the column to fetch
     *
     * @return  array
     */
    public function fetchColumn(BaseQuery $query, $columnIndex = 0);

    /**
     * Fetch the first column of the first row of the result set
     *
     * @param   BaseQuery $query
     *
     * @return  string
     */
    public function fetchOne(BaseQuery $query);

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @param   BaseQuery $query
     *
     * @return  array
     */
    public function fetchPairs(BaseQuery $query);
}
