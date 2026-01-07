<?php 
require_once 'vendor/autoload.php';
class HFHaku {
    public $HF_Token;
    public $hfBaseUrl = 'https://router.huggingface.co/v1';
    public $client;
    public $model;
    public $jsonSchemas = [
        "reseptit" => [
        'type' => 'object',
        'properties' => [
            'recipes' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'recipe_name' => ['type' => 'string'],
                        'cooking_time_in_minutes' => ['type' => 'integer']
                    ],
                    'required' => ['recipe_name', 'cooking_time_in_minutes']
                ]
            ]
        ],
        'required' => ['recipes']
    ]
    ];
    public $esivalmistellutKyselyt = [
        "default" => "%1",
        "json_structured" => "%1\n\nPalauta vastaus JSON-muodossa seuraavan rakenteen mukaisesti: [Schema]",
        "tiivistelma" => "%1\n\nTee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi. %2", 
        "seo" => "%1\n\nAnna lista ehdotuksia %1 hakukoneoptimointiin yllä olevasta artikkelista suomeksi. %2"
    ];
    public $valittuEsivalmisteltuKysely;

    public function __construct($model = "deepseek-ai/DeepSeek-V3.2:novita")
    {
        $this->HF_Token = getenv('HF_TOKEN');
        $this->model = $model;
        $this->client = OpenAI::factory()
            ->withApiKey($this->HF_Token)
            ->withBaseUri($this->hfBaseUrl)
            ->make();
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt["default"];
    }
    function tekstiHaku($arvot, $temperature = 0.8, $max_tokens = null) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        $base64Prompt = base64_encode($prompt);
        $parts = explode(':', $this->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = 'temp_ai/hf_tekstihaku_' . $base64Prompt . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';
        if(file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $vastaus];
        }
        try {
            $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
        ]);
        $vastaus = $response->choices[0]->message->content;
        /*$vastaus = "";
        foreach($response->choices as $choice) {
            $vastaus .= $choice->message->content;
            var_dump($choice);
        }*/
        $file = fopen($tiedostonPolku, 'w');
        fwrite($file, $vastaus);
        fclose($file);
        return [true, $vastaus];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        $statusCode = $e->getResponse()->getStatusCode();
        if ($statusCode === 429) {
            return [false, "Rate limit exceeded. Please try again later."];
        }
        return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
        return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
        
    }
    /**
     * Valitsee esivalmistellun kyselyn avaimella ja myös palauttaa sen
     */
    function valitseKysely($avain) {
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt[$avain];
        return $this->valittuEsivalmisteltuKysely;
    }
    /**
     * Lisää esivalmistellun kyselyn.
     */
    function lisaaKysely ($avain, $kysely) {
        $this->esivalmistellutKyselyt[$avain] = [$kysely];
    }
    /**
     * Suorittaa tiedostohakun, tukee teksti- ja kuva-tiedostoja.
     * 
     * Toimii vain tietyillä malleilla (esim. google/gemma-3-27b-it:nebius tai zai-org/GLM-4.6V-Flash:novita)
     */
    function tiedostoHaku($tekstiosa, $filePath, $temperature = 0.8, $max_tokens = null) {
        if (!file_exists($filePath)) {
            return [false, "Tiedostoa ei löytynyt: " . $filePath];
        }
        $mimeType = mime_content_type($filePath);
        $prompt = $tekstiosa;

        $base64Prompt = base64_encode($prompt);
        $base64FilePath = base64_encode($filePath);
        $parts = explode(':', $this->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = 'temp_ai/hf_tiedostohaku_' . $base64Prompt . '_' . $base64FilePath  . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';

        if(file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $vastaus];
        }
        try {
            if (strpos($mimeType, 'text/') === 0) {
                // Text file: read content and append to prompt
                $content = file_get_contents($filePath);
                $prompt = $tekstiosa . "\n\n" . $content;
                $messages = [['role' => 'user', 'content' => $prompt]];
            } elseif (strpos($mimeType, 'image/') === 0) {
                // Image file: base64 encode and include as data URI
                $base64 = base64_encode(file_get_contents($filePath));
                $dataUri = "data:$mimeType;base64,$base64";
                $messages = [
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUri]]
                    ]]
                ];
            } else {
                return [false, "Tiedostotyyppiä ei tueta: " . $mimeType];
            }
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
            ]);
            $vastaus = $response->choices[0]->message->content;
            $file = fopen($tiedostonPolku, 'w');
            fwrite($file, $vastaus);
            fclose($file);
            return [true, $vastaus];
        } catch (\Exception $e) {
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
        catch (\Throwable $e) {
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }
    /**
     * Suorittaa strukturoidun haun annetun JSON-skeeman perusteella.
     * 
     * Toimii vain tietyillä malleilla, kuten Qwen-3.2:lla ("Qwen/Qwen3-32B:groq")
     */
    function StructuredHaku($arvot, $jsonSchema, $temperature = 0.0, $max_tokens = null) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
        $base64Prompt = base64_encode($prompt);
        $parts = explode(':', $this->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = 'temp_ai/hf_structuredhaku_' . $base64Prompt . '_' . $jsonSchema . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';
        if(file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            $parsed = json_decode($vastaus, true);
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $parsed];
        }
        $schemaJson = json_encode($this->jsonSchemas[$jsonSchema]);
        $prompt = str_replace("[Schema]", $schemaJson, $prompt);
        try {
            $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'response_format' => [
                'type' => 'json_object'
            ]
        ]);
        $vastaus = $response->choices[0]->message->content;
        $parsed = json_decode($vastaus, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [false, "Invalid JSON response"];
        }
        $file = fopen($tiedostonPolku, 'w');
        fwrite($file, $vastaus);
        fclose($file);
        return [true, $parsed];
        } catch (\Exception $e) {
        return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
    }
    }
    function lisaaStructure($jsonSchemaAvain, $jsonSchema) {
        $this->jsonSchemas[$jsonSchemaAvain] = $jsonSchema;
    }
    function haeStructuret() {
        return $this->jsonSchemas;
    }
}
?>