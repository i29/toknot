<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Db\Driver;

class SQLite {
    public function __construct($dsn, $username, $password, $driverOption = array(0)) {
        list($prefix, $file) = explode(':', $dsn, 2);
    }
}

?>
