<?php
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
class GeminiHaku {
    public $GeminiApiKey;
    public $client;
    public $model;
    public $esivalmistellutKyselyt = [
        "default" => "%1%2%3%4%5",
        "tiivistelma" => "%1\n\nTee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi. %2", 
        "seo" => "%1\n\nAnna lista ehdotuksia %1 hakukoneoptimointiin yllä olevasta artikkelista suomeksi. %2"
    ];
    public $files = array();
    // sallitut mime-tyypit php => Gemini. Tuetaan: kuvat png, jpeg, webp, avif, csv, json
    private $mimeTypes = array(
        "image/png" => MimeType::IMAGE_PNG,
        "audio/mpeg" => MimeType::AUDIO_MP3,
        "image/jpeg" => MimeType::IMAGE_JPEG,
        "image/webp" => MimeType::IMAGE_WEBP,
        "application/json" => MimeType::APPLICATION_JSON,
        "text/csv" => MimeType::TEXT_CSV,
        "video/mp4" => MimeType::VIDEO_MP4
    ); 
    public $valittuEsivalmisteltuKysely;

    function __construct($model = 'gemini-2.5-flash')
    {
        $this->GeminiApiKey = getenv('GEMINI_API_KEY');
        $this->client = \Gemini::client($this->GeminiApiKey);
        $this->model = $model;
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt["default"];
    }
    function lisaaTiedosto ($tiedosto) {
        if(is_array($tiedosto)) {
            for($x = 0; $x < count($tiedosto); $x++) {
                $this->files[$tiedosto[$x]] = $tiedosto[$x];
            }
        } else {
        $this->files[$tiedosto] = $tiedosto;
        }
    }
    function poistaTiedosto ($tiedosto) {
        unset($this->files[$tiedosto]);
    }
    function haeTiedostot () {
        return $this->files;
    }
    function GeminiTiedostoHaku ($tekstiosa) {
        $prompt = [$tekstiosa];
        foreach ($this->files as $tiedosto) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tiedosto);
            if (in_array($mime_type, array_keys($this->mimeTypes))) {
                $geminiMimeType = $this->mimeTypes[$mime_type];
            } 
            else {
                return [false, "Epätuettu tiedostomuoto"];
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
        } catch (\Exception $e) {
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }

    function GeminiHaku ($arvot) {
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
    } catch (\Exception $e) {
        return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
    }
    }
    function VaihdaValittuEsivalmisteltuKysely($avain) {
        $this->valittuEsivalmisteltuKysely = $this->esivalmistellutKyselyt[$avain];
        //var_dump($this->valittuEsivalmisteltuKysely);

        return $this->valittuEsivalmisteltuKysely;
    }
    function EsivalmistellunKyselynLisays ($avain, $kysely) {
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