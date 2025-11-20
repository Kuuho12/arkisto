<?php
/**
 * Palauttaa tulostetun artikkelin
 * 
 * @param array $row Tietokannasta haettu artikkeli
 */
function tulostaArtikkeli($row)
{
    ob_start();
    require_once 'views/tulostaArtikkeli.php';
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
    require_once 'views/tulostaListatus.php';
    $listaus = ob_get_clean();
    ob_start();
    require_once 'views/tulostaLinkit.php';
    $linkit = ob_get_clean();
    return ['listaus' => $listaus, 'linkit' => $linkit];
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
    public $conn;
    function __construct()
    {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
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
}
