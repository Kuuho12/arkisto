<?php
require_once 'class.ai.php';
class AIOpenAI {
    public static $availableModels = [
        "gpt-5.1" => [
            "nimi" => "OpenAI GPT-5.1",
            "link" => "https://developers.openai.com/api/docs/models/gpt-5-1",
            "parametrit" => "Previous intelligent reasoning model for coding and agentic tasks with configurable reasoning effort",
            "sopimus" => false,
            "providers" => [],
            "temperature" => null, // 0-2
            "max_tokens" => 128000
        ],

        "gpt-5-mini" => [
            "nimi" => "OpenAI GPT-5 Mini",
            "link" => "https://developers.openai.com/api/docs/models/gpt-5-mini",
            "parametrit" => "A faster, cost-efficient version of GPT-5 for well-defined tasks",
            "sopimus" => false,
            "providers" => [],
            "temperature" => 1,
            "max_tokens" => 128000
        ],

        "gpt-5-nano" => [
            "nimi" => "OpenAI GPT-5 Nano",
            "link" => "https://developers.openai.com/api/docs/models/gpt-5-nano",
            "parametrit" => "Fastest, most cost-efficient version of GPT-5",
            "sopimus" => false,
            "providers" => [],
            "temperature" => 1,
            "max_tokens" => 128000
        ],

        "gpt-4.1" => [
            "nimi" => "OpenAI GPT-4.1",
            "link" => "https://developers.openai.com/api/docs/models/gpt-4.1",
            "parametrit" => "Smartest non-reasoning model",
            "sopimus" => false,
            "providers" => [],
            "temperature" => null, // 0-2
            "max_tokens" => 32768
        ],

        "gpt-4.1-mini" => [
            "nimi" => "OpenAI GPT-4.1 Mini",
            "link" => "https://developers.openai.com/api/docs/models/gpt-4-1-mini",
            "parametrit" => "Smaller, faster version of GPT-4.1",
            "sopimus" => false,
            "providers" => [],
            "temperature" => null, // 0-2
            "max_tokens" => 32768
        ],

        "gpt-4.1-nano" => [
            "nimi" => "OpenAI GPT-4.1 Nano",
            "link" => "https://developers.openai.com/api/docs/models/gpt-4-1-nano",
            "parametrit" => "Fastest, most cost-efficient version of GPT-4.1",
            "sopimus" => false,
            "providers" => [],
            "temperature" => null, // 0-2
            "max_tokens" => 32768
        ],

        "o3" => [
            "nimi" => "OpenAI o3",
            "link" => "https://developers.openai.com/api/docs/models/o3",
            "parametrit" => "Reasoning model for complex tasks, succeeded by GPT-5",
            "sopimus" => false,
            "providers" => [],
            "temperature" => 1,
            "max_tokens" => 100000
        ],

        "o4-mini" => [
            "nimi" => "OpenAI o4-mini",
            "link" => "https://developers.openai.com/api/docs/models/o4-mini",
            "parametrit" => "Fast, cost-efficient reasoning model, succeeded by GPT-5 mini",
            "sopimus" => false,
            "providers" => [],
            "temperature" => 1,
            "max_tokens" => 100000
        ],

        "o3-mini" => [
            "nimi" => "OpenAI o3-mini",
            "link" => "https://developers.openai.com/api/docs/models/o3-mini",
            "parametrit" => "A small model alternative to o3",
            "sopimus" => false,
            "providers" => [],
            "temperature" => 1, //Api valehtelee muilla arvoilla, ettei parametria muka tueta. Jos ei tuettaisi, niin arvoksi false, osan koodista valmistelin sitä arvoa varten
            "max_tokens" => 100000
        ],
    ];
    private $AI;
    private $savetoCache;
    private $max_tokens = 250;
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
                'Alkuperäinen otsikko' => ['type' => 'string'],
                'Tekijät' => ['type' => 'array', 'items' => ['type' => 'string']],
                'Tekijöiden organisaatiot' => ['type' => 'array', 'items' => ['type' => 'string']],
                'Lehden nimi' => ['type' => 'string'],
                'Julkaisuvuosi' => ['type' => 'integer'],
                'Esittely' => ['type' => 'string'],
                'Kieli' => ['type' => 'string'],
                'Maksullinen' => ['type' => 'boolean']
            ],
            'required' => ['Alkuperäinen otsikko', 'Tekijät', 'Tekijöiden organisaatiot', 'Lehden nimi', 'Julkaisuvuosi', 'Esittely', 'Kieli', 'Maksullinen']
        ]
    ];
    public function __construct($AIData, ?bool $savetoCache = null) {
        $this->AI = $AIData;
        if(!is_null($savetoCache)) {
            $this->savetoCache = $savetoCache;
        } else {
            $this->savetoCache = $AIData->savetoCache;
        }

        if (is_null($this->AI->model)) {
            $this->AI->model = 'gpt-5-nano';
        }

        $this->temperature = self::$availableModels[$this->AI->model]['temperature'] ?? $this->temperature;
        $this->max_tokens = self::$availableModels[$this->AI->model]['max_tokens'] ?? $this->max_tokens;
    }
    /**
     * Suorittaa tekstihakun tekoälyrajapintaan tai hakee valmiin vastauksen välimuistista.
     * 
     * Koodi aluksi tarkistaa onko juuri samanlaisen haun vastaus välimuistissa. Jos on, se lataa vastauksen tiedostosta ja palauttaa sen.
     * Muuten se suorittaa haun OpenAI API:iin ja tallentaa vastauksen välimuistiin tulevia hakuja varten.
     * Koodi palauttaa listan, jonka esimmäinen osa on boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Määrää haetaanko aiemmin tallennettu vastaus, joka tehtiin samalla promptilla, tiedostolla, mallilla, lämpötilalla ja max_tokens-arvolla
     */
    public function tekstiHaku(string $prompt, ?float $temperature = null, ?int $max_tokens = null, bool $haetaankoAiempi = false)
    {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $promptHash = md5($prompt);
        $tiedostonPolku = $this->AI->temp_dir . '/openai_tekstihaku_' . $promptHash . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            fclose($file);
            //echo "Ladattu välimuistista: " . $tiedostonPolku . "\n";
            return [true, $vastaus];
        }
        try {
            if($this->temperature) {
                $chatParams = [
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_completion_tokens' => $this->max_tokens, //toisin kuin max_completion_tokens, max_tokens laskisi vain näkyvät tokenit, eikä myös ajattelutokeneita, eikä max_tokens toimi uudemmissa malleissa
                'temperature' => $this->temperature
            ];
            }
            else {
                $chatParams = [
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_completion_tokens' => $this->max_tokens
            ];
            }
            $response = $this->AI->client->chat()->create($chatParams);
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
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
        
    }
    function chattays(array $arvot, array $chathistory, ?float $temperature = null, ?int $max_tokens = null) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $prompt = $this->AI->suoritaMuotoilu($arvot);
        try {
            $messages = array_merge($chathistory, [['role' => 'user', 'content' => $prompt]]);
            if($this->temperature) {
                $chatParams = [
                'model' => $this->AI->model,
                'messages' => $messages,
                'max_completion_tokens' => $this->max_tokens,
                'temperature' => $this->temperature
            ];
            }
            else {
                $chatParams = [
                'model' => $this->AI->model,
                'messages' => $messages,
                'max_completion_tokens' => $this->max_tokens
            ];
            }
            $response = $this->AI->client->chat()->create($chatParams);
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
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }

    }
    function suoritaHaku($arvot, ?string $filePath = null, ?float $temperature = null, ?int $max_tokens = null, bool $haetaankoAiempi = false) {
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
                $tiedostonPolku = $this->AI->temp_dir . '/openai_tekstihaku_' . $promptHash . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
                if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
                    $file = fopen($tiedostonPolku, 'r');
                    $vastaus = fread($file, filesize($tiedostonPolku));
                    fclose($file);
                    return [true, $vastaus];
                }
                if($this->temperature) {
                    $chatParams = [
                    'model' => $this->AI->model,
                    'messages' => [
                        [
                            'role' => 'user', 
                            'content' => $prompt
                        ],
                    ],
                    'max_completion_tokens' => $this->max_tokens,
                    'temperature' => $this->temperature
                ];
                }
                else {
                    $chatParams = [
                    'model' => $this->AI->model,
                    'messages' => [
                        [
                            'role' => 'user', 
                            'content' => $prompt
                        ],
                    ],
                    'max_completion_tokens' => $this->max_tokens
                ];
                }
                $response = $this->AI->client->chat()->create($chatParams);
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
                $tiedostonPolku = $this->AI->temp_dir . '/openai_tiedostohaku_' . $promptHash . '_' . $filePathHash . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';

                if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
                    $file = fopen($tiedostonPolku, 'r');
                    $vastaus = fread($file, filesize($tiedostonPolku));
                    fclose($file);
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
                    'max_completion_tokens' => $this->max_tokens,
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
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
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
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Määrää haetaanko aiemmin tallennettu vastaus, joka tehtiin samalla promptilla, tiedostolla, mallilla, lämpötilalla ja max_tokens-arvolla
     */
    function tiedostoHaku1($prompt, string $filePath, ?float $temperature = null, ?int $max_tokens = null, bool $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $promptHash = md5($prompt);
        $filePathHash = md5($filePath);
        $tiedostonPolku = $this->AI->temp_dir . '/openai_tiedostohaku_' . $promptHash . '_' . $filePathHash . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';

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
                'max_completion_tokens' => $this->max_tokens,
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
            if ($e->getCode() === 429) {
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
    function strukturoituHaku(string $prompt, $jsonSchema, ?float $temperature = null, ?int $max_tokens = null, bool $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        $schemaJson = json_encode($this->jsonSchemas[$jsonSchema]);
        $prompt = str_replace("[Schema]", $schemaJson, $prompt);
        $promptHash = md5($prompt);
        $tiedostonPolku = $this->AI->temp_dir . '/openai_structuredhaku_' . $promptHash . '_' . $jsonSchema . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            $parsed = json_decode($vastaus, true);
            fclose($file);
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
                'max_completion_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]);
            $vastaus = $response->choices[0]->message->content;
            $parsed = json_decode($vastaus, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [false, "Invalid JSON response", $vastaus];
            }
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $response->usage->totalTokens];
        } catch (\Exception $e) {
            if ($e->getCode() === 429) {
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
    /**
     * Hakee linkistä sivun koodin, karsii siitä paljon pois ja tekee siitä strukturoidun haun Gemini API:iin käyttäen aiemmin tallennettua JSON-skeemaa.
     * 
     * Koodin on tarkoitus hakea artikkelien tietoja, oletus structure ja ohjeistus sekä artikkelien koodin karsinta on rakennettu tätä varten. Koodi ei kykyne lukemaan AJAX:lla
     * generoitua sivun sisältöä. Sivun koodista karsitaan niin paljon pois, että lehden nimeä ei saata löytyä, mutta testailussa tekoäly aina jotenkin silti löysi sen.
     * 
     * @param string $linkki Nettisivun linkki, jonka sisältö haetaan
     * @param mixed $structure JSON-skeeman nimi, jolla haetaan tallennettu JSON-skeema. Oletuksena "Artikkeli", joka on rakennettu artikkelien tietojen hakua varten.
     * @param string|null $ohjeistus Tekoälylle annettavan promptin alkuun tuleva ohjeistus, joka korvaa oletusohjeistuksen. Oletusohjeistus on rakennettu artikkelien tietojen hakua varten.
     * @param float $temperature Lämpötila, joka vaikuttaa vastauksen luovuuteen (0.0-2.0)
     * @param int|null $max_tokens Maksimimäärä tokeneita, jotka vastauksessa sallitaan
     * @param bool $haetaankoAiempi Boolean, joka kertoo haetaanko aiemmin tallennettu vastaus vai tehdäänkö uusi haku. Oletuksena false, eli tehdään aina uusi haku.
     */
    function linkkiHaku(string $linkki, $structure = null, string|null $ohjeistus = null, ?float $temperature = null, ?int $max_tokens = null, bool $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_tokens)) {
            $this->max_tokens = $max_tokens;
        }
        if($structure == null) {
            $structure = "Artikkeli";
        }
        if($ohjeistus == null) {
            $ohjeistus = ' Hae tiedot artikkelista. Et saa keksiä tietoja, jos niitä ei löydy artikkelista. Alkuperäinen otsikko on meta-tagissa, jos se on annettu. Esittely löytyy artikkkelin alusta. Anna kieli ISO 639:2002 -standardin mukaan. Artikkeli: ' ;
        }
        $valittuStructure = json_encode($this->jsonSchemas[$structure]);
        $structureHash = md5($valittuStructure);
        $linkkiHash = md5($linkki);
        $tiedostonPolku = $this->AI->temp_dir . '/openai_linkkihaku_' . $linkkiHash . '_' . $structureHash . '_' . $this->AI->model . '_' . $this->temperature . '_' . $this->max_tokens . '.txt';
        if(file_exists($tiedostonPolku) && $haetaankoAiempi) {
            $file = fopen($tiedostonPolku, 'r');
            $vastaus = fread($file, filesize($tiedostonPolku));
            $parsed = json_decode($vastaus, true);
            fclose($file);
            return [true, $parsed];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $linkki);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate',  // Include 'br' for Brotli (Cloudflare often uses it)
            'Referer: https://www.google.com/',  // Or a relevant academic site like https://scholar.google.com/
            'Cache-Control: no-cache',
            'DNT: 1',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Increase timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $cookieFile = sys_get_temp_dir() . '/cookies.txt'; 
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);  // Save cookies
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); // Load cookies
        $artikkeli = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if ($httpCode !== 200) {
            $tiedosto = fopen("vastaus.txt", 'w');
            fwrite($tiedosto, $artikkeli);
            fclose($tiedosto);
            return [false, "HTTP $httpCode: Unable to fetch article. Error: " . $error, $httpCode];
        }
        if ($artikkeli === false) {
            return [false, "Linkin lukeminen epäonnistui: " . ($error ?? 'tuntematon virhe'), $httpCode];
        }
        $dom = new DOMDocument();
        // Vältä varoituksia rikkinäisestä HTML:sta
        libxml_use_internal_errors(true);
        $dom->loadHTML($artikkeli, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $ogTitle = '';
        $metaElements = $dom->getElementsByTagName('meta');

        foreach ($metaElements as $meta) {
            $name = $meta->getAttribute('name');
            $property = $meta->getAttribute('property');
            if (strtolower($name) === 'og:title' || strtolower($property) === 'og:title') {
                $ogTitle = $dom->saveHTML($meta);
                break;
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[text()="References"]/parent::* | //script | //table | //footer | //aside | //nav | //style | //img | //picture | //video | //audio | //iframe | //object | //embed');
        $elementsToRemove = [];
        foreach ($elements as $element) {
            $elementsToRemove[] = $element;
        }
        foreach ($elementsToRemove as $element) {
            $element->parentNode->removeChild($element);
        }
        $inside = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $inside .= $dom->saveHTML($child);
            }
        }
        $prompt = "Palauta vastaus JSON-muodossa seuraavan rakenteen mukaisesti: " . $valittuStructure . $ohjeistus . $ogTitle . $inside;

        try {
            $response = $this->AI->client->chat()->create([
                'model' => $this->AI->model,
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ],
                ],
                'max_completion_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]);
            $vastaus = $response->choices[0]->message->content;
            $parsed = json_decode($vastaus, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [false, "Invalid JSON response", $vastaus];
            }
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $response->usage->totalTokens];
        } catch (\Exception $e) {
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }

    function modelExists(?string $modelName = null) {
        if ($modelName === null) {
            $modelName = $this->AI->model;
        }
        $malli = false;
        try {
            $tulos = $this->AI->client->models()->retrieve($modelName);
            $malli = !is_null($tulos);
            return [$malli];
        } catch (\Exception $e) {
            return [$malli, $e->getMessage()];
        }
    }

    function modelWorks(string|null $modelName = null) {
        if ($modelName === null) {
        $modelName = $this->AI->model;
        }
        $malli = false;
        try {
            $malli = !is_null($this->AI->client->models()->retrieve($modelName));
            // Use a minimal prompt to test
            $response = $this->AI->client->chat()->create([
                'model' => $modelName,
                'messages' => [['role' => 'user', 'content' => 'Test']],
                'max_completion_tokens' => 1,  // Minimal to avoid token waste
                'temperature' => 0.0
            ]);
            return [true, null];  // If no exception, model exists
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 404 || $statusCode === 400) {
                return [false, $e->getMessage(), $malli];  // Model/provider not found or invalid
            }
            // Re-throw other errors (e.g., auth/rate limit)
            throw $e;
        } catch (\Throwable $e) {
            return [false, $e->getMessage(), $malli];  // Any other error likely means model doesn't exist
        }
    }
}
?>