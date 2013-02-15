
Database
========

Copyright 2012, Snowfire AB, snowfireit.com
Licensed under the MIT License.
Redistributions of files must retain the above copyright notice.

    $database = new Lib\Database(array(
    	'user' => 'user name',
    	'pass' => 'password',
    	'dbname' => 'database name'
    ));

Default options are:

	array(
		'host' => '127.0.0.1',
		'port' => 3306
	)



`Database::execute()`
---------------------

For `INSERT`, `UPDATE` and `DELETE`.

	public function execute($sql, $parameters = array());

### Examples

	$database->execute('DELETE FROM table');
	$database->execute('DELETE FROM table WHERE id = ?', $id);
	$database->execute('DELETE FROM table WHERE id = ? OR id = ?', array($id1, $id2));
	$database->execute('DELETE FROM table WHERE id = :current_id OR id = :master_id', array(
		'current_id' => '1', 
		'master_id' => '2'
	));



`Database::one()`, `Database::many()`
-------------------------------------

For `SELECT`. `Database::one()` returns the first row, `Database::many()` returns an array with all rows.

	public function one($sql, $parameters = array(), $option = null)
	public function many($sql, $parameters = array(), $option = null)

Parameters `$sql` and `$parameters` works the same as in `Database::execute()`. 

### Options

- `single_column`, return only the first column for every row.



`Database::last_insert_id()`
----------------------------

Returns the ID of the last inserted row.



Transactions
------------

Begin and end [transactions](http://dev.mysql.com/doc/refman/5.5/en/commit.html) with `Database::transaction_begin()` and `Database::transaction_end()`.