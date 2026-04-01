<?php
require_once 'class.ai.php';
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\MimeType;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Enums\FileState;
use Gemini\Data\UploadedFile;
use Gemini\Data\GoogleSearch;
use Gemini\Data\Tool;
class AIGemini {
    private $AI = null;
    private $savetoCache;
    private $max_output_tokens = 5000;
    private $temperature = 1;
    private $systemInstruction = "";
    private $mimeTypes = array(
        "image/png" => MimeType::IMAGE_PNG,
        "audio/mpeg" => MimeType::AUDIO_MP3,
        "image/jpeg" => MimeType::IMAGE_JPEG,
        "image/webp" => MimeType::IMAGE_WEBP,
        "application/json" => MimeType::APPLICATION_JSON,
        "text/csv" => MimeType::TEXT_CSV,
        "text/html" => MimeType::TEXT_HTML,
        "text/plain" => MimeType::TEXT_PLAIN,
        "video/mp4" => MimeType::VIDEO_MP4
    ); 
    public $structured_configs = [];
    public function __construct($AIData, $savetoCache = null, $systemInstruction = "") {
        $this->AI = $AIData;
        if(!is_null($savetoCache)) {
            $this->savetoCache = $savetoCache;
        } else {
            $this->savetoCache = $AIData->savetoCache;
        }
        $this->systemInstruction = $systemInstruction;
        $this->structured_configs = [
            "Artikkeli" => [
                "properties" => [
                    "Alkuperäinen otsikko" => new Schema(type: DataType::STRING),
                    "Tekijät" => new Schema(type: DataType::ARRAY, items: new Schema(type: DataType::STRING)),
                    "Tekijöiden organisaatiot" => new Schema(type: DataType::ARRAY, items: new Schema(type: DataType::STRING)),
                    "Lehden nimi" => new Schema(type: DataType::STRING),
                    "Julkaisuvuosi" => new Schema(type: DataType::INTEGER),
                    "Esittely" => new Schema(type: DataType::STRING),
                    "Kieli" => new Schema(type: DataType::STRING),
                    "Maksullinen" => new Schema(type: DataType::BOOLEAN)
                ], 
                "required" => [
                    "Alkuperäinen otsikko",
                    "Tekijät",
                    "Tekijöiden organisaatiot",
                    "Lehden nimi",
                    "Julkaisuvuosi",
                    "Esittely",
                    "Kieli",
                    "Maksullinen"
                ]
            ]
        ];
    }
    /**
     * Tekee haun Gemini API:iin käyttäen haun pohjana aiemmin valittua esivalmisteltua kyselyä
     * 
     * Esivalmistellusta kyselystä tehdään hakuprompti korvaamalla merkittyihin kohtiin funktioon parametrina annetun listan osat.
     * Esivalmistellun kyselyn voi valita valitseKysely-funktiolla.
     * Lopuksi tehdään haku Gemini API:iin. Onnistuessa palautetaan lista, joka sisältää truen ja tesktivastauksen, epäonnistuessa
     * lista, joka sisältää falsen ja virheilmoituksen.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     */
    public function tekstiHaku(string $prompt, $temperature = null, $max_output_tokens = null, $haetaankoAiempi = false, $systemInstruction = null) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_output_tokens)) {
            $this->max_output_tokens = $max_output_tokens;
        }
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        $promptHash = md5($prompt);
        $sysInsHash = md5($this->systemInstruction);
        $tiedostonPolku = $this->AI->temp_dir . "/gemini_tekstihaku_" . $promptHash . "_" . $sysInsHash . "_" . $this->AI->model . "_" . $this->temperature . "_" . $this->max_output_tokens . ".txt";
        if($haetaankoAiempi && file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, "r");
            $contents = fread($file, filesize($tiedostonPolku));
            fclose($file);
            return [true, $contents, "total_tokens" => null];
        }
        try {
        $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->withGenerationConfig(new GenerationConfig(
                temperature: $this->temperature,
                maxOutputTokens: $this->max_output_tokens))
            ->withSystemInstruction(Content::parse($this->systemInstruction))
            ->generateContent($prompt);
        $vastaus = $result->text();
        if($this->savetoCache) {
            $file = fopen($tiedostonPolku, 'w');
            fwrite($file, $vastaus);
            fclose($file);
        }
        $totalTokens = $result->usageMetadata->totalTokenCount;
        return [true, $vastaus, "total_tokens" => $totalTokens];
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

    function chattays($arvot, $chathistory, $temperature = null, $max_output_tokens = null, $systemInstruction = null) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_output_tokens)) {
            $this->max_output_tokens = $max_output_tokens;
        }
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        $prompt = $this->AI->suoritaMuotoilu($arvot);
        try {
            $chat = $this->AI->client
                ->generativeModel(model: $this->AI->model)
                ->withGenerationConfig(new GenerationConfig(
                    temperature: $this->temperature,
                    maxOutputTokens: $this->max_output_tokens))
                ->withSystemInstruction(Content::parse($this->systemInstruction))
                ->startChat(history: $chathistory);
            $result = $chat->sendMessage($prompt);
            $vastaus = $result->text();
            $chatlogs = [
            Content::parse(part: $prompt, role: Role::USER),
            Content::parse(part: $vastaus, role: Role::MODEL)
            ];
            return [true, $vastaus, $chatlogs];
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
     * 
     */
    function suoritaHaku($arvot, $temperature = null, $max_output_tokens = null, $haetaankoAiempi = false) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_output_tokens)) {
            $this->max_output_tokens = $max_output_tokens;
        }
        list($prompt, $systemInstruction) = $this->AI->suoritaMuotoilu($arvot);
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        try {
            if(count($this->AI->files) > 0) {
                $prompt = [$prompt];
                foreach ($this->AI->files as $tiedosto) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $tiedosto);
                    if (in_array($mime_type, array_keys($this->mimeTypes))) {
                        $geminiMimeType = $this->mimeTypes[$mime_type];
                    } 
                    else {
                        return [false, "Epätuettu tiedostomuoto: " . $mime_type];
                    }
                    $prompt[] = new Blob(
                    mimeType: $geminiMimeType,
                    data: base64_encode(
                        file_get_contents($tiedosto)
                    )
                    );
                }
                $result = $this->AI->client
                    ->generativeModel(model: $this->AI->model)
                    ->withGenerationConfig(new GenerationConfig(
                        temperature: $this->temperature,
                        maxOutputTokens: $this->max_output_tokens))
                    ->withSystemInstruction(Content::parse($this->systemInstruction))
                    ->generateContent($prompt);
                $vastaus = $result->text();
                $totalTokes = $result->usageMetadata->totalTokenCount;
                return [true, $vastaus, "total_tokens" => $totalTokes];
            } else {
                $promptHash = md5($prompt);
                $sysInsHash = md5($this->systemInstruction);
                $tiedostonPolku = $this->AI->temp_dir . "/gemini_tekstihaku_" . $promptHash . "_" . $sysInsHash . "_" . $this->AI->model . "_" . $this->temperature . "_" . $this->max_output_tokens . ".txt";
                if($haetaankoAiempi && file_exists($tiedostonPolku)) {
                    $file = fopen($tiedostonPolku, "r");
                    $contents = fread($file, filesize($tiedostonPolku));
                    fclose($file);
                    return [true, $contents, "total_tokens" => null];
                }

                $result = $this->AI->client
                    ->generativeModel(model: $this->AI->model)
                    ->withGenerationConfig(new GenerationConfig(
                        temperature: $this->temperature,
                        maxOutputTokens: $this->max_output_tokens))
                    ->withSystemInstruction(Content::parse($this->systemInstruction))
                    ->generateContent($prompt);
                $vastaus = $result->text();
                if($this->savetoCache) {
                    $file = fopen($tiedostonPolku, 'w');
                    fwrite($file, $vastaus);
                    fclose($file);
                }
                $totalTokes = $result->usageMetadata->totalTokenCount;
                return [true, $vastaus, "total_tokens" => $totalTokes];
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
     * Tekee haun Gemini API:iin aiemmin tallennetuilla tiedostoilla
     * 
     * Koodi palauttaa listan, jonka esimmäinen osa boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * Koodi looppaa jokaisen tiedoston. Looppauksessa aluksi tarkistetaan jokaisen tiedoston MIME tyyppi. Jos tyyppiä ei tueta, funktio palautuu.
     * Lopuksi looppauksessa tiedoston data tallennetaan blobina prompt-listamuuttujaan. Looppauksen jälkeen tehdään haku Gemini API:iin promptilla.
     * Käsiteltävt tiedostot lisätään lisaaTiedosto-funktiolla
     * 
     *  @param string $prompt Tekoälylle lähetettävä kysely
     */
    function tiedostoHaku2 ($prompt, $temperature = null, $max_output_tokens = null, $systemInstruction = null) {
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_output_tokens)) {
            $this->max_output_tokens = $max_output_tokens;
        }
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        $prompt = [$prompt];
        foreach ($this->AI->files as $tiedosto) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tiedosto);
            if (in_array($mime_type, array_keys($this->mimeTypes))) {
                $geminiMimeType = $this->mimeTypes[$mime_type];
            } 
            else {
                return [false, "Epätuettu tiedostomuoto: " . $mime_type];
            }
            $prompt[] = new Blob(
            mimeType: $geminiMimeType,
            data: base64_encode(
                file_get_contents($tiedosto)
            )
            );
        }
        try {
            $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->withGenerationConfig(new GenerationConfig(
                temperature: $this->temperature,
                maxOutputTokens: $this->max_output_tokens))
            ->withSystemInstruction(Content::parse($this->systemInstruction))
            ->generateContent($prompt);
            $vastaus = $result->text();
            $totalTokens = $result->usageMetadata->totalTokenCount;
            return [true, $vastaus, "total_tokens" => $totalTokens];
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
    function filesApiLisaaTiedosto($tiedosto) {
        if(!file_exists($tiedosto)) {
            return [false, "Tiedostoa ei löydy: " . $tiedosto];
        }
        $fileSuffix = pathinfo($tiedosto, PATHINFO_EXTENSION);
        if($fileSuffix === "txt") {
            $mime_type = "text/plain";
        }
        else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tiedosto);
        }
        if (in_array($mime_type, array_keys($this->mimeTypes))) {
            $geminiMimeType = $this->mimeTypes[$mime_type];
        } 
        else {
            return [false, "Epätuettu tiedostomuoto: " . $mime_type];
        }
        $files = $this->AI->client->files();
        $file = $files->upload(
            filename: $tiedosto,
            mimeType: $geminiMimeType,
            displayName: pathinfo($tiedosto, PATHINFO_FILENAME)
        );
        do {
        sleep(2);
        $meta = $files->metadataGet($file->uri);
        } while (!$meta->state->complete());

        if ($meta->state == FileState::Failed) { 
            return [false, "Tiedoston lataus epäonnistui: " . $meta->error->message];
        } else {
            return [true, $file];
        }
    }
    function filesApiHaku($prompt, $file, $temperature = 1, $max_output_tokens = null, $systemInstruction = null) { //Ei toimi
        if(!is_null($temperature)) {
            $this->temperature = $temperature;
        }
        if(!is_null($max_output_tokens)) {
            $this->max_output_tokens = $max_output_tokens;
        }
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        $prompt = $this->AI->suoritaMuotoilu($prompt);
        try {
            $mime_type = $file->mimeType;
            if (in_array($mime_type, array_keys($this->mimeTypes))) {
                $geminiMimeType = $this->mimeTypes[$mime_type];
            } 
            else {
                return [false, "Epätuettu tiedostomuoto: " . $mime_type];
            }
            $uploadedFile = new UploadedFile(
                fileUri: $file->uri,
                mimeType: $geminiMimeType  // or detect from the file object
            );
            $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->withGenerationConfig(new GenerationConfig(
                temperature: $this->temperature,
                maxOutputTokens: $this->max_output_tokens))
            ->withSystemInstruction(Content::parse($this->systemInstruction))
            ->generateContent([
                $prompt,
                $uploadedFile
            ]);
            $vastaus = $result->text();
            $totalTokes = $result->usageMetadata->totalTokenCount;
            return [true, $vastaus, "total_tokens" => $totalTokes];
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
     * Tekee strukturoidun haun Gemini API:iin käyttäen annettua promptia ja aiemmin tallennettua JSON-skeemaa
     * 
     * Koodi palauttaa listan, jonka esimmäinen osa boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * Koodi hakee tallennetun JSON-skeeman structured_configs-listasta annetulla avaimella. Tämän jälkeen tehdään haku Gemini API:iin
     * käyttäen annettua promptia ja JSON-skeemaa.
     * 
     * @param string $prompt Tekoälylle lähetettävä kysely
     * @param string $structure JSON-skeeman nimi, jolla haetaan tallennettu JSON-skeema
     */
    function strukturoituHaku($prompt, $structure, $haetaankoAiempi = false, $systemInstruction = null) {
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        $valittuStructure = $this->structured_configs[$structure];
        $structureHash = md5(json_encode($valittuStructure));
        $promptHash = md5($prompt);
        $sysInsHash = md5($this->systemInstruction);
        $tiedostonPolku = $this->AI->temp_dir . "/gemini_structuredhaku_" . $promptHash . "_". $structureHash . "_" . $sysInsHash . "_" . $this->AI->model . ".txt";
        if($haetaankoAiempi && file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, "r");
            $contents = fread($file, filesize($tiedostonPolku));
            fclose($file);
            return [true, $contents];
        }
        try {
            $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->withGenerationConfig(
                generationConfig: new GenerationConfig(
                    responseMimeType: ResponseMimeType::APPLICATION_JSON,
                    responseSchema: new Schema(
                        type: DataType::ARRAY,
                        items: new Schema(
                            type: DataType::OBJECT,
                            properties: $valittuStructure["properties"],
                            required: $valittuStructure["required"],
                        )
                    )
                )
            )
            ->withSystemInstruction(Content::parse($this->systemInstruction))
            ->generateContent($prompt);
            $vastaus = $result->text();
            $parsed = json_decode($vastaus, true);
            $totalTokens = $result->usageMetadata->totalTokenCount;
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [false, "Invalid JSON response"];
            }
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $totalTokens];
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
    function datatyping($tyyppi) {
        if(gettype(($tyyppi)) == "array") { 
            return new Schema(type: DataType::ARRAY, items: self::datatyping($tyyppi[0]));
        }
        switch ($tyyppi) {
            case "string":
                return new Schema(type: DataType::STRING);
            case "int":
                return new Schema(type: DataType::INTEGER);
            case "boolean":
                return new Schema(type: DataType::BOOLEAN);
            case "object":
                return new Schema(type: DataType::OBJECT);
            case "number":
                return new Schema(type: DataType::NUMBER);
            default:
                throw new Exception("Tuntematon datatyyppi: " . $tyyppi);
        }
    }
    /**
     * Rakentaa ja lisaa JSON-skeeman structured_configs-listaan
     * 
     * Rakennettu JSON-skeema sisaltaa kaksi osaa: properties ja required. Properties-osa sisältää avain-arvo pareja, jossa avain on propertyn nimi
     * ja arvo on Gemini SDK:n Schema-objekti, joka määrittelee propertyn tyypin. Required-osa sisältää listan propertyn nimiä, jotka ovat pakollisia
     * tekoälyn vastauksessa. Propeties ja required osat rakennetaan funktioon annetun listan $properties perusteella, joka sisältää jokaiselle 
     * propertylle listan, jossa eka arvo kertoo propertyn nimen, toinen arvo propertyn tyypin (string, int, boolean, object, array, number) ja 
     * kolmas on boolean, joka kertoo onko property pakollinen vai ei (lisataanko propertin nimi rqueired-osaan).
     * 
     * @param string $nimi Tallennetun JSON-skeeman nimi
     * @param array $properties Lista JSON-skeeman propertyista. Muodossa: [['propertyn_nimi', 'tyyppi', pakollinen_bool], ...]
     */
    function lisaaStructure ($nimi, $properties) {
        // $properties = [['recipe_name', 'string', true], ['cooking_time_in_minutes', 'int', true]]
        $structure = ["properties" => [], "required" => []];
        for ($x = 0; $x < count($properties); $x++) {
            $propertyName = $properties[$x][0];
            $structure["properties"][$propertyName] = self::datatyping($properties[$x][1]);
            if($properties[$x][2]) {
                $structure["required"][] = $propertyName;
            }
        }
        $this->structured_configs[$nimi] = $structure;
    }
    function poistaStructure($nimi) {
        if(isset($this->structured_configs[$nimi])) {
            unset($this->structured_configs[$nimi]);
            return [true, "Structure '$nimi' poistettu."];
        } else {
            return [false, "Structurea '$nimi' ei löydy."];
        }
    }
    /**
     * Palauttaa tallennetut JSON-skeemat
     */
    function haeStructuret() {
        return $this->structured_configs;
    }
    function haeStructure($nimi) {
        if(isset($this->structured_configs[$nimi])) {
            return [true, $this->structured_configs[$nimi]];
        } else {
            return [false, "Structurea '$nimi' ei löydy."];
        }
    }
    /**
     * Hakee linkistä sivun koodin, karsii siitä paljon pois ja tekee siitä strukturoidun haun Gemini API:iin käyttäen aiemmin tallennettua JSON-skeemaa.
     * 
     * Koodin on tarkoitus hakea artikkelien tietoja, oletus structure ja ohjeistus sekä artikkelien koodin karsinta on rakennettu tätä varten. Koodi ei kykyne lukemaan AJAX:lla
     * generoitua sivun sisältöä. Sivun koodista karsitaan niin paljon pois, että lehden nimeä ei saata löytyä, mutta testailussa tekoäly aina jotenkin silti löysi sen.
     */
    function linkkiHaku(string $linkki, $structure = null, string|null $ohjeistus = null, $haetaankoAiempi = false, $systemInstruction = null) {
        if(!is_null($systemInstruction)) {
            $this->systemInstruction = $systemInstruction;
        }
        if($structure == null) {
            $structure = "Artikkeli";
        }
        if($ohjeistus == null) {
            $ohjeistus = "Hae tiedot artikkelista. Et saa keksiä tietoja, jos niitä ei löydy artikkelista. Alkuperäinen otsikko on meta-tagissa, jos se on annettu. Esittely löytyy artikkkelin alusta. Anna kieli ISO 639-1:2002 -standardin mukaan. Artikkeli: ";
        }
        $valittuStructure = $this->structured_configs[$structure];
        $structureHash = md5(json_encode($valittuStructure));
        $linkkiHash = md5($linkki);
        $sysInsHash = md5($this->systemInstruction);
        $tiedostonPolku = $this->AI->temp_dir . "/gemini_linkkihaku_" . $linkkiHash . "_". $structureHash . "_" . $sysInsHash . "_" . $this->AI->model . ".txt";
        if($haetaankoAiempi && file_exists($tiedostonPolku)) {
            $file = fopen($tiedostonPolku, "r");
            $contents = fread($file, filesize($tiedostonPolku));
            fclose($file);
            return [true, $contents];
        }
        
        // $opts = [
        //     'http' => [
        //         'method'  => 'GET',
        //         'header'  => implode("\r\n", [
        //             'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        //             'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        //             'Accept-Language: fi-FI,fi;q=0.9,en-US;q=0.8,en;q=0.7',
        //             'Accept-Encoding: gzip, deflate, br',
        //             'Referer: https://www.google.com/',
        //             'Cache-Control: no-cache',
        //             'Connection: keep-alive'
        //         ]),
        //         'timeout' => 20,
        //         'follow_location' => 1,
        //     ],
        //     'ssl' => [
        //         'verify_peer' => true,
        //         'verify_peer_name' => true
        //     ]
        // ];
        //$context = stream_context_create($opts);
        //$artikkeli = @file_get_contents($linkki, false, $context);
        // if ($artikkeli === false) {
        //     $error = error_get_last();
        //     return [false, "Linkin lukeminen epäonnistui: " . ($error['message'] ?? 'tuntematon virhe')];
        // }
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
            $tiedosto = fopen("temp_ai/linkkihaku_virhevastaus.txt", 'w');
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
        $tiedosto = fopen("temp_ai/linkkihaku_inside.txt", 'w');
        fwrite($tiedosto, $inside);
        fclose($tiedosto);
        $prompt = $ohjeistus . $ogTitle . $inside;
        try {
            $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->withGenerationConfig(
                generationConfig: new GenerationConfig(
                    responseMimeType: ResponseMimeType::APPLICATION_JSON,
                    responseSchema: new Schema(
                        type: DataType::ARRAY,
                        items: new Schema(
                            type: DataType::OBJECT,
                            properties: $valittuStructure["properties"],
                            required: $valittuStructure["required"],
                        )
                    )
                )
            )
            ->withSystemInstruction(Content::parse($this->systemInstruction)) 
            /*->withTool(new Tool(googleSearch: GoogleSearch::from()))*/ // Tool use with a response mime type: 'application/json' is unsupported
            ->generateContent($prompt);
            $vastaus = $result->text();
            $parsed = json_decode($vastaus, true);
            $totalTokens = $result->usageMetadata->totalTokenCount;
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [false, "Invalid JSON response"];
            }
            if($this->savetoCache) {
                $file = fopen($tiedostonPolku, 'w');
                fwrite($file, $vastaus);
                fclose($file);
            }
            return [true, $parsed, "total_tokens" => $totalTokens];
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

    function modelExists($modelName = null) {
        if ($modelName === null) {
            $modelName = $this->AI->model;
        }
        $malli = false;
        try {
            $malli = !is_null($this->AI->client->models()->retrieve('models/' . $modelName));
            return [$malli];
        } catch (\Exception $e) {
            return [$malli, $e->getMessage()];
        }
    }
    /**
     * Tarkistaa, onko mallia olemassa ja toimiiko se.
     * 
     * Mallin olemassaolon tarkistus hoituu retrieve-metodilla, jolla hakee mallin tiedot ja joka tuntematonta mallia hakeassa johtaa Exceptioniin.
     * Sen jälkeen testataan mallin toimivuus yksinkertaisella 'Test'-promptilla.
     * 
     * @param $modelName Mallin nimi
     */
    function modelWorks($modelName = null) {
        if ($modelName === null) {
        $modelName = $this->AI->model;
        }
        $malli = false;
        try {
            $malli = !is_null($this->AI->client->models()->retrieve('models/' . $modelName));
            // Use a minimal prompt to test
            $result = $this->AI->client
                ->generativeModel(model: $modelName)
                ->generateContent('Test');  // Short prompt to minimize token usage
            return [true, null, $malli];  // If no exception, model exists
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 404 || $statusCode === 400) {
                return [false, $e->getMessage(), $malli];  // Model not found or invalid
            }
            // Re-throw other errors (e.g., auth issues)
            throw $e;
        } catch (\Exception $e) {
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later.", $malli];
            }
            return [false, $e->getMessage(), $malli];  // Any other error likely means model doesn't exist
        }
    }

    function listModels($pageSize = null) {
        return $this->AI->client->models()->list(pageSize: $pageSize);
    }
    /**
     * Laskee promptin tokenit, mukaan lukien tallennetut tiedostot.
     * Tarkoitus käyttää pitkien promptien tokenien laskemiseen ennen haun tekemistä
     */
    function laskeTokenit($arvot) {
        $prompt = $this->AI->suoritaMuotoilu($arvot);
        try {
            if(count($this->AI->files) > 0) {
                $prompt = [$prompt];
                foreach ($this->AI->files as $tiedosto) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $tiedosto);
                    if (in_array($mime_type, array_keys($this->mimeTypes))) {
                        $geminiMimeType = $this->mimeTypes[$mime_type];
                    } 
                    else {
                        return [false, "Epätuettu tiedostomuoto: " . $mime_type];
                    }
                    $prompt[] = new Blob(
                    mimeType: $geminiMimeType,
                    data: base64_encode(
                        file_get_contents($tiedosto)
                    )
                    );
                }
                $result = $this->AI->client
                    ->generativeModel(model: $this->AI->model)
                    ->countTokens($prompt);
                $vastaus = $result->totalTokens;
                return [true, $vastaus];
            } else {
                $result = $this->AI->client
                    ->generativeModel(model: $this->AI->model)
                    ->countTokens($prompt);
                $vastaus = $result->totalTokens;
                return [true, $vastaus];
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Error: " . $e->getMessage()];
        }
    }
}


function applyInlineFormatting(string $text): string
{
    // Extract code blocks to prevent formatting inside them
    $codeBlocks = [];
    $text = preg_replace_callback('/(?<!`)`((?:[^`]|```)+)`(?!`)/', function($matches) use (&$codeBlocks) { // '/`(.+?)`/' on toiseksi paras
        $placeholder = '{{CODEBLOCK-' . count($codeBlocks) . '}}';
        $codeBlocks[] = $matches[1];
        return $placeholder;
    }, $text);

    // Escape HTML special characters for the rest of the text
    $text = htmlspecialchars($text, ENT_SUBSTITUTE, 'UTF-8');

    // Replace [link text](url) or [link text](url 'title') with <a> tags
    $text = preg_replace_callback('/\[(.+?)\]\(([^)]+)\)/', function($matches) {
        $linkText = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $urlAndTitle = $matches[2];
        
        // Parse URL and optional title
        if (preg_match('/^(\S+)(?:\s+["\'](.+?)["\'])?$/', $urlAndTitle, $parts)) {
            $url = htmlspecialchars($parts[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $title = $parts[2] ?? '';
            $titleAttr = !empty($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"' : '';
            return '<a href="' . $url . '"' . $titleAttr . '>' . $linkText . '</a>';
        }
        
        return htmlspecialchars($matches[0], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $text);
    
    // Replace __double underscores__ with <strong>bold</strong>
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    
    // Replace _single underscores_ with <em>italic</em>
    $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
    
    // Replace **bold** with <strong>bold</strong>
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // Replace *italic* with <em>italic</em> (but not already processed bold)
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);

    // Replace ~text~ with <s>strikethrough</s>
    $text = preg_replace('/~(.+?)~/', '<s>$1</s>', $text);

    // Restore code blocks
    foreach ($codeBlocks as $index => $code) {
        $placeholder = '{{CODEBLOCK-' . $index . '}}';
        $text = str_replace($placeholder, '<code>' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>', $text);
    }
    
    return $text;
}

/**
 * Parses a markdown table from an array of lines starting at a given index
 * Returns array [html, newIndex] where newIndex is the line after the table
 */
function parseMarkdownTable(array &$lines, int $startIndex): array
{
    if ($startIndex >= count($lines)) {
        return ['', $startIndex];
    }

    // Check if first line is a table row
    $headerLine = $lines[$startIndex];
    if (!preg_match('/^\|/', $headerLine)) {
        return ['', $startIndex];
    }

    // Parse header row
    $headerCells = array_filter(array_map('trim', explode('|', $headerLine)), fn($v) => $v !== '');
    if (empty($headerCells)) {
        return ['', $startIndex];
    }

    // Check for separator line
    if ($startIndex + 1 >= count($lines)) {
        return ['', $startIndex];
    }

    $separatorLine = $lines[$startIndex + 1];
    if (!preg_match('/^\|/', $separatorLine)) {
        return ['', $startIndex];
    }

    $separatorCells = array_filter(array_map('trim', explode('|', $separatorLine)), fn($v) => $v !== '');

    // Verify separator line has valid markdown table separators
    $alignments = [0 => ''];
    foreach ($separatorCells as $cell) {
        if (!preg_match('/^:?-+:?$/', $cell)) {
            return ['', $startIndex];
        }
        
        // Determine alignment
        $hasLeft = str_starts_with($cell, ':');
        $hasRight = str_ends_with($cell, ':');
        
        if ($hasLeft && $hasRight) {
            $alignments[] = 'center';
        } elseif ($hasRight) {
            $alignments[] = 'right';
        } elseif ($hasLeft) {
            $alignments[] = 'left';
        } else {
            $alignments[] = '';
        }
    }

    // Must have same number of columns in header and separator
    if (count($headerCells) !== count($separatorCells)) {
        return ['', $startIndex];
    }

    $html = "<table>\n<thead>\n<tr>\n";
    
    // Add header cells
    foreach ($headerCells as $i => $cell) {
        $align = $alignments[$i] ? ' style="text-align:' . $alignments[$i] . '"' : '';
        $cellContent = applyInlineFormatting($cell);
        $html .= "<th{$align}>{$cellContent}</th>\n";
    }
    
    $html .= "</tr>\n</thead>\n<tbody>\n";

    // Parse body rows
    $currentIndex = $startIndex + 2;
    while ($currentIndex < count($lines)) {
        $line = $lines[$currentIndex];
        
        if (!preg_match('/^\|/', $line)) {
            break;
        }

        $cells = array_filter(array_map('trim', explode('|', $line)), fn($v) => $v !== '');
        
        // Row must have same number of cells as header
        if (count($cells) !== count($headerCells)) {
            break;
        }

        $html .= "<tr>\n";
        foreach ($cells as $i => $cell) {
            $align = $alignments[$i] ? ' style="text-align:' . $alignments[$i] . '"' : '';
            $cellContent = applyInlineFormatting($cell);
            $html .= "<td{$align}>{$cellContent}</td>\n";
        }
        $html .= "</tr>\n";

        $currentIndex++;
    }

    $html .= "</tbody>\n</table>\n";
    
    return [$html, $currentIndex];
}

function gemtextToHtml(string $input): string
{
    $lines = preg_split('/\r\n|\r|\n/', $input);
    $html = '';

    $inPre = 0;
    $listStack = []; // Stack of list types at each nesting level
    $blockquoteStack = []; // Stack for nested blockquotes
    $spacesPerIndent = 3; // Number of spaces per indent level
    $preIndent = 0; // Indent level of current <pre> block

    $i = 0;
    while ($i < count($lines)) {
        $rawLine = $lines[$i];
        $trimmedLine = trim($rawLine);

        // Preformatted block ``` (only if nothing follows the fence)
        if (preg_match('/^(\s*)```[ \t]*$/', $rawLine, $m)) {
            // Close all blockquotes before pre block
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $indent = strlen($m[1]);
            if(count($listStack) === 1 && $indent != 0) {
                $spacesPerIndent = $indent; // Adjust spaces per indent based on first nested list
            }
            $nestLevel = floor($indent / $spacesPerIndent);

            while (count($listStack) > $nestLevel) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }

            if ($inPre === 0) {
                $html .= "<pre><code>";
                $inPre++;
                $preIndent = $indent;
            } else {
                $inPre--;
                if ($inPre === 0) {
                    $html .= "</code></pre>\n";
                } else {
                    $html .= htmlspecialchars(substr($rawLine, $preIndent), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
                }
            }

            $i++;
            continue;
        }

        // Preformatted block ``` + text
        if (preg_match('/^(\s*)```(.+)$/', $rawLine, $m)) {
            // Close all blockquotes before pre block
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $inPre++;
            if ($inPre === 1) {

                $indent = strlen($m[1]);
                if(count($listStack) === 1 && $indent != 0) {
                    $spacesPerIndent = $indent; // Adjust spaces per indent based on first nested list
                }
                $nestLevel = floor($indent / $spacesPerIndent);

                while (count($listStack) > $nestLevel) {
                    $listType = array_pop($listStack);
                    $html .= "</li></{$listType}>\n";
                }
                $preIndent = $indent;

                $html .= '<pre><code class="language-' . htmlspecialchars(substr($trimmedLine, 3), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">";
            } else {
                $html .= htmlspecialchars(substr($rawLine, $preIndent), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            }

            $i++;
            continue;
        }

        // Inside <pre> - preserve whitespace
        if ($inPre > 0) {
            $html .= htmlspecialchars(substr($rawLine, $preIndent), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            $i++;
            continue;
        }

        // Empty line
        if ($trimmedLine === '') {
            // Close all open lists on empty line
            /*while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }*/
            // Close all blockquotes on empty line
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            $i++;
            continue;
        }

        // Markdown tables
        if (preg_match('/^\|/', $rawLine)) {
            // Close all open lists before table
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }
            // Close all blockquotes before table
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            $vanhaI = $i;
            list($tableHtml, $i) = parseMarkdownTable($lines, $i); //epäoptimaalia lähettää jokainen rivi
            $html .= $tableHtml;
            if($i != $vanhaI) {
                continue;
            }
        }

        // Blockquotes > or > > or > > >
        if (preg_match('/^(\s*)((?:>\s*)+)(.*)$/', $rawLine, $m)) {            
            $indent = strlen($m[1]);
            if(count($listStack) === 1 && $indent != 0) {
                $spacesPerIndent = $indent; // Adjust spaces per indent based on first nested list
            }
            $nestLevel = floor($indent / $spacesPerIndent);

            while (count($listStack) > $nestLevel) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }

            // Count the nesting level of blockquotes
            $blockquoteMarks = $m[2];
            $blockquoteLevel = substr_count($blockquoteMarks, '>');
            $blockquoteText = trim($m[3]);
            
            // Close blockquotes that are deeper than current level
            while (count($blockquoteStack) > $blockquoteLevel) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            
            // Open new blockquotes until we reach target level
            while (count($blockquoteStack) < $blockquoteLevel) {
                $html .= "<blockquote>\n";
                $blockquoteStack[] = 'blockquote';
            }
            
            $text = applyInlineFormatting($blockquoteText);
            $html .= "<p>{$text}</p>\n";
            $i++;
            continue;
        }

        // Headings # ## ###
        if (preg_match('/^(#{1,4})\s*(.+)$/', $rawLine, $m)) {
            // Close all open lists before heading
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }
            // Close all blockquotes before heading
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $level = strlen($m[1]);
            $text = applyInlineFormatting($m[2]);

            $html .= "<h{$level}>{$text}</h{$level}>\n";
            $i++;
            continue;
        }

        // Horizontal rule *** --- ___
        if($trimmedLine === '***' || $trimmedLine === '---' || $trimmedLine === '___') {
            // Close all open lists before horizontal rule
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }
            // Close all blockquotes before horizontal rule
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $html .= "<hr />\n";
            $i++;
            continue;
        }

        // Links => url text
        if (preg_match('/^=>\s+(\S+)(?:\s+(.*))?$/', $rawLine, $m)) {
            // Close all open lists before link
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }
            // Close all blockquotes before link
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $url = $m[1];
            $text = $m[2] ?? $m[1];

            $href = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $textEsc = applyInlineFormatting($text);

            $html .= "<p><a href=\"{$href}\">{$textEsc}</a></p>\n";
            $i++;
            continue;
        }

        // Ordered list item (1. 2. 3. etc)
        if (preg_match('/^(\s*)(\d+\.\s+)(.+)$/', $rawLine, $m)) {
            // Close all blockquotes before list
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            
            $indent = strlen($m[1]);
            $item = applyInlineFormatting($m[3]);

            if(count($listStack) === 1 && $indent != 0) {
                $spacesPerIndent = $indent; // Adjust spaces per indent based on first nested list
            }
            // Adjust nesting level
            $nestLevel = intval($indent / $spacesPerIndent); // 0, 1, 2, 3... based on spaces. tab = 4 spaces,

            // Close lists that are deeper than current level
            while (count($listStack) > $nestLevel + 1) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }
        
            if($nestLevel == count($listStack) - 1) { 

                if($listStack[count($listStack) - 1] !== 'ol') {
                    // Close the last opened list if it's not ol
                    $listType = array_pop($listStack);
                    $html .= "</li></{$listType}>\n";
                    // Open a new ol
                    $html .= "<ol><li>{$item}\n";
                    $listStack[] = 'ol';
                } else {
                    $html .= "</li><li>{$item}\n";
                }

            } else {
                // Open new lists until we reach target nesting level
                while (count($listStack) <= $nestLevel) {
                    $html .= "<ol><li>\n";
                    $listStack[] = 'ol';
                }
                $html .= "{$item}\n";
            }
            $i++;
            continue;
        }

        // Unordered list item * or -
        if (preg_match('/^(\s*)([\*\-]\s+)(.+)$/', $rawLine, $m)) {
            // Close all blockquotes before list
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            
            $indent = strlen($m[1]);
            $item = applyInlineFormatting($m[3]);

            if(count($listStack) === 1 && $indent != 0) {
                $spacesPerIndent = $indent; // Adjust spaces per indent based on first nested list
            }
            // Adjust nesting level
            $nestLevel = intval($indent / $spacesPerIndent); // 0, 1, 2, 3... based on spaces

            // Close lists that are deeper than current level
            while (count($listStack) > $nestLevel + 1) {
                $listType = array_pop($listStack);
                $html .= "</li></{$listType}>\n";
            }

            if($nestLevel == count($listStack) - 1) {

                if($listStack[count($listStack) - 1] !== 'ul') {
                    // Close the last opened list if it's not ul
                    $listType = array_pop($listStack);
                    $html .= "</li></{$listType}>\n";
                    // Open a new ul
                    $html .= "<ul><li>{$item}\n";
                    $listStack[] = 'ul';
                } else {
                    $html .= "</li><li>{$item}\n";
                }

            } else {
                // Open new lists until we reach target nesting level
                while (count($listStack) <= $nestLevel) {
                    $html .= "<ul><li>\n";
                    $listStack[] = 'ul';
                }
                $html .= "{$item}\n";
            }
            $i++;
            continue;
        }

        // Normal paragraph
        // Close all blockquotes before paragraph
        while (!empty($blockquoteStack)) {
            array_pop($blockquoteStack);
            $html .= "</blockquote>\n";
        }
        // Close all open lists before paragraph
        while (!empty($listStack)) {
            $listType = array_pop($listStack);
            $html .= "</li></{$listType}>\n";
        }

        $text = applyInlineFormatting(rtrim($rawLine));
        $html .= "<p>{$text}</p>\n";
        $i++;
    }

    // Close open pre
    if ($inPre > 0) {
        $html .= "</code></pre>\n";
    }

    // Close all remaining open blockquotes
    while (!empty($blockquoteStack)) {
        array_pop($blockquoteStack);
        $html .= "</blockquote>\n";
    }
    
    // Close all remaining open lists
    while (!empty($listStack)) {
        $listType = array_pop($listStack);
        $html .= "</li></{$listType}>\n";
    }

    return $html;
}

?>