# Simple PDO wrapper

Usage examples
-----------------

### Creating database instance
Mysql

    $db = PDOWrapper::openMysql(YOUR_DB_HOST, YOUR_DB_NAME, YOUR_DB_USER, YOUR_DB_PASSWORD);
    $db = PDOWrapper::openMysql(YOUR_DB_HOST, YOUR_DB_NAME, YOUR_DB_USER, YOUR_DB_PASSWORD, CHARSET);

Sqlite

    $db = PDOWrapper::openSqlite( DB_PATH );

Custom

    $db = new PDOWrapper(DSN, YOUR_DB_USER, YOUR_DB_PASSWORD);

### Select
Select first record

    // SELECT * FROM table_name WHERE field1='value1'
    $record = $db->selectFirstRecord("SELECT * FROM table_name WHERE field1=?", array("value1"));

    // SELECT * FROM table_name WHERE id=5
    $id = 5;
    $record = $db->selectRecordWithId("table_name", $id);

Select all records

    // SELECT * FROM table_name WHERE id>5 ORDER BY id
    $records = $db->selectRecords("SELECT * FROM table_name WHERE id>? ORDER BY id", array(5));

### Insert

    // INSERT INTO table_name (field1, field2) VALUES ('value1', 2)
    $lastInsertId = $db->insertRecord("table_name", array("field1"=>"value1", "field2"=>2));

### Update

    // UPDATE table_name SET field1='value1', field2=2
    $result = $db->updateRecord("table_name", array("field1"=>"value1", "field2"=>2));

    // UPDATE table_name SET field1='value1', field2=2 WHERE id>5
    $result = $db->updateRecord("table_name", array("field1"=>"value1", "field2"=>2), "id>?", array(5));

    // // UPDATE table_name SET field1='value1', field2=2 WHERE id=5
    $id = 5;
    $result = $db->updateRecordWithId("table_name", array("field1"=>"value1", "field2"=>2), $id);

### Delete

    // DELETE FROM table_name
    $result = $db->deleteRecord("table_name");

    // DELETE FROM table_name WHERE id>5
    $result = $db->deleteRecord("table_name", "id>?", array(5));

    // DELETE FROM table_name WHERE id=5
    $id = 5;
    $result = $db->deleteRecordWithId("table_name", $id);

### Count rows in table
    $count = $db->getRowCount("table_name");
    $count = $db->getRowCount("table_name", "id>?", array(5));

### Custom query

    // UPDATE table_name SET field1='value1', field2='value2' WHERE id>5
    $stmt = $db->query("UPDATE table_name SET field1=?, field2=? WHERE id>?", array("value1", "value2", 5));

    $stmt = $db->query("DROP TABLE table_name");

### Transactions
Transaction methods: <b>beginTransaction(), endTransaction(), cancelTransaction()</b>

### Errors
If functions (<b>selectFirstRecord, selectRecordWithId, selectRecords, insertRecord, updateRecord, updateRecordWithId, deleteRecord, deleteRecordWithId, getRowCount, query</b>) returns false, it means that an error has occurred.
You can get the error string:

    $errorString = $db->getLastError();

