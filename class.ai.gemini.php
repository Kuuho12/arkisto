<?php
require_once 'class.ai.php';
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\MimeType;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
class AIGemini extends AI {
    private $mimeTypes = array(
        "image/png" => MimeType::IMAGE_PNG,
        "audio/mpeg" => MimeType::AUDIO_MP3,
        "image/jpeg" => MimeType::IMAGE_JPEG,
        "image/webp" => MimeType::IMAGE_WEBP,
        "application/json" => MimeType::APPLICATION_JSON,
        "text/csv" => MimeType::TEXT_CSV,
        "video/mp4" => MimeType::VIDEO_MP4
    ); 
    public $files = array();
    public $structured_configs;
    public function __construct($apiKey) {
        parent::__construct($apiKey, "gemini", 'gemini-2.5-flash');
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
    /**
     * Tekee haun Gemini API:iin aiemmin tallennetuilla tiedostoilla
     * 
     * Koodi palauttaa listan, jonka esimmäinen osa boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * Koodi looppaa jokaisen tiedoston. Looppauksessa aluksi tarkistetaan jokaisen tiedoston MIME tyyppi. Jos tyyppiä ei tueta, funktio palautuu.
     * Lopuksi looppauksessa tiedoston data tallennetaan blobina prompt-listamuuttujaan. Looppauksen jälkeen tehdään haku Gemini API:iin promptilla.
     * Käsiteltävt tiedostot lisätään lisaaTiedosto-funktiolla
     * 
     *  @param array $tekstiosa Tekoälylle lähetettävän kyselyn tekstiosa
     */
    function tiedostoHaku2 ($tekstiosa) {
        $prompt = [$tekstiosa];
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
    /**
     * Lisaa tiedoston tai tiedostoja objektin $files-listaan
     * 
     * Tiedoston avain ja arvo ovat identiset.
     * 
     * @param string $tiedosto Tiedoston suhteellinen polku (relative path)
     */
    function lisaaTiedosto ($tiedosto) {
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
    function poistaTiedosto ($tiedosto) {
        unset($this->files[$tiedosto]);
    }
    /**
     * Palauttaa objektin $files-listan
     */
    function haeTiedostot () {
        return $this->files;
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
}
?>