<?php
require_once 'vendor/autoload.php';
class Ai {
    public $apiKey;
    public $client;
    public $model;
    public $childClass;
    public $esivalmistellutKyselyt = [
        "default" => "%1",
        "json_structured" => "%1\n\nPalauta vastaus JSON-muodossa seuraavan rakenteen mukaisesti: [Schema]",
        "tiivistelma" => "%1\n\nTee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi. %2", 
        "seo" => "%1\n\nAnna lista ehdotuksia %1 hakukoneoptimointiin yllä olevasta artikkelista suomeksi. %2"
    ];
    public $valittuEsivalmisteltuKysely;
    public $files = array();
    public $temp_dir = "temp_ai";
    public function __construct($apiKey, $api = "gemini", $model = null)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt["default"];
        if($api === "huggingface") {
            $this->client = OpenAI::factory()
                ->withApiKey($this->apiKey)
                ->withBaseUri('https://router.huggingface.co/v1')
                ->make();
            $this->childClass = new AIHuggingface($this);
        }
        else if ($api === "openai") {
            $this->client = OpenAI::client($this->apiKey);
            $this->childClass = new AIOpenAI($this);
        }
        else if ($api === "gemini") {
            $this->client = \Gemini::client($this->apiKey);
            $this->childClass = new AIGemini($this);
        }
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tekstihaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     */
    function tekstiHaku($arvot, ...$extraParametrit) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $this->childClass->tekstiHaku($prompt, ...$extraParametrit);
    }
    function suoritaMuotoilu($arvot) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $prompt;
    }
    function suoritaHaku($arvot) {
        return $this->childClass->suoritaHaku($arvot);
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tiedostohaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     * @param string $filePath Tiedoston polku, jota saatetaan käytetään lapsiluokasta riippuen tiedoston hakuun
     */
    function tiedostoHaku($arvot, $filePath = null, ...$extraParametrit) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if (method_exists($this->childClass, 'tiedostoHaku1')) {
            return $this->childClass->tiedostoHaku1($prompt, $filePath, ...$extraParametrit);
        } else {
        return $this->childClass->tiedostoHaku2($prompt);
        }
    }
    /**
     * Lisaa tiedoston tai tiedostoja objektin $files-listaan
     * 
     * Tiedoston avain ja arvo ovat identiset.
     * 
     * @param string $tiedosto Tiedoston suhteellinen polku (relative path)
     */
    public function lisaaTiedosto ($tiedosto) {
        if(is_array($tiedosto)) {
            for($x = 0; $x < count($tiedosto); $x++) {
                $this->files[$tiedosto[$x]] = $tiedosto[$x];
            }
        } else {
        $this->files[$tiedosto] = $tiedosto;
        }
    }
    /**
     * Poistaa tiedoston objektin $files-listasta
     */
    public function poistaTiedosto ($tiedosto) {
        unset($this->files[$tiedosto]);
    }
    /**
     * Palauttaa objektin $files-listan
     */
    public function haeTiedostot () {
        return $this->files;
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa strukturoidun haun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     * @param string $jsonSchema JSON-skeeman nimi, jolla haetaan lapsiluokan julkisesta listasta JSON-skeema
     */
    function strukturoituHaku($arvot, $jsonSchema) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $this->childClass->strukturoituHaku($prompt, $jsonSchema);
    }
    /**
     * Valitsee esivalmistellun kyselyn avaimella. Palauttaa true, jos kysely löytyi ja valittiin, muuten false.
     */
    public function valitseKysely($kyselyNimi) {
        if(array_key_exists($kyselyNimi, $this->esivalmistellutKyselyt)) {
            $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt[$kyselyNimi];
            return true;
        }
        return false;
    }
    /**
     * Lisää esivalmistellun kyselyn.
     */
    public function lisaaKysely($kyselyNimi, $kyselyTeksti) {
        $this->esivalmistellutKyselyt[$kyselyNimi] = $kyselyTeksti;
        return true;
    }

}
?>