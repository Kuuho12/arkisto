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
class AIGemini extends AI {
    private $mimeTypes = array(
        "image/png" => MimeType::IMAGE_PNG,
        "audio/mpeg" => MimeType::AUDIO_MP3,
        "image/jpeg" => MimeType::IMAGE_JPEG,
        "image/webp" => MimeType::IMAGE_WEBP,
        "application/json" => MimeType::APPLICATION_JSON,
        "text/csv" => MimeType::TEXT_CSV,
        "text/plain" => MimeType::TEXT_PLAIN,
        "video/mp4" => MimeType::VIDEO_MP4
    ); 
    public $structured_configs;
    public function __construct($apiKey, $model = 'gemini-2.5-flash') {
        parent::__construct($apiKey, "gemini", $model);
    }
    /**
     * Tekee haun Gemini API:iin käyttäen haun pohjana aiemmin valittua esivalmisteltua kyselyä
     * 
     * Esivalmistellusta kyselystä tehdään hakuprompti korvaamalla merkittyihin kohtiin funktioon parametrina annetun listan osat.
     * Esivalmistellun kyselyn voi valita valitseKysely-funktiolla.
     * Lopuksi tehdään haku Gemini API:iin. Onnistuessa palautetaan lista, joka sisältää truen ja tesktivastauksen, epäonnistuessa
     * lista, joka sisältää falsen ja virheilmoituksen.
     * 
     * @param array $prompt Tekoälylle lähetettävä kysely
     */
    public function tekstiHaku2($prompt) {
        try {
        $result = $this->client
            ->generativeModel(model: $this->model)
            ->generateContent($prompt);
        $vastaus = $result->text();
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

    function chattays($arvot, $chathistory) {
        $prompt = parent::suoritaHaku($arvot);
        try {
            $chat = $this->client
                ->generativeModel(model: $this->model)
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
                return [false, "Rate limit exceeded. Please try again later."];
            }
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }
    
    function suoritaHaku($arvot) {
        $prompt = parent::suoritaHaku($arvot);
        try {
            if(count($this->files) > 0) {
                $prompt = [$prompt];
                foreach ($this->files as $tiedosto) {
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
                $result = $this->client
                    ->generativeModel(model: $this->model)
                    ->generateContent($prompt);
                $vastaus = $result->text();
                $totalTokes = $result->usageMetadata->totalTokenCount;
                return [true, $vastaus, "total_tokens" => $totalTokes];
            } else {
                $result = $this->client
                    ->generativeModel(model: $this->model)
                    ->generateContent($prompt);
                $vastaus = $result->text();
                $totalTokes = $result->usageMetadata->totalTokenCount;
                return [true, $vastaus, "total_tokens" => $totalTokes];
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
     * Tekee haun Gemini API:iin aiemmin tallennetuilla tiedostoilla
     * 
     * Koodi palauttaa listan, jonka esimmäinen osa boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * Koodi looppaa jokaisen tiedoston. Looppauksessa aluksi tarkistetaan jokaisen tiedoston MIME tyyppi. Jos tyyppiä ei tueta, funktio palautuu.
     * Lopuksi looppauksessa tiedoston data tallennetaan blobina prompt-listamuuttujaan. Looppauksen jälkeen tehdään haku Gemini API:iin promptilla.
     * Käsiteltävt tiedostot lisätään lisaaTiedosto-funktiolla
     * 
     *  @param array $tekstiosa Tekoälylle lähetettävä kysely
     */
    function tiedostoHaku2 ($prompt) {
        $prompt = [$prompt];
        foreach ($this->files as $tiedosto) {
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
            $result = $this->client
            ->generativeModel(model: $this->model)
            ->generateContent($prompt);
            $vastaus = $result->text();
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
        $files = $this->client->files();
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
    function filesApiHaku($prompt, $file) { //Ei toimi
        $prompt = parent::suoritaHaku($prompt);
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
            $result = $this->client
            ->generativeModel(model: $this->model)
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
            return [false, "Rate limit exceeded. Please try again later."];
        }
        return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
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
    function strukturoituHaku2($prompt, $structure) {
        $valittuStructure = $this->structured_configs[$structure];
        try {
            $result = $this->client
            ->generativeModel(model: $this->model)
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
            ->generateContent($prompt);
            $vastaus = $result->text();
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
            switch ($properties[$x][1]) {
                case "string":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::STRING);
                    break;
                case "int":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::INTEGER);
                    break;
                case "boolean":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::BOOLEAN);
                    break;
                case "object":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::OBJECT);
                    break;
                case "array":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::ARRAY);
                    break;
                case "number":
                    $structure["properties"][$propertyName] = new Schema(type: DataType::NUMBER);
                    break;
            }
            if($properties[$x][2]) {
                $structure["required"][] = $propertyName;
            }
        }
        $this->structured_configs[$nimi] = $structure;
    }
    /**
     * Palauttaa tallennetut JSON-skeemat
     */
    function haeStructuret() {
        return $this->structured_configs;
    }
    function modelExists($modelName = null) {
        if ($modelName === null) {
        $modelName = $this->model;
        }
        $malli = false;
        try {
            $malli = !is_null($this->client->models()->retrieve('models/' . $modelName));
            // Use a minimal prompt to test
            $result = $this->client
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
            return [false, $e->getMessage(), $malli];  // Any other error likely means model doesn't exist
        }
    }
    function laskeTokenit($arvot) {
        $prompt = parent::suoritaHaku($arvot);
        try {
            if(count($this->files) > 0) {
                $prompt = [$prompt];
                foreach ($this->files as $tiedosto) {
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
                $result = $this->client
                    ->generativeModel(model: $this->model)
                    ->countTokens($prompt);
                $vastaus = $result->totalTokens;
                return [true, $vastaus];
            } else {
                $result = $this->client
                    ->generativeModel(model: $this->model)
                    ->countTokens($prompt);
                $vastaus = $result->totalTokens;
                return [true, $vastaus];
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            return [false, "HTTP Error $statusCode: " . $e->getMessage()];
        } catch (\Exception $e) {
        return [false, "Error: " . $e->getMessage()];
        }
    }
}
?>