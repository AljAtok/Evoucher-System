<?php namespace RabbitORM;
class QueryBuilder {

	protected $db_conn = null;
	protected $table = '';

	function __construct($db_group, $table)
	{
		$ci =& get_instance();

		$this->db_conn = $ci->load->database($db_group, true);

		$this->table = $table;
		$this->db_conn->from( $this->table );

		if(defined('RABBITORM_DEBUG') and RABBITORM_DEBUG === true)
		{
			$property_name = 'rabbitorm_db_'.rand();
			$ci->{$property_name} = $this->db_conn;
		}
	}

	function __call($name, $arguments)
	{
		if(!method_exists($this->db_conn, $name)) return show_error('Unknown function '.$name, 500);

		return call_user_func_array( array($this->db_conn, $name), $arguments );
	}

	function __clone()
	{
		$this->db_conn = clone $this->db_conn;
	}

	function select($columns = array())
	{
		if (empty($columns)) {
			return null; // it's no allowed return all direct in QueryBuilder
		}
			$query = '';
			foreach($columns as $key => $value) {
				$query .= "$value as '$key' ";
				if (next($columns) !== false) {
				 $query .= ",";
				}
			}
		$this->db_conn->select($query);
	}

	function insert($data)
	{
		$insert = $this->db_conn->insert($this->table, $data );
		return ($insert !== false) ? $this->db_conn->insert_id() : false;
	}

	function update($data, $where = array())
	{
		if(empty($where) and !is_array($where)) $where = array();

		$update = $this->db_conn->update( $this->table, $data, $where );
		return $update;
	}
}