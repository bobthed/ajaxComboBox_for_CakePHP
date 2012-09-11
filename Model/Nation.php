<?php
class Nation extends AppModel {
	//==============================================================
	//Please change following variables.
	//--------------------------------------------------------------
	//var $bitly_usr = 'username';
	//var $bitly_key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
	//==============================================================
	public $useTable = 'nation';
	/*
	Methods(2012-09-11)
		shortenByBitly       Connect to bit.ly
		modelAjaxSearch      Root of Ajax search
		escapeAlongDB        Escape the all elements of array
		escapeStr            Escape along database
		quoteAlongDB         Quote along database
		quoteSearchField     Quote each search-fields
		quoteOrderby         Quote each order-by
		setAsterisk          Use all of fields instead of "*"
		setQuerySearchAll    Get all records
		setQuerySearchWords  Search by words
	*/
	//****************************************************
	//Connect ot bit.ly
	//****************************************************
	function shortenByBitly() {
		//JavaScriptへ返す配列を準備
		$result  = array();

		//可変長オブジェクトを引数に採っている
		foreach ($_GET as $key => $url) {
			//CakePHP用の処理
			if (!preg_match('/^p_\d+$/', $key)) continue;
			
			if (!is_string($url)) die();
	
			$req  = 'http://api.bit.ly/shorten?version=2.0.1';
			$req .= "&login={$this->bitly_usr}";
			$req .= "&apiKey={$this->bitly_key}";
			$req .= '&longUrl='.rawurlencode($url);

			$contents = file_get_contents($req);
			if(isset($contents)) {
				$bitly = json_decode($contents, true);
			}
			$result[] = $bitly['results'][$url]['shortUrl'];
		}
		return json_encode($result);
	}
	//****************************************************
	//Root of Ajax search
	//****************************************************
	function modelAjaxSearch($not_escaped) {
		//Check the type of database.
		$type_db = (preg_match(
			'/sqlite/i',
			$this->getDatasource($this->useDbConfig)->config['datasource']
		)) ? 'sqlite' : 'mysql';

		//Escape all params
		$clear = $this->escapeAlongDB($not_escaped, $type_db);

		if (array_key_exists('search_field', $clear)){
			$clear['quoted_sf'] = $this->quoteSearchField($clear['search_field'], $type_db);
		}

		//insert "ESCAPE '\'" if SQLite3
		$clear['esc'] = ($type_db == 'sqlite') ? "ESCAPE '\'" : '';

		//CASE WHEN 以降の並べ替えの条件
		if (array_key_exists('order_by', $clear)){
			$clear['order_by'] = $this->quoteOrderby($clear['order_by'], $type_db);
		}

		//Use all of fields instead of "*"
		$clear['asterisk'] = $this->setAsterisk($type_db);

		if (array_key_exists('page_num', $clear) && isset($clear['page_num'])) {
			if ($clear['q_word'][0] == '') {
				$queries = $this->setQuerySearchAll($clear, $type_db);
			} else {
				$queries = $this->setQuerySearchWords($clear, $type_db);
			}
			//----------------------------------------------------
			// Query to database
			//----------------------------------------------------
			$data  = $this->query($queries[0]);
			$data2 = $this->query($queries[1]);

			$return = array();
			for($i=0; $i<count($data); $i++){
				$return['result'][] = $data[$i][$this->name];
			}
			$return['cnt_whole'] = $data2[0][0]['cnt'];
			return json_encode($return);
		} else {
			//----------------------------------------------------
			//get initialize value
			//----------------------------------------------------
			$data = $this->find('first', array(
				'conditions' => array($not_escaped['pkey_name'] => $not_escaped['pkey_val'])
			));
			return json_encode($data[$this->name]);
		}
	}
	//****************************************************
	//Escape the all elements of array
	//****************************************************
	function escapeAlongDB($not_escaped, $type_db) {
		$return = array();
		foreach ($not_escaped as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $key2 => $val2) {
					if (is_array($val2)) {
						foreach ($val2 as $key3 => $val3) {
							$return[$key][$key2][$key3] = $this->escapeStr($val3, $type_db);
						}
					} else {
						$return[$key][$key2] = $this->escapeStr($val2, $type_db);
					}
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
	function escapeStr($str, $type_db) {
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
	function quoteAlongDB($str, $type_db) {
		return ($type_db == 'sqlite')
			? '"'.$str.'"'
			: '`'.$str.'`';
	}
	//****************************************************
	//Quote each search-fields 
	//****************************************************
	function quoteSearchField($arr, $type_db) {
		$return = array();
		foreach ($arr as $val) {
			$return[] = $this->quoteAlongDB($val, $type_db);
		}
		return $return;
	}
	//****************************************************
	//Quote each order-by
	//****************************************************
	function quoteOrderby($order_by, $type_db) {
		$arr = array();
		for ($i=0; $i<count($order_by); $i++) {
			$arr[] = $this->quoteAlongDB($order_by[$i][0], $type_db).' '.$order_by[$i][1];
		}
		return join(',', $arr);
	}
	//****************************************************
	//Use all of fields instead of "*"
	//****************************************************
	function setAsterisk($type_db) {
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
	//Get all records
	//****************************************************
	function setQuerySearchAll($clear, $type_db) {
		$quoted_t = $this->quoteAlongDB($clear['db_table'], $type_db);
		$clear['offset'] = ($clear['page_num'] - 1) * $clear['per_page'];
		return array(
			sprintf(
				"SELECT %s FROM %s AS %s ORDER BY %s LIMIT %s OFFSET %s",
				$clear['asterisk'],
				$quoted_t,
				$this->quoteAlongDB($this->name, $type_db),
				$clear['order_by'],
				$clear['per_page'],
				$clear['offset']		
			),
			"SELECT COUNT({$clear['quoted_sf'][0]}) AS cnt FROM $quoted_t"
		);
	}
	//****************************************************
	//Search by words
	//****************************************************
	function setQuerySearchWords($clear, $type_db) {
		//----------------------------------------------------
		// WHERE
		//----------------------------------------------------
		$depth1 = array();
		for($i = 0; $i < count($clear['q_word']); $i++){
			$depth2 = array();
			for($j = 0; $j < count($clear['quoted_sf']); $j++){
				$depth2[] = "{$clear['quoted_sf'][$j]} LIKE '%{$clear['q_word'][$i]}%' {$clear['esc']}";
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
			for ($j = 0; $j < count($clear['quoted_sf']); $j++) {		
				$str .= "WHEN {$clear['quoted_sf'][$j]} = '{$clear['q_word'][$i]}' ";
				$str .= "THEN $cnt ";
				$cnt++;
				$str .= "WHEN {$clear['quoted_sf'][$j]} LIKE '{$clear['q_word'][$i]}%' {$clear['esc']} ";
				$str .= "THEN $cnt ";
				$cnt++;
				$str .= "WHEN {$clear['quoted_sf'][$j]} LIKE '%{$clear['q_word'][$i]}%' {$clear['esc']} ";
				$str .= "THEN $cnt ";
			}
		}
		$cnt++;

		$clear['order'] = $str . "ELSE $cnt END), {$clear['order_by']}";
	
		//----------------------------------------------------
		// OFFSET
		//----------------------------------------------------
		$clear['offset'] = ($clear['page_num'] - 1) * $clear['per_page'];

		//----------------------------------------------------
		// Generate SQL
		//----------------------------------------------------
		$quoted_t = $this->quoteAlongDB($clear['db_table'], $type_db);
		return array(
			sprintf(
				"SELECT %s FROM %s AS %s WHERE %s ORDER BY %s LIMIT %s OFFSET %s",
				$clear['asterisk'],
				$quoted_t,
				$this->quoteAlongDB($this->name, $type_db),
				$clear['where'],
				$clear['order'],
				$clear['per_page'],
				$clear['offset']		
			),
			//whole count
			sprintf(
				"SELECT COUNT(%s) AS cnt FROM %s WHERE %s",
				$clear['quoted_sf'][0],
				$quoted_t,
				$clear['where']
			)
		);
	}
}
