<?php
exists_frame();
final class XDba extends XMySQLDba {
}
class XMySQLDba {
    private $read_con = null;
    private $con = null;
    private $res;
    private $api_res = false;
    private $dba_table;
    private $select_api = false;
    private $api_fetch = false;
    public $sql = null;
    private $cfg;
    public function __construct() {
        global $_CFG;
        $this->cfg = $_CFG->db;
        $this->connect();
        $this->dba_table = new XMySQLTable($this);
    }
    private function connect() {
        if($this->cfg->select_api) $this->select_api = $this->cfg->select_api;
        $con =@mysql_connect($this->cfg->host, $this->cfg->user, $this->cfg->password);
        if($con === false) throw new XException('MySQL connect Error:#'.mysql_errno().'-'.mysql_error());
        $sr = @mysql_select_db($this->cfg->name, $con);
        if($sr === false) throw new XException('MySQL select DB error:#'.mysql_errno().'-'.mysql_error($con));
        mysql_query('SET NAMES "utf8"', $con);
        $this->con = $con;
    }
    public function __get($table) {
        $this->dba_table->table = $table;
        return $this->dba_table;
    }
    public function free() {
        if($this->api_res) $this->res->free();
        if(is_resource($this->res)) {
            mysql_free_result($this->res);
            $this->res = null;
        }
    }
    public function query($sql) {
        $this->sql = $sql;
        if($this->select_api && $this->put_api($sql)) {
            $this->api_res = true;
            $this->res =  new dba_interface();
            return;
        }
        $this->free();
        $this->res = mysql_query($sql, $this->con);
        if($this->res === false) {
            throw new XException('MySQL Query Error:#'.mysql_errno($this->con).'-'.mysql_error($this->con));
        }
        return $this->res;
    }
    public function put_api($sql) {
        $sql_parts = explode(' ',trim($sql));
        if(strtoupper($sql_parts[0]) == 'SELECT') {
            return true;
        }
        return false;
    }
    private function assoc() {
        if($this->api_res) return $this->res->fetch_assoc();
        return mysql_fetch_assoc($this->res);
    }
    private function row() {
        if($this->api_res) return $this->res->fetch_row();
        return mysql_fetch_row($this->res);
    }
    private function count_rows() {
        if($this->api_res) return $this->res->num_rows();
        return mysql_num_rows($this->res);
    }
    public function fetch($sql) {
        $return = array();
        $this->query($sql);
        if($this->api_fetch) {
            $this->api_fetch = false;
        }
        while($row = $this->assoc()) {
            $return[] = $row;
        }
        return $return;
    }
    public function get_one_row($sql) {
        $this->query($sql);
        return $this->assoc();
    }
    public function get_one($sql) {
        $this->query($sql);
        $row = $this->row();
        return $row[0];
    }
    public function affected_rows() {
        if($this->api_res) return $this->res->affected_rows();
        return mysql_affected_rows($this->res);
    }
    public function insert_id() {
        return $this->get_one('SELECT LAST_INSERT_ID()');
    }
    public function get_all_row($sql) {
        $return = array();
        $this->query($sql);
        while($row = $this->row()) {
            $return[] = $row;
        }
		return $return;
    }
    public function close() {
        if($this->api_res) return $this->res->close();
        mysql_close($this->con);
    }
    public function __destruct() {
        $this->free();
    }

}
class XMySQLTable {
    public $table = null;
    public $dba = null;
    public $field_list = null;
    public $primary_name = null;
    //public $res = null;
    public function __construct($db) {
        $this->dba = $db;
        //$this->columnus();
    }
    public function columnus() {
        if($this->field_list == null) {
            $arr = $this->dba->fetch("SHOW COLUMNS FROM `{$this->table}`");
            foreach($arr as $key => $value) {
                $return[] = $value['Field'];
            }
            $this->field_list = $return;
        }
        return $this->field_list;
    }
    public function columnus_list_sql() {
        $this->columnus();
        $sql = '`'.implode('`,`', $this->field_list) .'`';
        return $sql;
    }
    public function auto_select($limit,$start=0) {
        $columuns = $this->columnus_list_sql();
        $sql = "SELECT $columuns FROM `{$this->table}` LIMIT $start,$limit";
        return $this->dba->fetch($sql);
    }
    public function primary() {
        if($this->primary_name == null) {
            $sql = "SHOW INDEX FROM `{$this->table}` WHERE key_name='PRIMARY'";
            $index = $this->dba->get_one_row($sql);
            $this->primary_name =  empty($index) ? false : $index['Column_name'];
        }
        return $this->primary_name;
    }
    public function primary_select_by_in($in) {
       $primary_name = $this->primary();
        $columuns = $this->columnus_list_sql();
        $limit = count($in);
        $where = implode('\',\'',$in);
        $sql = "SELECT $columuns FROM `{$this->table}` WHERE `$primary_name` IN('$where') LIMIT $limit";
        $re = $this->dba->fetch($sql);
        return empty($re) ? false : $re;
    }
    public function primary_select($value) {
        $primary_name = $this->primary();
        $columuns = $this->columnus_list_sql();
        $sql = "SELECT $columuns FROM `{$this->table}` WHERE `$primary_name`='$value' LIMIT 1";
        $re = $this->dba->get_one_row($sql);
        return empty($re) ? false : $re;
    }
    public function primary_delete($value) {
        $primary_name = $this->primary();
        $sql = "DELETE FROM {$this->table} WHERE `$primary_name`='$value' LIMIT 1";
        $this->dba->query($sql);
    }
    public function primary_update($id, array $data) {
        $primary_name = $this->primary();
        $fv = array();
        foreach($data as $key=>$value) {
            $fv[] = "`$key`='$value'";
        }
        $set_str = implode(',',$fv);
        $sql = "UPDATE `{$this->table}` SET $set_str WHERE `$primary_name`='$id' LIMIT 1";
        $this->dba->query($sql);
    }
    public function auto_select_count($where = null) {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`" . $where;
        return $this->dba->get_one($sql);
    }
    public function auto_insert(array $arr) {
        $keys = array_keys($arr);
        $columuns = '`'.implode('`,`', $keys) .'`';
        $values_str  = '\'' . implode('\',\'', $arr) . '\'';
        $sql = "INSERT INTO `{$this->table}` ($columuns) VALUES ($values_str)";
        $this->dba->query($sql) or die(mysql_error());
        return $this->dba->insert_id();
    }
    public function get_one_field($field,$limit,$start=0) {
        $field = $field ? $this->columnus_list_sql() : "`$field`";
        $sql = "SELECT $field FROM `{$this->table}` LIMIT {$start},{$limit}";
        $this->dba->query($sql);
        $return = array();
        while($row = mysql_fetch_row($this->res)) {
            $return[] = $row[0];
        }
        return $return;
    }
}