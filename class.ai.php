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
    public function tekstiHaku($arvot, $childClass) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $childClass->tekstiHaku2($prompt);
    }
    function tiedostoHaku($arvot, $childClass, $filePath = null) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        if (method_exists($childClass, 'tiedostoHaku')) {
            return $childClass->tiedostoHaku($prompt, $filePath);
        } else {
        return $childClass->tiedostoHaku2($prompt);
        }
    }
    function strukturoituHaku($arvot, $childClass, $jsonSchema) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        return $childClass->strukturoituHaku2($prompt, $jsonSchema);
    }
    public function valitseKysely($kyselyNimi) {
        if(array_key_exists($kyselyNimi, $this->esivalmistellutKyselyt)) {
            $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt[$kyselyNimi];
            return true;
        }
        return false;
    }
    public function lisaaKysely($kyselyNimi, $kyselyTeksti) {
        $this->esivalmistellutKyselyt[$kyselyNimi] = $kyselyTeksti;
        return true;
    }

}
?>