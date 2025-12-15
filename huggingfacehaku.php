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
    function tekstiHaku($arvot, $max_tokens = null, $temperature = 0.8) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
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
        return [true, $vastaus];
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
     * Suorittaa strukturoidun haun annetun JSON-skeeman perusteella.
     * 
     * Toimii vain tietyillä malleilla, kuten Qwen-3.2:lla ("Qwen/Qwen3-32B:groq")
     */
    function StructuredHaku($arvot, $jsonSchema, $max_tokens = null, $temperature = 0.0) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
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