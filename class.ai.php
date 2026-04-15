<?php
require_once 'vendor/autoload.php';
class Ai {
    public $apiKey;
    public $api;
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
    public $savetoCache;
    public function __construct($apiKey, $api = "gemini", $model = null, $savetoCache = false)
    {
        if(gettype($apiKey) === 'string') {
            $this->apiKey = $apiKey;
        } else {
            throw new Exception("API key must be a string.");
        }
        if(gettype($model) === 'string') {
            $this->model = $model;
        } else {
            $this->model = null;
        }
        if(gettype($savetoCache) === 'boolean') {
            $this->savetoCache = $savetoCache;
        } else {
            $this->savetoCache = false;
        }
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt["default"];
        $this->setAPi($api);
    }
    public function setAPi($api) {
        if(gettype($api) === 'string') {
            $this->api = $api;
        } else {
            throw new Exception("API must be a string.");
        }
        switch($api) {
        case "huggingface":
            $this->client = OpenAI::factory()
                ->withApiKey($this->apiKey)
                ->withBaseUri('https://router.huggingface.co/v1')
                ->make();
            $this->childClass = new AIHuggingface($this, $this->savetoCache);
            if(is_null($this->model)) {
                $this->model = "google/gemma-3-27b-it:featherless-ai";
            }
            break;
        case "openai":
            $this->client = OpenAI::client($this->apiKey);
            $this->childClass = new AIOpenAI($this, $this->savetoCache);
            if(is_null($this->model)) {
                $this->model = "gpt-5-nano";
            }
            break;
        case "gemini":
            $this->client = \Gemini::client($this->apiKey);
            $this->childClass = new AIGemini($this, $this->savetoCache);
            if(is_null($this->model)) {
                $this->model = "gemini-2.5-flash";
            }
            break;
        }
    }
    public function getApi() {
        return $this->api;
    }
    public function setModel($model) {
        if(gettype($model) === 'string') {
            $this->model = $model;
        } else {
            throw new Exception("Model must be a string.");
        }
    }
    public function getModel() {
        return $this->model;
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tekstihaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     */
    function tekstiHaku(array $arvot, ...$extraParametrit) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $systemInstruction = $arvot["systemInstruction"] ?? null;
        unset($arvot["systemInstruction"]);
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if ($this->api === "gemini") {
            $extraParametrit["systemInstruction"] = $systemInstruction;
        } else {
            $prompt = $systemInstruction ? "$systemInstruction $prompt" : $prompt;
        }
        return $this->childClass->tekstiHaku($prompt, ...$extraParametrit);
    }
    function suoritaMuotoilu(array $arvot) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $systemInstruction = $arvot["systemInstruction"] ?? null;
        unset($arvot["systemInstruction"]);
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if ($this->api === "gemini" && !is_null($systemInstruction)) {
            return [$prompt, $systemInstruction];
        } else {
            $prompt = $systemInstruction ? "$systemInstruction $prompt" : $prompt;
            return $prompt;
        }
    }
    function suoritaHaku(array $arvot, ...$extraParametrit) {
        return $this->childClass->suoritaHaku($arvot, ...$extraParametrit);
    }
    /**
     * Muotoilee promptin esivalmistellun kyselyn pohjalta ja antaa lapsiluokan hoitaa tiedostohaun loppuun.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     * @param object $childClass Lapsiluokka, joka hoitaa haun loppuun
     * @param string $filePath Tiedoston polku, jota saatetaan käytetään lapsiluokasta riippuen tiedoston hakuun
     */
    function tiedostoHaku(array $arvot, ?string $filePath = null, ...$extraParametrit) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $systemInstruction = $arvot["systemInstruction"] ?? null;
        unset($arvot["systemInstruction"]);
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if ($this->api === "gemini") {
            $extraParametrit["systemInstruction"] = $systemInstruction;
        } else {
            $prompt = $systemInstruction ? "$systemInstruction $prompt" : $prompt;
        }
        if (method_exists($this->childClass, 'tiedostoHaku1')) {
            return $this->childClass->tiedostoHaku1($prompt, $filePath, ...$extraParametrit);
        } else {
        return $this->childClass->tiedostoHaku2($prompt, ...$extraParametrit);
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
    function strukturoituHaku(array $arvot, $jsonSchema, ...$extraParametrit) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $systemInstruction = $arvot["systemInstruction"] ?? null;
        unset($arvot["systemInstruction"]);
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if ($this->api === "gemini") {
            $extraParametrit["systemInstruction"] = $systemInstruction;
        } else {
            $prompt = $systemInstruction ? "$systemInstruction $prompt" : $prompt;
        }
        return $this->childClass->strukturoituHaku($prompt, $jsonSchema, ...$extraParametrit);
    }
    function lisaaStructure($schemaName, $jsonSchema) {
        if(method_exists($this->childClass, 'lisaaStructure')) {
            return $this->childClass->lisaaStructure($schemaName, $jsonSchema);
        } else {
            throw new Exception("Lapsiluokalla ei ole lisaaStructure-metodia.");
        }
    }
    function haeStructuret() {
        if(method_exists($this->childClass, 'haeStructuret')) {
            return $this->childClass->haeStructuret();
        } else {
            throw new Exception("Lapsiluokalla ei ole haeStructuret-metodia.");
        }
    }

    function linkkiHaku(string $linkki, $structureName = null, ...$extraParametrit) {
        return $this->childClass->linkkiHaku($linkki, $structureName, ...$extraParametrit);
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
    public function modelExists(?string $modelName = null) {
        if(method_exists($this->childClass, 'modelExists')) {
            return $this->childClass->modelExists($modelName);
        } else {
            throw new Exception("Lapsiluokalla ei ole modelExists-metodia.");
        }
    }
    public function modelWorks(?string $modelName = null) {
        if(method_exists($this->childClass, 'modelWorks')) {
            return $this->childClass->modelWorks($modelName);
        } else {
            throw new Exception("Lapsiluokalla ei ole modelWorks-metodia.");
        }
    }
}
?>