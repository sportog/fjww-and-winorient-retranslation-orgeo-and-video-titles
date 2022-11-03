<?php

class DataBase {
    public $mysql;
    private $host = "db";
    private $username = "root";
    private $password = "root";
    private $database = "sportog";
    function __construct ( ) {
        $this->mysql = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->mysql->connect_errno)
            return false;
    }
    public function selectOne ( String $sql ) {
        $return = false;
        if ($result = $this->mysql->query($sql)) {
            if ($result->num_rows) {
                while ($row = $result->fetch_object()) {
                    $return = $row;
                }
                $result->close();
                return $return;
            }
        }
        else die('SQL error: ' . $sql);
        return $return;
    }
    public function select ( String $sql , String $key = null ) {
        $return = false;
        if ($result = $this->mysql->query($sql)) {
            if ($result->num_rows) {
                $return = [];
                while ($row = $result->fetch_object()) {
                    if (empty($key))
                        $return[] = $row;
                    else
                        $return[$row->{$key}] = $row;
                }
                $result->close();
                return $return;
            }
        }
        else die('SQL error: ' . $sql);
        return $return;
    }
    public function query ( String $sql ) {
        $return = false;
        if ($this->mysql->query($sql))
            $return = true;
        else die('SQL error: ' . $sql);
        return $return;
    }
    public function insert ( String $sql ) {
        $return = false;
        if ($this->mysql->query($sql))
            $return = $this->mysql->insert_id;
        else die('SQL error: ' . $sql);
        return $return;
    }
}
