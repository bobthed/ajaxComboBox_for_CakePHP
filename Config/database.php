<?php
class DATABASE_CONFIG {

	//MySQL
	public $default = array(
		'datasource' => 'Database/Mysql',
		'persistent' => false,
		'host'       => 'localhost',
		'login'      => 'root',
		'password'   => '',
		'database'   => 'acbox',
		'prefix'     => '',
		'encoding'   => 'utf8'
	);
	/*
	//SQLite Local
	public $default = array(
		'datasource' => 'Database/Sqlite',
		'database' => '../sqlite3/sample.sqlite3'
	);
	*/
}
