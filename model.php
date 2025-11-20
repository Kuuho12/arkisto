<?php
class Viewtulostukset {
    function tulostaLinkit($taulunPituus, $listaMaara) {

    }
}
class SQLModel {
    public $servername;
    public $username;
    public $password;
    public $dbname;
    public $tableName;
    public $conn;
    function __construct($servername, $username, $password, $dbname, $tableName)
    {
        $this->servername = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->tableName = $tableName;
        $this->conn = new mysqli($servername, $username, $password, $dbname);
    }
    function haeArtikkeliIdlla($id) {
        $sql = "SELECT * FROM $this->tableName WHERE id = " . intval($id);
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
    function haeListatus($maara, $sivu) {
        $offset = ($sivu - 1) * $maara;
        $sql = "SELECT * FROM $this->tableName ORDER BY aika DESC LIMIT " . intval($maara) . " OFFSET " . intval($offset);
        $result = $this->conn->query($sql);
        $artikkelit = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $artikkelit[] = $row;
            }
        }
        return $artikkelit;

    }
    function haeCount() {
        $sql = "SELECT COUNT(*) as count FROM $this->tableName";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return intval($row['count']);
        } else {
            return 0;
        }
    }
}
?>