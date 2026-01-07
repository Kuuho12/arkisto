<?php
require_once 'vendor/autoload.php';
class AI {
    public $apiKey;
    public $client;
    public $model;
    public $esivalmistellutKyselyt = [
        "default" => "%1",
        "json_structured" => "%1\n\nPalauta vastaus JSON-muodossa seuraavan rakenteen mukaisesti: [Schema]",
        "tiivistelma" => "%1\n\nTee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi. %2", 
        "seo" => "%1\n\nAnna lista ehdotuksia %1 hakukoneoptimointiin yllä olevasta artikkelista suomeksi. %2"
    ];
    public $valittuEsivalmisteltuKysely;
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
            $this->model = $model;
        }
        else if ($api === "gemini") {
            $this->client = \Gemini::client($this->apiKey);
        }
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tekstihaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     */
    function tekstiHaku($arvot, $childClass) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $childClass->tekstiHaku2($prompt);
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tiedostohaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     * @param string $filePath Tiedoston polku, jota saatetaan käytetään lapsiluokasta riippuen tiedoston hakuun
     */
    function tiedostoHaku($arvot, $childClass, $filePath = null) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if (method_exists($childClass, 'tiedostoHaku1')) {
            return $childClass->tiedostoHaku1($prompt, $filePath);
        } else {
        return $childClass->tiedostoHaku2($prompt);
        }
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa strukturoidun haun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     * @param string $jsonSchema JSON-skeeman nimi, jolla haetaan lapsiluokan julkisesta listasta JSON-skeema
     */
    function strukturoituHaku($arvot, $childClass, $jsonSchema) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $childClass->strukturoituHaku2($prompt, $jsonSchema);
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