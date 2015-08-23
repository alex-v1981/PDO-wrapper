<?php

/**
 * Class PDOWrapper
 */
class PDOWrapper
{
    private $dbh;
    private $idFieldName = "id";
    private $lastError = false;

    /**
     * Static method for creating PDOWrapper object with SQLITE connection
     *
     * @param string    $dbFilePath     Sqlite file path
     * @return PDOWrapper
     */
    public static function openSqlite( $dbFilePath )
    {
        return new PDOWrapper("sqlite:".$dbFilePath, "", "");
    }

    /**
     * Static method for creating PDOWrapper object with MYSQL connection
     *
     * @param   string  $host           Mysql host
     * @param   string  $dbName         Mysql database name
     * @param   string  $username       Mysql user name
     * @param   string  $password       Mysql password
     * @param   string  $charset        Connection charset
     * @return PDOWrapper
     */
    public static function openMysql($host, $dbName, $username, $password, $charset="")
    {
        $wrapper = new PDOWrapper("mysql:host=$host;dbname=$dbName", $username, $password);

        if ($charset && !$wrapper->getLastError())
            $wrapper->query("SET NAMES ?", array($charset));

        return $wrapper;
    }

    /**
     * Class constructor
     *
     * @param string    $dsn        Connection DSN
     * @param string    $username   Connection user name
     * @param string    $password   Connection password
     */
    function __construct($dsn, $username, $password)
    {
        try
        {
            $this->lastError = false;

            $this->dbh = new PDO($dsn, $username, $password);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e)
        {
            $this->error( $e->getMessage() );
        }
    }

    /**
     * Clears error variable
     */
    private function clearError()
    {
        $this->lastError = false;
    }

    /**
     * This method is called when an error occurs
     *
     * @param string $errorMsg  Error description
     */
    private function error($errorMsg="")
    {
        if ( $errorMsg )
        {
            $this->lastError = $errorMsg;
        }
        else
        {
            $info = $this->dbh->errorInfo();

            if ( $info[1] )
                $this->lastError = $info[2];
        }
    }

    /**
     * Set id field name for other methods with id parameter
     *
     * @param string $idFieldName   ID field name
     */
    public function setIdFieldName( $idFieldName )
    {
        if ( $idFieldName )
            $this->idFieldName = $idFieldName;
    }

    /**
     * Returns last error description
     *
     * @return bool|string  Error description or false when no error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Execute SQL query
     *
     * @param string    $sql        SQL statement
     * @param array     $bind       Bind parameters array
     * @return bool|PDOStatement    PDO statement or false when an error occurs
     */
    public function query($sql, $bind=array())
    {
        $this->clearError();

        try
        {
            $sth = $this->dbh->prepare( $sql );
            $sth->execute( $bind );
        }
        catch (PDOException $e)
        {
            $sth = false;
            $this->error( $e->getMessage() );
        }

        return $sth;
    }

    /**
     * Insert a record to a table
     *
     * @param string    $table  Table name
     * @param array     $fieldAndValueList  Associative array with the pairs: "field name" => value
     * @return bool|string  Last insert ID or false when an error occurs
     */
    public function insertRecord($table, $fieldAndValueList)
    {
        $fields = "";
        $placeholders = "";
        $values = array();

        foreach ($fieldAndValueList as $field => $value)
        {
            if ( $fields )
                $fields .= ",";

            $fields .= $field;

            if ( $placeholders )
                $placeholders .= ",";

            $placeholders .= "?";

            $values[] = $value;
        }

        $res = $this->query("INSERT INTO $table ($fields) VALUES ($placeholders)", $values);

        if ( $res )
            return $this->dbh->lastInsertId();

        return false;
    }

    /**
     * Update records for one table
     *
     * @param string    $table  Table name
     * @param array     $fieldAndValueList  Associative array with the pairs: "field name" => value
     * @param string    $whereWithNonameParams Where string with positional placeholders
     * @param array     $whereData  Bind parameters array for "where"
     * @return bool     Returns TRUE on success or FALSE on failure
     */
    public function updateRecord($table, $fieldAndValueList, $whereWithNonameParams="", $whereData=array())
    {
        if ( $whereWithNonameParams )
            $whereWithNonameParams = "WHERE ".$whereWithNonameParams;

        $fields = "";
        $values = array();

        foreach ($fieldAndValueList as $field => $value)
        {
            if ( $fields )
                $fields .= ",";

            $fields .= $field."=?";

            $values[] = $value;
        }

        $values = array_merge($values, $whereData);

        $res = $this->query("UPDATE $table SET $fields $whereWithNonameParams", $values);

        return $res ? true : false;
    }

    /**
     * Update records for one table for one record with id=value
     *
     * @param string    $table  Table name
     * @param array     $fieldAndValueList  Associative array with the pairs: "field name" => value
     * @param mixed     $id     ID value
     * @return bool     Returns TRUE on success or FALSE on failure
     */
    public function updateRecordWithId($table, $fieldAndValueList, $id)
    {
        return $this->updateRecord($table, $fieldAndValueList, '`'.$this->idFieldName.'`=?', array($id));
    }

    /**
     * Delete records from table
     *
     * @param string    $table  Table name
     * @param string    $where  Where string
     * @param array     $whereData   Bind parameters array for "where"
     * @return bool     Returns TRUE on success or FALSE on failure
     */
    public function deleteRecord($table, $where="", $whereData=array())
    {
        if ( $where )
            $where = "WHERE ".$where;

        $res = $this->query("DELETE FROM $table $where", $whereData);

        return $res ? true : false;
    }

    /**
     * Delete one record from table with id=value
     *
     * @param string    $table  Table name
     * @param mixed     $id     ID value
     * @return bool     Returns TRUE on success or FALSE on failure
     */
    public function deleteRecordWithId($table, $id)
    {
        return $this->deleteRecord($table, '`'.$this->idFieldName.'`=?', array($id));
    }

    /**
     * Select records from a table and returns row(s) as 2D array
     *
     * @param string    $sql    SQL statement
     * @param array     $bind   Bind parameters array
     * @return array|bool   Row(s) as 2D array or false when an error occurs
     */
    public function selectRecords($sql, $bind=array())
    {
        $sth = $this->query($sql, $bind);

        if ( !$sth )
            return false;

        try
        {
            $sth->setFetchMode( PDO::FETCH_ASSOC );
            $res = $sth->fetchAll();
        }
        catch (PDOException $e)
        {
            $res = false;
            $this->error( $e->getMessage() );
        }

        return $res;
    }

    /**
     * Select first record from a table
     *
     * @param string    $sql    SQL statement
     * @param array     $bind   Bind parameters array
     * @return array|bool   A result row or false when an error occurs
     */
    public function selectFirstRecord($sql, $bind=array())
    {
        $sth = $this->query($sql, $bind);

        if ( !$sth )
            return false;

        try
        {
            $sth->setFetchMode( PDO::FETCH_ASSOC );
            $res = $sth->fetch();
        }
        catch (PDOException $e)
        {
            $res = false;
            $this->error( $e->getMessage() );
        }

        return $res;
    }

    /**
     * Select record from a table with id=value
     *
     * @param string    $table  Table name
     * @param mixed     $id     ID value
     * @return array|bool   A result row or false when an error occurs
     */
    public function selectRecordWithId($table, $id)
    {
        return $this->selectFirstRecord("SELECT * FROM $table WHERE `".$this->idFieldName."`=?", array($id));
    }

    /**
     * Get number of rows
     *
     * @param string    $table  Table name
     * @param string    $where  Where string
     * @param array     $whereData   Bind parameters array for "where"
     * @return bool|int Number of rows or false when an error occurs
     */
    public function getRowCount($table, $where="", $whereData=array())
    {
        if ( $where )
            $where = "WHERE ".$where;

        $res = $this->selectFirstRecord("SELECT COUNT(*) AS num FROM $table $where", $whereData);

        if ($res === false)
            return false;

        return intval( $res["num"] );
    }

    /**
     * Initiates a transaction
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    /**
     * Commits a transaction
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    /**
     * Rolls back a transaction
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }
}