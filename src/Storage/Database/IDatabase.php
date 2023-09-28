<?php

namespace Equalify\Storage\Database;

/**
 * @author Chris Kelly (TolstoyDotCom)
 */
interface IDatabase {

    /**
     */
    public function getRows($table, array $filters = [], $page = 1, $rows_per_page = '', $order_by = '') : array;

}
