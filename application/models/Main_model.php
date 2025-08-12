<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main_model extends CI_Model {

	public function get_data($tbl, $where=null, $row=FALSE, $select=null, $order=FALSE){

		if($where != null){
			$this->db->where($where);
		}

		if($select != null){
			$this->db->select($select);
		}

		if($order != FALSE){
			$this->db->order_by($order);
		}

		$query = $this->db->get($tbl);

		// return $this->db->last_query();
		if($row == TRUE){
			$result_data = $query->row();	
		}else{
			$result_data= $query->result();
		}
		return $result_data;
	}

	public function insert_data($tbl, $set, $id=FALSE){
		$this->db->trans_start();

		$this->db->set($set);
		$this->db->insert($tbl);
		$insert_id = $this->db->insert_id();

		if($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			return FALSE;
		}else{
			$this->db->trans_commit();
			if ($id == TRUE) {
				$result['result'] = TRUE;
				$result['id'] = $insert_id;
				return $result;
			} else {
				return TRUE;
			}
		}	
	}

	public function update_data($tbl, $set, $where){
		$this->db->trans_start();

		$this->db->set($set);
		$this->db->where($where);
		$this->db->update($tbl);

		if($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			return FALSE;
		}else{
			$this->db->trans_commit();
			return TRUE;
		}	
	}

	public function check_data($tbl, $where, $row=FALSE, $select=FALSE){

		if($select != FALSE){
			$this->db->select($select);
		}

		$query = $this->db->get_where($tbl, $where);

		$result = $query->num_rows();

		if($result > 0){
			if($row == TRUE){
				$data['info'] = $query->row();
				$data['result'] = TRUE;
			}else{
				$data = TRUE;
			}
			
		}else{
			if($row == TRUE){
				$data['result'] = FALSE;
			}else{

				$data = FALSE;
			}
			
		}
		return $data;
	}

	public function get_join($tbl, $join, $row_type=FALSE, $order=FALSE, $group=FALSE, $select=FALSE, $where=FALSE, $limit = 0, $string = FALSE){

		foreach($join as $row=>$value){
			if(strpos($row, ', ')){
				$split_row = explode(', ', $row);
				$join_tbl = $split_row[0];
				$join_type = $split_row[1];

				$this->db->join($join_tbl, $value, $join_type);
			}else{
				$this->db->join($row, $value);
			}
		}

		if($select != FALSE){
			$this->db->select($select);
		}
		
		if($group != FALSE){
			$this->db->group_by($group);
		}

		if($order != FALSE){
			$this->db->order_by($order);
		}

		if($where != FALSE){
			$this->db->where($where);
		}

		if ($limit) {
			$this->db->limit($limit);
		}

		$query = $this->db->get($tbl);

		if($string){
			return $this->db->last_query();
		}
		
		if($row_type === FALSE){
			$result = $query->result();
		}else{
			$result = $query->row();
		}
		return $result;
	}

	public function get_query($sql_query, $row_type=FALSE){

		$query = $this->db->query($sql_query);
		if($row_type === FALSE){
			$result = $query->result();
		}else{
			$result = $query->row();
		}
		return $result;
	}

	public function check_join($tbl, $join, $row_type=FALSE, $order=FALSE, $group=FALSE, $select=FALSE){

		foreach($join as $row=>$value){
			$this->db->join($row, $value);	
		}
		
		if($select != FALSE){
			$this->db->select($select);
		}
		
		if($group != FALSE){
			$this->db->group_by($group);
		}

		if($order != FALSE){
			$this->db->order_by($order);
		}

		$query = $this->db->get($tbl);
		$num_rows = $query->num_rows();
		if($row_type == FALSE){

			if($num_rows > 0){
				return TRUE;
			}else{
				return FALSE;
			}
		}else{

			if($num_rows > 0){
				$result['result'] = TRUE;

				$result['info'] = $query->row();
				return $result;
			}else{
				$result['result'] = FALSE;
				return $result;
			}
		}
		
	}

	public function check_query($query, $row_data=FALSE){
		$query = $this->db->query($query);

		$num = $query->num_rows();
		if($row_data == FALSE){
			
			if($num > 0){
				return TRUE;
			}else{
				return FALSE;
			}
		}else{
			if($num > 0){
				$data['result'] = TRUE;	
				$data['info'] = $query->row();
			}else{
				$data['result'] = FALSE;	
			}
			
		}

		return $data;
	}

	public function get_count($tbl, $where=null){
		
		if($where != null){
			$this->db->where($where);	
		}
		
		$query = $this->db->get($tbl);

		$num = $query->num_rows();
		return $num;
	}

	public function get_join_datatables($tbl, $join, $row_type=FALSE, $order=FALSE, $group=FALSE, $select=FALSE, $where=FALSE, $string = false){

		if($join){
			foreach($join as $row=>$value){
				if(strpos($row, ', ')){
					$split_row = explode(', ', $row);
					$join_tbl = $split_row[0];
					$join_type = $split_row[1];
	
					$this->db->join($join_tbl, $value, $join_type);
				}else{
					$this->db->join($row, $value);
				}
			}
		}

		if($select != FALSE){
			$this->db->select($select);
		}
		
		if($group != FALSE){
			$this->db->group_by($group);
		}

		if($order != FALSE){
			$this->db->order_by($order);
		}

		if($where != FALSE){
			$this->db->where($where);
		}

		$query = $this->db->get($tbl);
		if($row_type === FALSE){
			$result = $query;
		}else{
			$result = $query->row();
		}
		if($string){
			return $this->db->last_query();
		} else {
			return $result;
		}
	}

	public function get_dynamic_dt($postData, $table, $column_order = false, $column_search = null, $order = null, $select = false, $join = null, $filter = false, $group_by = false){
		

        if($select != FALSE){
            $this->db->select($select);
        }

        $this->db->from($table);

        if($join != false){
            foreach($join as $row => $value){
            	if(is_array($value)){
	            	foreach ($value as $key => $key_data) {
	            		$this->db->join($row, $key, $key_data);
	            	}
            	} else {
            		$this->db->join($row, $value);
            	}
            }
        }
 
        $i = 0;
        // loop searchable columns 
        foreach($column_search as $item){
            // if datatable send POST for search
            if($postData['search']['value']){
                // first loop
                if($i===0){
                    // open bracket
                    $this->db->group_start();
                    $this->db->like($item, $postData['search']['value']);
                }else{
                    $this->db->or_like($item, $postData['search']['value']);
                }
                
                // last loop
                if(count($column_search) - 1 == $i){
                    // close bracket
                    $this->db->group_end();
                }
            }
            $i++;
        }
        if($filter != FALSE){
			$this->db->where($filter);
		}
        //$this->db->where($where);
        if($group_by != FALSE){
        	$this->db->group_by($group_by);
    	}
    	
        if(isset($postData['order'])){
            $this->db->order_by($column_order[$postData['order']['0']['column']], $postData['order']['0']['dir']);
        }else if(isset($order)){
            if(is_array($order)){
				$this->db->order_by(key($order), $order[key($order)]);
			} else {
				$this->db->order_by($order);
			}
            
        }
        

        if($postData['length'] != -1){
            $this->db->limit($postData['length'], $postData['start']);
        }
        $query = $this->db->get();
        $result['query'] = $this->db->last_query();
        $result['result'] = $query;
        return $result;
        
    }

	public function countAll($table){

        $this->db->from($table);
        return $this->db->count_all_results();
    }

    public function countFiltered($postData, $table, $column_order = false, $column_search = null, $order = null, $select = false, $join = null, $filter = false, $group_by = false){
    	
        
        if($select != FALSE){
            $this->db->select($select);
        }	        

        $this->db->from($table);

        if($join != false){
            foreach($join as $row=>$value){
            	if(is_array($value)){
	            	foreach ($value as $key => $key_data) {
	            		$this->db->join($row, $key, $key_data);
	            	}
            	} else {
            		$this->db->join($row, $value);
            	}
            }
        }
 
        $i = 0;
        // loop searchable columns 
        foreach($column_search as $item){
            // if datatable send POST for search
            if($postData['search']['value']){
                // first loop
                if($i===0){
                    // open bracket
                    $this->db->group_start();
                    $this->db->like($item, $postData['search']['value']);
                }else{
                    $this->db->or_like($item, $postData['search']['value']);
                }
                
                // last loop
                if(count($column_search) - 1 == $i){
                    // close bracket
                    $this->db->group_end();
                }
            }
            $i++;
        }

        if($filter != FALSE){
			$this->db->where($filter);
		}
		//$this->db->where($where);
		if($group_by != FALSE){
        	$this->db->group_by($group_by);
    	}
         
        if(isset($postData['order'])){
            $this->db->order_by($column_order[$postData['order']['0']['column']], $postData['order']['0']['dir']);
        }else if(isset($order)){
            
            if(is_array($order)){
				$this->db->order_by(key($order), $order[key($order)]);
			} else {
				$this->db->order_by($order);
			}
        }

        $query = $this->db->get();

        return $query->num_rows();
    }
}
