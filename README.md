# Simple PDO wrapper

Usage examples
-----------------

### Creating database instance
Mysql

    $db = PDOWrapper::openMysql(YOUR_DB_HOST, YOUR_DB_NAME, YOUR_DB_USER, YOUR_DB_PASSWORD);
    $db = PDOWrapper::openMysql(YOUR_DB_HOST, YOUR_DB_NAME, YOUR_DB_USER, YOUR_DB_PASSWORD, EXIT_ON_ERROR_FLAG, CHARSET);

Sqlite

    $db = PDOWrapper::openSqlite( DB_PATH );
    $db = PDOWrapper::openSqlite(DB_PATH, EXIT_ON_ERROR_FLAG);

Custom

    $db = new PDOWrapper(DSN, YOUR_DB_USER, YOUR_DB_PASSWORD, EXIT_ON_ERROR_FLAG);

### Select
Select first record

    $record = $db->selectOneRecord("SELECT * FROM table_name WHERE id=?", array(5));

Select all records

    $records = $db->selectRecords("SELECT * FROM table_name WHERE id>? ORDER BY id", array(5));

### Insert 
    $lastInsertId = $db->insertRecord("table_name", array("field1"=>"value1", "field2"=>2));

### Update
    $result = $db->updateRecord("table_name", array("field1"=>"value1", "field2"=>2));
    $result = $db->updateRecord("table_name", array("field1"=>"value1", "field2"=>2), "id>?", array(5));

    $id = 5;
    $result = $db->updateRecordWithId("table_name", array("field1"=>"value1", "field2"=>2), $id);

### Delete
    $result = $db->deleteRecord("table_name");
    $result = $db->deleteRecord("table_name", "id>?", array(5));

    $id = 5;
    $result = $db->deleteRecordWithId("table_name", $id);

### Count rows in table
    $count = $db->getRowCount("table_name");
    $count = $db->getRowCount("table_name", "id>?", array(5));

### Custom query
    $stmt = $db->query("UPDATE table_name SET field1=?, field2=? WHERE id>?", array("value1", "value2", 5));
    $stmt = $db->query("DROP TABLE table_name");

### Transactions
Transaction methods: <b>beginTransaction(), endTransaction(), cancelTransaction()</b>

### Errors
If functions (<b>selectOneRecord, selectRecords, insertRecord, updateRecord, updateRecordWithId, deleteRecord, deleteRecordWithId, getRowCount, query</b>) returns false, it means that an error has occurred.
You can get the error string:

    $errorString = $db->getLastError();

