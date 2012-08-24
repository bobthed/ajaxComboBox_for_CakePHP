<?php
class Nation extends AppModel {
	public $name = 'Nation';
	public $useTable = 'nation';

	//****************************************************
	//Escape the all elements of array
	//****************************************************
	public function escapeAlongDB($not_escaped, $type_db) {
		$return = array();
		foreach ($not_escaped as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $key2 => $val2) {
					$return[$key][$key2] = $this->escapeStr($val2, $type_db);
				}
			} else {
				$return[$key] = $this->escapeStr($val, $type_db);
			}
		}
		return $return;
	}
	//****************************************************
	//Escape along database
	//****************************************************
	public function escapeStr($str, $type_db) {
		if ($type_db == 'sqlite') {
			$str = sqlite_escape_string(
				str_replace(
					array('\\',   '%',  '_'),
					array('\\\\', '\%', '\_'),
					$str
				)
			);
		} else {
			$str = mysql_escape_string(
				str_replace(
					array('\\',   '%',  '_'),
					array('\\\\', '\%', '\_'),
					$str
				)
			);
		}
		return $str;
	}
	//****************************************************
	//Quote along database
	//****************************************************
	public function quoteAlongDB($str, $type_db) {
		return ($type_db == 'sqlite')
			? '"'.$str.'"'
			: '`'.$str.'`';
	}
	//****************************************************
	//Get the list of all fields instead of asterisk
	//****************************************************
	public function setAsterisk($type_db) {
		if ($type_db == 'sqlite') {
			//--------------------
			// SQLite3
			//--------------------
			$path = ConnectionManager::$config->{$this->useDbConfig}['database'];
			$db = new SQLite3($path);
			$rows = $db->query("PRAGMA table_info(\"{$this->useTable}\")");
			$return = array();
			$quoted_m = $this->quoteAlongDB($this->name, $type_db);
			while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
				$quoted_f =$this->quoteAlongDB($row['name'], $type_db);
				$return[] = "$quoted_m.$quoted_f";
			}
			$db->close();
			return join(',', $return);
		} else {
			//--------------------
			// MySQL
			//--------------------
			return '*';
		}
	}
	//****************************************************
	//Search for database
	//****************************************************
	public function modelAjaxSearch($not_escaped) {
		//Check the type of database.
		$ds = ConnectionManager::$config->{$this->useDbConfig}['datasource'];
		$type_db = (preg_match('/sqlite/i', $ds)) ? 'sqlite' : 'mysql';
		
		//insert "ESCAPE '\'" if SQLite3
		$esc_sqlite = ($type_db == 'sqlite') ? "ESCAPE '\'" : '';
		
		//Escape all params
		$clear = $this->escapeAlongDB($not_escaped, $type_db);

		//List of all fields
		$asterisk = $this->setAsterisk($type_db);

		if (isset($clear['page_num'])) {
			if ($clear['q_word'][0] == '') {
			
				$quoted_o = $this->quoteAlongDB($clear['order_field'][0], $type_db);
				$quoted_t = $this->quoteAlongDB($clear['db_table'], $type_db);
				
				$clear['order']  = "$quoted_o {$clear['order_by']}";
				$clear['offset'] = ($clear['page_num'] - 1) * $clear['per_page'];

				$query = sprintf(
					"SELECT %s FROM %s AS %s ORDER BY %s LIMIT %s OFFSET %s",
					$asterisk,
					$quoted_t,
					$this->name,
					$clear['order'],
					$clear['per_page'],
					$clear['offset']		
				);
				//whole count
				$query2 = "SELECT COUNT($quoted_o) AS cnt FROM $quoted_t";

			} else {
				//----------------------------------------------------
				// WHERE
				//----------------------------------------------------
				$depth1 = array();
				for($i = 0; $i < count($clear['q_word']); $i++){
					$depth2 = array();
					for($j = 0; $j < count($clear['search_field']); $j++){
						$quoted_s = $this->quoteAlongDB($clear['search_field'][$j], $type_db);
						$depth2[] = "$quoted_s LIKE '%{$clear['q_word'][$i]}%' $esc_sqlite ";
					}
					$depth1[] = '(' . join(' OR ', $depth2) . ')';
				}
				$clear['where'] = join(" {$clear['and_or']} ", $depth1);

				//----------------------------------------------------
				// ORDER BY
				//----------------------------------------------------
				$cnt = 0;
				$str = '(CASE ';
				for ($i = 0; $i < count($clear['q_word']); $i++) {
					for ($j = 0; $j < count($clear['order_field']); $j++) {
						$quoted_o = $this->quoteAlongDB($clear['order_field'][$j], $type_db);
				
						$str .= "WHEN $quoted_o = '{$clear['q_word'][$i]}' ";
						$str .= "THEN $cnt ";
						$cnt++;
						$str .= "WHEN $quoted_o LIKE '{$clear['q_word'][$i]}%' $esc_sqlite ";
						$str .= "THEN $cnt ";
						$cnt++;
						$str .= "WHEN $quoted_o LIKE '%{$clear['q_word'][$i]}%' $esc_sqlite ";
						$str .= "THEN $cnt ";
					}
				}
				$cnt++;

				$quoted_o_0 = $this->quoteAlongDB($clear['order_field'][0], $type_db);
				$clear['orderby'] = $str . "ELSE $cnt END), $quoted_o_0 {$clear['order_by']}";
			
				//----------------------------------------------------
				// OFFSET
				//----------------------------------------------------
				$clear['offset'] = ($clear['page_num'] - 1) * $clear['per_page'];

				//----------------------------------------------------
				// Generate SQL
				//----------------------------------------------------
				$quoted_t = $this->quoteAlongDB($clear['db_table'], $type_db);
				$query = sprintf(
					"SELECT %s FROM %s AS %s WHERE %s ORDER BY %s LIMIT %s OFFSET %s",
					$asterisk,
					$quoted_t,
					$this->name,
					$clear['where'],
					$clear['orderby'],
					$clear['per_page'],
					$clear['offset']		
				);
				//whole count
				$query2 = sprintf(
					"SELECT COUNT(%s) AS cnt FROM %s WHERE %s",
					$quoted_o_0,
					$quoted_t,
					$clear['where']
				);
			}
			//----------------------------------------------------
			// Query to database
			//----------------------------------------------------
			$data  = $this->query($query);
			$data2 = $this->query($query2);

			$return = array();
			for($i=0; $i<count($data); $i++){
				$return['result'][] = $data[$i][$this->name];
			}
			$return['cnt_whole'] = $data2[0][0]['cnt'];

			return json_encode($return);

		} else {
			//****************************************************
			//get initialize value
			//****************************************************
			$arr_params = array(
				'conditions' => array($not_escaped['pkey_name'] => $not_escaped['pkey_val'])
			);
			$data = $this->find('all', $arr_params);
			echo json_encode($data[0][$this->name]);
		}
	}
}
