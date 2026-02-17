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
class AIGemini {
    private $AI = null;
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
    public $structured_configs;
    public function __construct($AIData) {
        $this->AI = $AIData;
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
        $result = $this->AI->client
            ->generativeModel(model: $this->AI->model)
            ->generateContent($prompt);
        $vastaus = $result->text();
        return [true, $vastaus];
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

    function chattays($arvot, $chathistory) {
        $prompt = $this->AI->suoritaHaku($arvot);
        try {
            $chat = $this->AI->client
                ->generativeModel(model: $this->AI->model)
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
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Haku epäonnistui. Error: " . $e->getMessage()];
        }
    }
    
    function suoritaHaku($arvot) {
        $prompt = $this->AI->suoritaHaku($arvot);
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
                    ->generateContent($prompt);
                $vastaus = $result->text();
                $totalTokes = $result->usageMetadata->totalTokenCount;
                return [true, $vastaus, "total_tokens" => $totalTokes];
            } else {
                $result = $this->AI->client
                    ->generativeModel(model: $this->AI->model)
                    ->generateContent($prompt);
                $vastaus = $result->text();
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
            if ($e->getErrorCode() === 429) {
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
     *  @param array $tekstiosa Tekoälylle lähetettävä kysely
     */
    function tiedostoHaku2 ($prompt) {
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
            ->generateContent($prompt);
            $vastaus = $result->text();
            return [true, $vastaus];
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
    function filesApiHaku($prompt, $file) { //Ei toimi
        $prompt = $this->AI->suoritaHaku($prompt);
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
            if ($e->getErrorCode() === 429) {
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
    function strukturoituHaku2($prompt, $structure) {
        $valittuStructure = $this->structured_configs[$structure];
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
            ->generateContent($prompt);
            $vastaus = $result->text();
            return [true, $vastaus];
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
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, $e->getMessage(), $malli];  // Any other error likely means model doesn't exist
        }
    }
    function laskeTokenit($arvot) {
        $prompt = $this->AI->suoritaHaku($arvot);
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
            if ($e->getErrorCode() === 429) {
                return [null, "Rate limit exceeded. Please try again later."];
            }
            return [false, "Error: " . $e->getMessage()];
        }
    }
}

function applyInlineFormatting(string $text): string
{
    // Then escape HTML special characters for the rest of the text
    $text = htmlspecialchars($text, ENT_SUBSTITUTE, 'UTF-8');

    // Replace `text` with <code>code</code>
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

    // Replace [link text](url) or [link text](url 'title') with <a> tags BEFORE general escaping
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
    $alignments = [];
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

    $i = 0;
    while ($i < count($lines)) {
        $rawLine = $lines[$i];

        // Preformatted block ```
        if (trim($rawLine) === '```') {
            // Close all open lists before pre block
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
            }
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            if ($inPre === 0) {
                $html .= "<pre><code>";
                $inPre++;
            } else {
                $inPre--;
                if ($inPre === 0) {
                    $html .= "</code></pre>\n";
                } else {
                    $html .= htmlspecialchars($rawLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
                }
            }

            $i++;
            continue;
        }

        // Preformatted block ``` + text
        if (strpos(trim($rawLine), '```') === 0) {
            // Close all open lists before pre block
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
            }
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $inPre++;
            if ($inPre === 1) {
                $html .= "<pre><code>";
            } else {
                $html .= htmlspecialchars($rawLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            }

            $i++;
            continue;
        }

        // Inside <pre> - preserve whitespace
        if ($inPre > 0) {
            $html .= htmlspecialchars($rawLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            $i++;
            continue;
        }

        // Empty line
        if (trim($rawLine) === '') {
            // Close all open lists on empty line
            /*while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
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
                $html .= "</{$listType}>\n";
            }
            // Close all blockquotes before table
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }
            
            list($tableHtml, $i) = parseMarkdownTable($lines, $i);
            $html .= $tableHtml;
            continue;
        }

        // Blockquotes > or > > or > > >
        if (preg_match('/^((?:>\s*)+)(.*)$/', trim($rawLine), $m)) {
            // Close all open lists before blockquote
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
            }
            
            // Count the nesting level of blockquotes
            $blockquoteMarks = $m[1];
            $blockquoteLevel = substr_count($blockquoteMarks, '>');
            $blockquoteText = trim($m[2]);
            
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
                $html .= "</{$listType}>\n";
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
        if(trim($rawLine) === '***' || trim($rawLine) === '---' || trim($rawLine) === '___') {
            // Close all open lists before horizontal rule
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
            }
            // Close all blockquotes before horizontal rule
            while (!empty($blockquoteStack)) {
                array_pop($blockquoteStack);
                $html .= "</blockquote>\n";
            }

            $html .= "<hr />\n";
            continue;
        }

        // Links => url text
        if (preg_match('/^=>\s+(\S+)(?:\s+(.*))?$/', $rawLine, $m)) {
            // Close all open lists before link
            while (!empty($listStack)) {
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
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
                $html .= "</{$listType}>\n";
            }

            // Open new lists until we reach target nesting level
            while (count($listStack) <= $nestLevel) {
                $html .= "<ol>\n";
                $listStack[] = 'ol';
            }

            if($listStack[count($listStack) - 1] !== 'ol') {
                // Close the last opened list if it's not ol
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
                // Open a new ol
                $html .= "<ol>\n";
                $listStack[] = 'ol';
            }

            $html .= "<li>{$item}</li>\n";
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
                $html .= "</{$listType}>\n";
            }

            // Open new lists until we reach target nesting level
            while (count($listStack) <= $nestLevel) {
                $html .= "<ul>\n";
                $listStack[] = 'ul';
            }

            if($listStack[count($listStack) - 1] !== 'ul') {
                // Close the last opened list if it's not ul
                $listType = array_pop($listStack);
                $html .= "</{$listType}>\n";
                // Open a new ul
                $html .= "<ul>\n";
                $listStack[] = 'ul';
            }

            $html .= "<li>{$item}</li>\n";
            $i++;
            continue;
        }

        // Normal paragraph
        // Close all open lists before paragraph
        while (!empty($listStack)) {
            $listType = array_pop($listStack);
            $html .= "</{$listType}>\n";
        }
        // Close all blockquotes before paragraph
        while (!empty($blockquoteStack)) {
            array_pop($blockquoteStack);
            $html .= "</blockquote>\n";
        }

        $text = applyInlineFormatting(rtrim($rawLine));
        $html .= "<p>{$text}</p>\n";
        $i++;
    }

    // Close all remaining open lists
    while (!empty($listStack)) {
        $listType = array_pop($listStack);
        $html .= "</{$listType}>\n";
    }

    // Close all remaining open blockquotes
    while (!empty($blockquoteStack)) {
        array_pop($blockquoteStack);
        $html .= "</blockquote>\n";
    }

    // Close open pre
    if ($inPre > 0) {
        $html .= "</code></pre>\n";
    }

    return $html;
}

?>