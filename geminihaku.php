<?php
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\MimeType;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
class GeminiHaku {
    public $GeminiApiKey;
    public $client;
    public $model;
    public $esivalmistellutKyselyt = [
        "default" => "%1",
        "tiivistelma" => "%1\n\nTee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi. %2", 
        "seo" => "%1\n\nAnna lista ehdotuksia %1 hakukoneoptimointiin yllä olevasta artikkelista suomeksi. %2"
    ];
    public $valittuEsivalmisteltuKysely;
    public $files = array();
    private $mimeTypes = array(
        "image/png" => MimeType::IMAGE_PNG,
        "audio/mpeg" => MimeType::AUDIO_MP3,
        "image/jpeg" => MimeType::IMAGE_JPEG,
        "image/webp" => MimeType::IMAGE_WEBP,
        "application/json" => MimeType::APPLICATION_JSON,
        "text/csv" => MimeType::TEXT_CSV,
        "video/mp4" => MimeType::VIDEO_MP4
    ); 
    public $structured_configs;

    function __construct($model = 'gemini-2.5-flash')
    {
        $this->GeminiApiKey = getenv('GEMINI_API_KEY');
        $this->client = \Gemini::client($this->GeminiApiKey);
        $this->model = $model;
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt["default"];
        $this->structured_configs = [
        "recipes_with_cooking_time" => [
            "properties" => [
                'recipe_name' => new Schema(type: DataType::STRING),
                'cooking_time_in_minutes' => new Schema(type: DataType::INTEGER)
            ], 
            "required" => [
                'recipe_name',
                'cooking_time_in_minutes'
            ]
        ]
    ];
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
     * Tekee haun Gemini API:iin aiemmin tallennetuilla tiedostoilla
     * 
     * Koodi palauttaa listan, jonka esimmäinen osa boolean, joka kertoo onnistuiko haku ja toinen osa haun tuloksen tai virheilmoituksen.
     * Koodi looppaa jokaisen tiedoston. Looppauksessa aluksi tarkistetaan jokaisen tiedoston MIME tyyppi. Jos tyyppiä ei tueta, funktio palautuu.
     * Lopuksi looppauksessa tiedoston data tallennetaan blobina prompt-listamuuttujaan. Looppauksen jälkeen tehdään haku Gemini API:iin promptilla.
     * Käsiteltävt tiedostot lisätään lisaaTiedosto-funktiolla
     * 
     *  @param string $tekstiosa Haun alkuun tuleva tekstiosa
     */
    function geminiTiedostoHaku ($tekstiosa) {
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

    function geminiStructuredHaku($arvot, $structure) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
        }
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
            }
            if($properties[$x][2]) {
                $structure["required"][] = $propertyName;
            }
        }
        //var_dump($structure);
        $this->structured_configs[$nimi] = $structure;
        //var_dump($this->structured_configs);
    }
    function haeStructuret() {
        return $this->structured_configs;
    }
    /**
     * Tekee haun Gemini API:iin käyttäen haun pohjana aiemmin valittua esivalmisteltua kyselyä
     * 
     * Esivalmistellusta kyselystä tehdään hakuprompti korvaamalla merkittyihin kohtiin funktioon parametrina annetun listan osat.
     * Esivalmistellun kyselyn voi valita valitseKysely-funktiolla.
     * Lopuksi tehdään haku Gemini API:iin. Onnistuessa palautetaan lista, joka sisältää truen ja tesktivastauksen, epäonnistuessa
     * lista, joka sisältää falsen ja virheilmoituksen.
     * 
     * @param array $arvot Lista haun osista, jotka liitetään esivalmisteltuun kyselyyn
     */
    function geminiHaku ($arvot) {
        $prompt = $this->valittuEsivalmisteltuKysely;
        $arvotCount = count($arvot);
        for($x = 1; $x <= $arvotCount; $x++) {
            $prompt = str_replace("%$x", $arvot[$x-1], $prompt);
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
    /*function TiivistelmanLuonti ($promptinosa, $id) {
        $prompt = $promptinosa . "\n\n" . "Tee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi.";
        try {
        $result = $this->client
            ->generativeModel(model: $this->model)
            ->generateContent($prompt);
        $tiivistelma = $result->text();
        $file = fopen('temp_ai/summary_' . $id . '.txt', 'w');
        fwrite($file, $tiivistelma);
        fclose($file);
        return $tiivistelma;
    } catch (\Exception $e) {
        return "Tekoälytiivistelmää ei voida luoda tällä hetkellä. Error: " . $e->getMessage();
    }
    }
    function HakukoneoptimoinninLuonti ($promptinosa, $id) {
        $prompt = $promptinosa . "\n\n" . "Anna lista ehdotuksia hakukoneoptimointiin yllä olevasta artikkelista suomeksi.";
        try {
        $result = $this->client
            ->generativeModel(model: $this->model)
            ->generateContent($prompt);
        $optimointi = $result->text();
        $file = fopen('temp_ai/optimization_' . $id . '.txt', 'w');
        fwrite($file, $optimointi);
        fclose($file);
        return $optimointi;
    } catch (\Exception $e) {
        return "Hakukoneille optimointiehdotuksien luonti epäonnistui. Error: " . $e->getMessage();
    }
    }*/
}
?>