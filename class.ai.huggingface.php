<?php
require_once 'class.ai.php';
class AIHuggingface {
    private $AI;
    private $savetoCache;
    private $max_tokens = 5000;
    private $temperature = 0.8;
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
        ],
        "Artikkeli" => [
            'type' => 'object',
            'properties' => [
                'Otsikko' => ['type' => 'string'],
                'Tekijät' => ['type' => 'array', 'items' => ['type' => 'string']],
                'Tekijöiden organisaatiot' => ['type' => 'array', 'items' => ['type' => 'string']],
                'Lehden nimi' => ['type' => 'string'],
                'Julkaisuvuosi' => ['type' => 'integer'],
                'Esittely' => ['type' => 'string'],
                'Kieli' => ['type' => 'string'],
                'Maksullinen' => ['type' => 'boolean']
            ],
            'required' => ['Otsikko', 'Tekijät', 'Tekijöiden organisaatiot', 'Lehden nimi', 'Julkaisuvuosi', 'Esittely', 'Kieli', 'Maksullinen']
        ]
    ];
    public function __construct($AIData, $savetoCache = null) { /*$apiKey, $model = "deepseek-ai/DeepSeek-V3.2:novita"*/
        $this->AI = $AIData;
        if(!is_null($savetoCache)) {
            $this->savetoCache = $savetoCache;
        } else {
            $this->savetoCache = $AIData->savetoCache;
        }
    }
    /**
     * Suorittaa tekstihakun tekoälyrajapintaan tai hakee valmiin vastauksen välimuistista.
     * 
     * Koodi aluksi tarkistaa onko juuri samanlaisen haun vastaus välimuistissa. Jos on, se lataa vastauksen tiedostosta ja palauttaa sen.
     * Muuten se suorittaa haun Hugging Face API:iin ja tallentaa vastauksen välimuistiin tulevia hakuja varten.
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Määrää haetaanko aiemmin tallennettu vastaus, joka tehtiin samalla promptilla, tiedostolla, mallilla, lämpötilalla ja max_tokens-arvolla
     */
    public function tekstiHaku($prompt, $temperature = null, $max_tokens = null, $haetaankoAiempi = false)
    {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $promptHash = md5($prompt);
        $parts = explode(':', $this->AI->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = $this->AI->temp_dir . '/hf_tekstihaku_' . $promptHash . '_' . $model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $vastaus];
        }
        try {
            $response = $this->AI->client->chat()->create([
            'model' => $this->AI->model,
            'messages' => [
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'n' => 1
        ]);
        $vastaus = $response->choices[0]->message->content;
        if($this->savetoCache) {
            $file = fopen($tiedostonPolku, 'w');
            fwrite($file, $vastaus);
            fclose($file);
        }
        return [true, $vastaus, "total_tokens" => $response->usage->totalTokens];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
        
    }
    function chattays($arvot, $chathistory, $temperature = null, $max_tokens = null) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $prompt = $this->AI->suoritaMuotoilu($arvot);
        try {
            $messages = array_merge($chathistory, [['role' => 'user', 'content' => $prompt]]);
            $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => $messages,
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
            ]);
            $vastaus = $response->choices[0]->message->content;
            $chathistory = array_merge($chathistory, [
                ['role' => 'user', 'content' => $prompt],
                ['role' => 'assistant', 'content' => $vastaus]
            ]);
            return [true, $vastaus, $chathistory];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }

    }
    function suoritaHaku($arvot, $filePath = null, $temperature = null, $max_tokens = null, $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $prompt = $this->AI->suoritaMuotoilu($arvot);
        try {
            if ($filePath == null) {
                $promptHash = md5($prompt);
                $parts = explode(':', $this->AI->model);
                $model = str_replace("/", "-", $parts[0]);
                $tiedostonPolku = $this->AI->temp_dir . '/hf_tekstihaku_' . $promptHash . '_' . $model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
                if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
                    $file = fopen($tiedostonPolku, 'r');
                    $vastaus = fread($file, filesize($tiedostonPolku));
                    fclose($file);
                    //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
                    return [true, $vastaus];
                }
                $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
                'n' => 1
                ]);
                $vastaus = $response->choices[0]->message->content;
                if($this->savetoCache) {
                    $file = fopen($tiedostonPolku, 'w');
                    fwrite($file, $vastaus);
                    fclose($file);
                }
                return [true, $vastaus, "total_tokens" => $response->usage->totalTokens];
            } else {
                $promptHash = md5($prompt);
                $filePathHash = md5($filePath);
                $parts = explode(':', $this->AI->model);
                $model = str_replace("/", "-", $parts[0]);
                $tiedostonPolku = $this->AI->temp_dir . '/hf_tiedostohaku_' . $promptHash . '_' . $filePathHash . '_' . $model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';

                if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
                    $file = fopen($tiedostonPolku, 'r');
                    $vastaus = fread($file, filesize($tiedostonPolku));
                    fclose($file);
                    //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
                    return [true, $vastaus];
                }

                if (!file_exists($filePath)) {
                    return [false, "Tiedostoa ei löytynyt: " . $filePath];
                }
                $mimeType = mime_content_type($filePath);

                if (strpos($mimeType, 'text/') === 0 or strpos($mimeType, 'application/json') === 0) {
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
                $response = $this->AI->client->chat()->create([
                    'model' => $this->AI->model,
                    'messages' => $messages,
                    'max_tokens' => $this->max_tokens,
                    'temperature' => $this->temperature,
                ]);
                $vastaus = $response->choices[0]->message->content;
                if($this->savetoCache) {
                    $file = fopen($tiedostonPolku, 'w');
                    fwrite($file, $vastaus);
                    fclose($file);
                }
                return [true, $vastaus, "total_tokens" => $response->usage->totalTokens];
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }
    /**
     * Suorittaa tiedostohaun tekoälyrajapintaan tai hakee valmiin vastauksen, tukee teksti- ja kuva-tiedostoja.
     * 
     * Toimii vain tietyillä malleilla (esim. google/gemma-3-27b-it:featherless-ai tai zai-org/GLM-4.6V-Flash:novita)
     * Koodi aluksi tarkistaa onko juuri samanlaisen haun vastaus välimuistissa. Jos on, se lataa vastauksen tiedostosta ja palauttaa sen.
     * Muuten se suorittaa haun Hugging Face API:iin ja tallentaa vastauksen välimuistiin tulevia hakuja varten.
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param string $filePath Tiedoston polku, josta tiedosto haetaan
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Määrää haetaanko aiemmin tallennettu vastaus, joka tehtiin samalla promptilla, tiedostolla, mallilla, lämpötilalla ja max_tokens-arvolla
     */
    function tiedostoHaku1($prompt, $filePath, $temperature = null, $max_tokens = null, $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $promptHash = md5($prompt);
        $filePathHash = md5($filePath);
        $parts = explode(':', $this->AI->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = $this->AI->temp_dir . '/hf_tiedostohaku_' . $promptHash . '_' . $filePathHash . '_' . $model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';

        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $vastaus];
        }

        if (!file_exists($filePath)) {
            return [false, "Tiedostoa ei löytynyt: " . $filePath];
        }
        $mimeType = mime_content_type($filePath);

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
            $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => $messages,
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
            ]);
            $vastaus = $response->choices[0]->message->content;
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $vastaus, "total_tokens" => $response->usage->totalTokens];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
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
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0). Suositellaan pitämään arvossa 0.0, jotta tekoäly noudattaa JSON-skeemaa
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Määrää haetaanko aiemmin tallennettu vastaus, joka tehtiin samalla promptilla, tiedostolla, mallilla, lämpötilalla ja max_tokens-arvolla
     */
    function strukturoituHaku($prompt, $jsonSchema, $temperature = 0.0, $max_tokens = null, $haetaankoAiempi = false) {
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $schemaJson = json_encode($this->jsonSchemas[$jsonSchema]);
        $prompt = str_replace("[Schema]", $schemaJson, $prompt);
        $promptHash = md5($prompt);
        $parts = explode(':', $this->AI->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = $this->AI->temp_dir . '/hf_structuredhaku_' . $promptHash . '_' . $jsonSchema . '_' . $model . '_' . $temperature . '_' . $this->max_tokens . '.txt';
        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            $parsed = json_decode($vastaus, true);
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $parsed];
        }
        try {
            $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => $this->max_tokens,
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
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $response->usage->totalTokens];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
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
    function poistaStructure($jsonSchemaAvain) {
        if(isset($this->jsonSchemas[$jsonSchemaAvain])) {
            unset($this->jsonSchemas[$jsonSchemaAvain]);
            return [true, "Structure '$jsonSchemaAvain' poistettu."];
        } else {
            return [false, "Structurea '$jsonSchemaAvain' ei löydy."];
        }
    }
    /**
     * Palauttaa tallennetut JSON-skeemat
     */
    function haeStructuret() {
        return $this->jsonSchemas;
    }
    function haeStructure($jsonSchemaAvain) {
        if(isset($this->jsonSchemas[$jsonSchemaAvain])) {
            return [true, $this->jsonSchemas[$jsonSchemaAvain]];
        } else {
            return [false, "Structurea '$jsonSchemaAvain' ei löydy."];
        }
    }

    function linkkiHaku(string $linkki, $structure = null, $temperature = 0.0, $max_tokens = null, $haetaankoAiempi = false) { // Useimmat lehtisivut ovat liian pitkiä, jotta tämä toimisi
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        if($structure == null) {
            $structure = "Artikkeli";
        }
        $valittuStructure = json_encode($this->jsonSchemas[$structure]);
        $structureHash = md5($valittuStructure);
        $linkkiHash = md5($linkki);
        $parts = explode(':', $this->AI->model);
        $model = str_replace("/", "-", $parts[0]);
        $tiedostonPolku = $this->AI->temp_dir . "/hf_linkkihaku_" . $linkkiHash . "_". $structureHash . "_" . $model . "_" . $temperature . '_' . $this->max_tokens . ".txt";
        if($haetaankoAiempi && file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, "r");
            $contents = fread($file, filesize($tiedostonPolku));
            fclose($file);
            return [true, $contents];
        }

        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: fi-FI,fi;q=0.9,en-US;q=0.8,en;q=0.7'
                ]),
                'timeout' => 20
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];
        $context = stream_context_create($opts);
        $artikkeli = @file_get_contents($linkki, false, $context);
        if ($artikkeli === false) {
            $error = error_get_last();
            return [false, "Linkin lukeminen epäonnistui: " . ($error['message'] ?? 'tuntematon virhe')];
        }
        $dom = new DOMDocument();
        // Vältä varoituksia rikkinäisestä HTML:sta
        libxml_use_internal_errors(true);
        $dom->loadHTML($artikkeli, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        $inside = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $inside .= $dom->saveHTML($child);
            }
        }

        $prompt = "Palauta vastaus JSON-muodossa seuraavan rakenteen mukaisesti: " . $valittuStructure . " Hae tiedot artikkelista. Et saa keksiä tietoja, jos niitä ei löydy artikkelista. Esittely löytyy artikkkelin alusta. Artikkeli: " . $inside;
        try {
            $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => $this->max_tokens,
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
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $response->usage->totalTokens];
        } catch (\Exception $e) {
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }

    function modelExists($modelName = null) {
        if ($modelName === null) {
        $modelName = $this->AI->model;
        }
        try {
            // Use a minimal prompt to test
            $response = $this->AI->client->chat()->create([
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