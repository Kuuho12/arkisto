<?php
require_once 'class.ai.php';
class AIHuggingface extends AI {
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
                        'ingredients' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'cooking_time_in_minutes' => ['type' => 'integer']
                    ],
                    'required' => ['recipe_name', 'ingredients', 'cooking_time_in_minutes']
                ]
            ]
        ],
        'required' => ['recipes']
    ]
    ];
    public function __construct($apiKey, $model = "deepseek-ai/DeepSeek-V3.2:novita") {
        parent::__construct($apiKey, "huggingface", $model);
    }
    public function tekstiHaku2($prompt, $temperature = 0.8, $max_tokens = null)
    {
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
    function tiedostoHaku($tekstiosa, $filePath, $temperature = 0.8, $max_tokens = null) {
        if (!file_exists($filePath)) {
            return [false, "Tiedostoa ei löytynyt: " . $filePath];
        }
        $mimeType = mime_content_type($filePath);
        $prompt = $tekstiosa;

        $base64Prompt = base64_encode($prompt);
        $parts = explode(':', $this->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = 'temp_ai/hf_tiedostohaku_' . $base64Prompt . '_' . $filePath . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';

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
    function strukturoituHaku2($prompt, $jsonSchema, $temperature = 0.0, $max_tokens = null) {
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
        $prompt = str_replace("[Schema]", $schemaJson, $prompt); // Muistaakseni, jos tämän teki ennen välimuistutusta, tiedostopolusta tuli liian pitkä
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