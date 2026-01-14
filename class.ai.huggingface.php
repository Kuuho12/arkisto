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
    /**
     * Suorittaa tekstihakun tekoälyrajapintaan tai hakee valmiin vastauksen välimuistista.
     * 
     * Koodi aluksi tarkistaa onko juuri samanlaisen haun vastaus välimuistissa. Jos on, se lataa vastauksen tiedostosta ja palauttaa sen.
     * Muuten se suorittaa haun Hugging Face API:iin ja tallentaa vastauksen välimuistiin tulevia hakuja varten.
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-1.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     */
    public function tekstiHaku2($prompt, $temperature = 0.8, $max_tokens = null)
    {
        $base64Prompt = str_replace("/", "-", base64_encode($prompt));
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
    function suoritaHaku($arvot, $filePath = null, $temperature = 0.8, $max_tokens = null) {
        $prompt = parent::suoritaHaku($arvot);
        try {
            if ($filePath == null) {
                $base64Prompt = str_replace("/", "-", base64_encode($prompt));
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
            } else {
                if (!file_exists($filePath)) {
                    return [false, "Tiedostoa ei löytynyt: " . $filePath];
                }
                $mimeType = mime_content_type($filePath);

                $base64Prompt = str_replace("/", "-", base64_encode($prompt));
                $base64FilePath = str_replace("/", "-", base64_encode($filePath));
                $parts = explode(':', $this->model);
                $model = str_replace("/", "-", $parts[0]);
                $tiedostonPolku = 'temp_ai/hf_tiedostohaku_' . $base64Prompt . '_' . $base64FilePath . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';

                if(file_exists($tiedostonPolku)) {
                    $file = fopen($tiedostonPolku, 'r');
                    $vastaus = fread($file, filesize($tiedostonPolku));
                    fclose($file);
                    //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
                    return [true, $vastaus];
                }
                if (strpos($mimeType, 'text/') === 0) {
                    // Text file: read content and append to prompt
                    $content = file_get_contents($filePath);
                    $prompt = $prompt . "\n\n" . $content;
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
            }
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
     * Suorittaa tiedostohaun tekoälyrajapintaan tai hakee valmiin vastauksen, tukee teksti- ja kuva-tiedostoja.
     * 
     * Toimii vain tietyillä malleilla (esim. google/gemma-3-27b-it:nebius tai zai-org/GLM-4.6V-Flash:novita)
     * Koodi aluksi tarkistaa onko juuri samanlaisen haun vastaus välimuistissa. Jos on, se lataa vastauksen tiedostosta ja palauttaa sen.
     * Muuten se suorittaa haun Hugging Face API:iin ja tallentaa vastauksen välimuistiin tulevia hakuja varten.
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param string $filePath Tiedoston polku, josta tiedosto haetaan
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-1.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     */
    function tiedostoHaku1($prompt, $filePath, $temperature = 0.8, $max_tokens = null) {
        if (!file_exists($filePath)) {
            return [false, "Tiedostoa ei löytynyt: " . $filePath];
        }
        $mimeType = mime_content_type($filePath);

        $base64Prompt = str_replace("/", "-", base64_encode($prompt));
        $base64FilePath = str_replace("/", "-", base64_encode($filePath));
        $parts = explode(':', $this->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = 'temp_ai/hf_tiedostohaku_' . $base64Prompt . '_' . $base64FilePath . '_' . $model . '_' . $temperature . '_' . $max_tokens . '.txt';

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
                $prompt = $prompt . "\n\n" . $content;
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
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-1.0). Suositellaan pitämään arvossa 0.0, jotta tekoäly noudattaa JSON-skeemaa
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     */
    function strukturoituHaku2($prompt, $jsonSchema, $temperature = 0.0, $max_tokens = null) {
        $base64Prompt = str_replace("/", "-", base64_encode($prompt));
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
    /**
     * Lisaa JSON-skeeman $jsonSchemas-listaan
     * 
     * @param string $jsonSchemaAvain JSON-skeeman avain, jolla skeema tallennetaan listaan
     * @param array $jsonSchema JSON-skeema listamuodossa
     */
    function lisaaStructure($jsonSchemaAvain, $jsonSchema) {
        $this->jsonSchemas[$jsonSchemaAvain] = $jsonSchema;
    }
    /**
     * Palauttaa tallennetut JSON-skeemat
     */
    function haeStructuret() {
        return $this->jsonSchemas;
    }
    function modelExists($modelName = null) {
        if ($modelName === null) {
        $modelName = $this->model;
        }
        try {
            // Use a minimal prompt to test
            $response = $this->client->chat()->create([
                'model' => $modelName,
                'messages' => [['role' => 'user', 'content' => 'Test']],
                'max_tokens' => 1,  // Minimal to avoid token waste
                'temperature' => 0.0
            ]);
            return [true, null];  // If no exception, model exists
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 404 || $statusCode === 400) {
                return [false, $e->getMessage()];  // Model/provider not found or invalid
            }
            // Re-throw other errors (e.g., auth/rate limit)
            throw $e;
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];  // Any other error likely means model doesn't exist
        }
    }
}
?>