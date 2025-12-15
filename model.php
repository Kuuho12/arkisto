<?php
/**
 * Palauttaa tulostetun artikkelin
 * 
 * @param array $row Tietokannasta haettu artikkeli
 */
function tulostaArtikkeli($row)
{
    ob_start();
    require_once 'newViews/tulostaArtikkeli.php';
    return ob_get_clean();
}
/**
 * Palauttaa tulostetun listauksen ja linkit
 * 
 * @param array $all_rows Tietokannasta haettu artikkelilistaus
 * @param int $taulunPituus Artikkelien kokonaismäärä
 * @param int $listaMaara Kuinka monta artikkelia näytetään listauksessa (per sivu)
 * @param string $url Nykyinen URL-polku
 * @param int $sivu Nykyinen sivunumero
 * @param int|null $id Artikkelin id, tai null jos ei ole artikkelisivulla
 */
function tulostaListausJaLinkit ($all_rows, $taulunPituus, $listaMaara, $url, $sivu, $id) {
    ob_start();
    require_once 'newViews/tulostaListatus.php';
    $listaus = ob_get_clean();
    ob_start();
    require_once 'newViews/tulostaLinkit.php';
    $linkit = ob_get_clean();
    return ['listaus' => $listaus, 'linkit' => $linkit];
}
function tulostaHallintaListaus ($all_rows, $taulunPituus, $listaMaara, $sivu) {
    ob_start();
    require_once 'newViews/tulostaHallintaListaus.php';
    return ob_get_clean();
}
function tulostaLinkit ($taulunPituus, $listaMaara, $url, $sivu, $id) {
    ob_start();
    require_once 'newViews/tulostaLinkit.php';
    return ob_get_clean();
}
function tulostaInformaatio($taulunPituus, $taulunInformaatio, $taulunMerkisto) {
    ob_start();
    require_once 'newViews/tulostaInformaatio.php';
    return ob_get_clean();
}
function tulostaMuokkausArtikkeli($row, $id, $result) {
    ob_start();
    require_once 'newViews/tulostaMuokkausArtikkeli.php';
    return ob_get_clean();
}
function tulostaLisaysArtikkeli($result) {
    ob_start();
    require_once 'newViews/tulostaLisaysArtikkeli.php';
    return ob_get_clean();
}
/**
 * Käytetään datan hakuun tietokannasta
 * 
 * Dataa haetaan paikallisesta mysql-tietokannasta.
 * Luokka sisältää yhden artikkelin haun id:llä, artikkeli-listauksen haun ja artikkelien kokonaismäärän haun.
 * 
 * @author Kuura Pönkä
 * @copyright 2025 Innowise 
 */
class SQLHaku
{
    public $servername = "localhost";
    public $username = "esimerkki";
    public $password = "ikkremise";
    public $dbname = "localhost";
    public $tablename = "artikkelit3";
    public $backuptablename = "artikkelit4";
    public $conn;
    public $stmt_insert;
    public $stmt_insert_backup;
    function __construct()
    {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        $this->stmt_insert = "INSERT INTO $this->tablename (aika, otsikko, sisalto, kirjoittaja) VALUES (?, ?, ?, ?)";
        $this->stmt_insert_backup = "INSERT INTO $this->backuptablename (aika, otsikko, sisalto, kirjoittaja) VALUES (?, ?, ?, ?)";
    }
    /** 
     * Hakee artikkelin id:n perusteella
     * 
     * @param int $id Artikkelin id
     */
    function haeArtikkeliIdlla($id)
    {
        $sql = "SELECT * FROM $this->tablename WHERE id = " . intval($id);
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
    /**
     * Hakee kyseisen sivun artikkelilistauksen
     * 
     * @param int $maara Kuinka monta artikkelia näytetään listauksessa (per sivu)
     * @param int $sivu Nykyinen sivunumero
     * @param int|null $id Artikkelin id, tai null jos ei ole artikkelisivulla
     */
    function haeListatus($maara, $sivu, $id)
    {
        $offset = ($sivu - 1) * $maara;
        $sql = "SELECT * FROM $this->tablename WHERE id <> " . intval($id) . " ORDER BY aika DESC LIMIT " . intval($maara) . " OFFSET " . intval($offset);
        $result = $this->conn->query($sql);
        $artikkelit = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $artikkelit[] = $row;
            }
        }
        return $artikkelit;
    }
    function lisaaArtikkeli($aika, $otsikko, $sisalto, $kirjoittaja) {
        $sql = "INSERT INTO $this->tablename (aika, otsikko, sisalto, kirjoittaja) VALUES ('$aika', '$otsikko', '$sisalto', '$kirjoittaja')";
        $result = $this->conn->query($sql);
        $sql2 = "INSERT INTO $this->backuptablename (aika, otsikko, sisalto, kirjoittaja) VALUES ('$aika', '$otsikko', '$sisalto', '$kirjoittaja')";
        $result2 = $this->conn->query($sql2);
        return [$result, $result2];
    }
    /**
     * Hakee artikkelien kokonaismäärän
     */
    function haeCount()
    {
        $sql = "SELECT COUNT(*) as count FROM $this->tablename";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return intval($row['count']);
        } else {
            return 0;
        }
    }
    function haeTaulunMerkisto() {
        $sql = "SHOW TABLE STATUS LIKE '$this->tablename'";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['Collation'];
        } else {
            return "Tuntematon merkistö";
        }
    }
    function haeTaulunInformaatio() {
        $sql = "DESCRIBE $this->tablename";
        return $this->conn->query($sql);
    }
    function muokkaaTaulua($id, $aika, $otsikko, $sisalto, $kirjoittaja) {
        $sql = "UPDATE $this->tablename SET aika = '$aika', otsikko = '$otsikko', sisalto = '$sisalto', kirjoittaja = '$kirjoittaja' WHERE id = " . intval($id);
        $result = $this->conn->query($sql);
        return $result;
    }
    function haeBackUpArtikkeli($id) {
        $sql = "SELECT * FROM $this->backuptablename WHERE id = " . intval($id);
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
    function palautaBackUp($id) {
        $sql = "SELECT * FROM $this->backuptablename WHERE id = " . intval($id);
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) { 
            $result = $result->fetch_assoc();
            $aika = $result["aika"];
            $otsikko = $result["otsikko"];
            $sisalto = $result["sisalto"];
            $kirjoittaja = $result["kirjoittaja"];
            $sql = "UPDATE $this->tablename SET aika = '$aika', otsikko = '$otsikko', sisalto = '$sisalto', kirjoittaja = '$kirjoittaja' WHERE id = " . intval($id);
            return $this->conn->query($sql);
        } else {
            return null;
        }
    }
}
