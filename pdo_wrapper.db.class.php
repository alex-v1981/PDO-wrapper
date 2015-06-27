<?php

class PDOWrapper
{
    private $dbh;
    private $exitAfterError;
    private $idFieldName = "id";
    private $lastError = false;

    public static function openSqlite($dbFilePath, $exitAfterError=false)
    {
        return new PDOWrapper("sqlite:".$dbFilePath, "", "", $exitAfterError);
    }

    public static function openMysql($host, $dbName, $username, $password, $exitAfterError=false, $charset=false)
    {
        $wrapper = new PDOWrapper("mysql:host=$host;dbname=$dbName", $username, $password, $exitAfterError);

        if (!$wrapper->getLastError() && $charset)
            $wrapper->query("SET NAMES ?", array($charset));

        return $wrapper;
    }

    function __construct($dsn, $username, $password, $exitAfterError)
    {
        $this->exitAfterError = $exitAfterError;

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

    private function clearError()
    {
        $this->lastError = false;
    }

    private function error($errorMsg=false)
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

        if ( $this->exitAfterError )
            exit( $this->getLastError() );
    }

    public function setIdFieldName( $idFieldName )
    {
        if ( $idFieldName )
            $this->idFieldName = $idFieldName;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function query($sql, $data=array())
    {
        $this->clearError();

        try
        {
            $sth = $this->dbh->prepare( $sql );
            $sth->execute( $data );
        }
        catch (PDOException $e)
        {
            $sth = false;
            $this->error( $e->getMessage() );
        }

        return $sth;
    }

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

    public function updateRecordWithId($table, $fieldAndValueList, $id)
    {
        return $this->updateRecord($table, $fieldAndValueList, '`'.$this->idFieldName.'`=?', array($id));
    }

    public function deleteRecord($table, $where="", $data=array())
    {
        if ( $where )
            $where = "WHERE ".$where;

        $res = $this->query("DELETE FROM $table $where", $data);

        return $res ? true : false;
    }

    public function deleteRecordWithId($table, $id)
    {
        return $this->deleteRecord($table, '`'.$this->idFieldName.'`=?', array($id));
    }

    public function selectRecords($sql, $data=array())
    {
        $sth = $this->query($sql, $data);

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

    public function selectOneRecord($sql, $data=array())
    {
        $sth = $this->query($sql, $data);

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

    public function selectOneRecordWithId($table, $id)
    {
        return $this->selectOneRecord("SELECT * FROM $table WHERE `".$this->idFieldName."`=?", array($id));
    }

    public function getRowCount($table, $where="", $data=array())
    {
        if ( $where )
            $where = "WHERE ".$where;

        $res = $this->selectOneRecord("SELECT COUNT(*) AS num FROM $table $where", $data);

        if ($res === false)
            return false;

        return intval( $res["num"] );
    }

    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }
}