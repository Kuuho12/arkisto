<div id="tekoalytestaus">
    <div id="tekoalytestaus-controls">
        <h3>Prompt:</h3>
        <textarea name="prompt" id="prompt"><?php echo htmlspecialchars($promptTeksti); ?></textarea>
        <h3>Api ja malli:</h3>
        <div class="flex-direction-row">
            <div class="osa">
                <div class="osa2">
                    <input type="checkbox" id="api-gemini" name="api-gemini" value="gemini" <?php echo $Gemini ? 'checked' : ''; ?>>
                    <label for="api-gemini">Gemini</label>
                </div>
                <input type="text" name="gemini-model" id="gemini-model" value="<?php echo htmlspecialchars($Gemini_model); ?>" list="gemini-mallit">
                <datalist id="gemini-mallit">
                    <option value="gemini-2.5-flash">
                    <option value="gemini-2.5-flash-lite">
                </datalist>
            </div>
            <div class="osa">
                <div class="osa2">
                    <input type="checkbox" id="api-openai" name="api-openai" value="openai" <?php echo $OpenAI ? 'checked' : ''; ?>>
                    <label for="api-openai">OpenAI</label>
                </div>
                <input type="text" name="openai-model" id="openai-model" value="<?php echo htmlspecialchars($OpenAI_model); ?>" list="openai-mallit">
                <datalist id="openai-mallit">
                    <option value="gpt-5-nano">
                    <option value="gpt-4o-mini">
                    <option value="gpt-5.4-nano">
                </datalist>
            </div>
            <div class="osa">
                <div class="osa2">
                    <input type="checkbox" id="api-huggingface" name="api-huggingface" value="huggingface" <?php echo $HuggingFace ? 'checked' : ''; ?>>
                    <label for="api-huggingface">Hugging Face</label>
                </div>
                <input type="text" name="huggingface-model" id="huggingface-model" value="<?php echo htmlspecialchars($HuggingFace_model); ?>" list="huggingface-mallit">
                <datalist id="huggingface-mallit">
                    <option value="Qwen/Qwen3-32B:groq">
                    <option value="Qwen/Qwen3.5-9B:together">
                    <option value="google/gemma-3-27b-it:featherless-ai">
                </datalist>
            </div>
        </div>
        <div class="osa3">
            <input type="submit" id="poista" value="Poista tietokannasta" style="display: none;">
            <input type="submit" id="muokkaa" value="Tallenna muutokset" style="display: none;" disabled>
            <input type="submit" id="tallenna-haku" value="Tallenna">
            <input type="submit" id="suorita-haku" value="Suorita haku" disabled>
        </div>
    </div>
    <div id="vastaukset">
        <div class="vastausosa" id="uusinvastausosa">
            <!--<div class="osa" style="display: none;">
                <h3>Geminin vastaus:</h3>
                <textarea name="gemini-vastaus" id="gemini-vastaus" class="vastaus-textarea"></textarea>
            </div>
            <div class="osa" style="display: none;">
                <h3>OpenAI vastaus:</h3>
                <textarea name="openai-vastaus" id="openai-vastaus" class="vastaus-textarea"></textarea>
            </div>
            <div class="osa" style="display: none;">
                <h3>Hugging Face vastaus:</h3>
                <textarea name="huggingface-vastaus" id="huggingface-vastaus" class="vastaus-textarea"></textarea>
            </div>-->
        </div>
    </div>
</div>
<script>
    const promptElement = document.getElementById('prompt');
    const apiGeminiElement = document.getElementById('api-gemini');
    const apiOpenAIElement = document.getElementById('api-openai');
    const apiHuggingFaceElement = document.getElementById('api-huggingface');
    const geminiModelElement = document.getElementById('gemini-model');
    const openAIModelElement = document.getElementById('openai-model');
    const huggingFaceModelElement = document.getElementById('huggingface-model');

    const tallennaHakuElement = document.getElementById('tallenna-haku');
    const suoritaHakuElement = document.getElementById('suorita-haku');
    const muokkaaElement = document.getElementById('muokkaa');

    const root = document.querySelector(':root')

    let currentPromptId = <?php echo json_encode($promptId); ?>;
    const error1 = <?php echo json_encode($errors[0]); ?>;
    const error2 = <?php echo json_encode($errors[1]); ?>;

    let currentPrompt = null;
    let currentApiGemini = false
    let currentApiOpenAI = false
    let currentApiHuggingFace = false
    let currentGeminiModel = null
    let currentOpenAIModel = null
    let currentHuggingFaceModel = null

    if(currentPromptId !== null && error1 === null) {
        currentPrompt = <?php echo json_encode($promptTeksti); ?>;
        currentApiGemini = <?php echo json_encode($Gemini); ?>;
        currentApiOpenAI = <?php echo json_encode($OpenAI); ?>;
        currentApiHuggingFace = <?php echo json_encode($HuggingFace); ?>;
        currentGeminiModel = <?php echo json_encode(($Gemini ? $Gemini_model : "")); ?>;
        currentOpenAIModel = <?php echo json_encode(($OpenAI ? $OpenAI_model : "")); ?>;
        currentHuggingFaceModel = <?php echo json_encode(($HuggingFace ? $HuggingFace_model : "")); ?>;
        muokkaaElement.style.display = "inline-block";
        tallennaHakuElement.value = "Tallenna uutena promptina";
        suoritaHakuElement.disabled = false;
    }

    let onkoGemini = false
    let onkoOpenAI = false
    let onkoHugginFace = false
    let apiJarjestys = [["gemini", "Gemini"], ["openai", "OpenAI"], ["huggingface", "Hugging Face"]] // 0 = koodissa käytetty nimi (pitää olla sama kuin tietokannassa), 1 = sivulla näytetty nimi

    if(error1 !== null) {
        alert(error1);
    } else if (error2 !== null) {
        alert(error2)
    }
    let responses_ = <?php echo json_encode(empty($responses) ? false : $responses); ?>;
    if(responses_ !== false) {
        showResponses(responses_);
    }

    tallennaHakuElement.addEventListener('click', () => {
        const prompt = promptElement.value.trim();
        const apiGemini = apiGeminiElement.checked;
        const apiOpenAI = apiOpenAIElement.checked;
        const apiHuggingFace = apiHuggingFaceElement.checked;
        const geminiModel = apiGemini ? geminiModelElement.value.trim() : "";
        const openAIModel = apiOpenAI ? openAIModelElement.value.trim() : "";
        const huggingFaceModel = apiHuggingFace ? huggingFaceModelElement.value.trim() : "";
        if(!apiGemini && !apiOpenAI && !apiHuggingFace) {
            alert("Valitse ainakin yksi API.");
            return;
        }
        tallennaHakuElement.disabled = true;
        fetch('testisivu_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 0,
                prompt: prompt,
                Gemini: apiGemini,
                HuggingFace: apiHuggingFace,
                OpenAI: apiOpenAI,
                Gemini_model:  geminiModel,
                HuggingFace_model: huggingFaceModel,
                OpenAI_model: openAIModel
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                currentPromptId = data.promptId;
                currentPrompt = prompt;
                currentApiGemini = apiGemini
                currentApiOpenAI = apiOpenAI
                currentApiHuggingFace = apiHuggingFace
                currentGeminiModel = geminiModel
                currentOpenAIModel = openAIModel
                currentHuggingFaceModel = huggingFaceModel
                alert(data.message);
                suoritaHakuElement.disabled = false;
                muokkaaElement.disabled = true;
                tallennaHakuElement.value = "Tallenna uutena promptina";
                muokkaaElement.style.display = "inline-block";
            } else {
                alert("Virhe: " + data.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error);
        })
        .finally(() => {
            tallennaHakuElement.disabled = false;
        });
    })

    muokkaaElement.addEventListener("click", () => {
        const prompt = promptElement.value.trim();
        const apiGemini = apiGeminiElement.checked;
        const apiOpenAI = apiOpenAIElement.checked;
        const apiHuggingFace = apiHuggingFaceElement.checked;
        const geminiModel = apiGemini ? geminiModelElement.value.trim() : "";
        const openAIModel = apiOpenAI ? openAIModelElement.value.trim() : "";
        const huggingFaceModel = apiHuggingFace ? huggingFaceModelElement.value.trim() : "";
        if(!apiGemini && !apiOpenAI && !apiHuggingFace) {
            alert("Valitse ainakin yksi API.");
            return;
        }
        muokkaaElement.disabled = true;
        fetch('testisivu_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 2,
                promptId: currentPromptId,
                prompt: prompt,
                Gemini: apiGemini,
                HuggingFace: apiHuggingFace,
                OpenAI: apiOpenAI,
                Gemini_model:  geminiModel,
                HuggingFace_model: huggingFaceModel,
                OpenAI_model: openAIModel
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                currentPrompt = prompt;
                currentApiGemini = apiGemini
                currentApiOpenAI = apiOpenAI
                currentApiHuggingFace = apiHuggingFace
                currentGeminiModel = geminiModel
                currentOpenAIModel = openAIModel
                currentHuggingFaceModel = huggingFaceModel
                alert(data.message);
                suoritaHakuElement.disabled = false;
            } else {
                alert("Virhe: " + data.message);
                muokkaaElement.disabled = false;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            muokkaaElement.disabled = false;
        })
    })

    const container = document.getElementById('tekoalytestaus-controls');

    function submitInputsControl(event) {
        const target = event.target;
        if (!target.matches('input, textarea, select')) return;
        if (currentPromptId !== null) {
            const prompt = promptElement.value.trim();
            const apiGemini = apiGeminiElement.checked;
            const apiOpenAI = apiOpenAIElement.checked;
            const apiHuggingFace = apiHuggingFaceElement.checked;
            const geminiModel = apiGemini ? geminiModelElement.value.trim() : "";
            const openAIModel = apiOpenAI ? openAIModelElement.value.trim() : "";
            const huggingFaceModel = apiHuggingFace ? huggingFaceModelElement.value.trim() : "";
            if(currentPrompt == prompt && currentApiGemini == apiGemini && currentApiOpenAI == apiOpenAI && currentApiHuggingFace == apiHuggingFace && currentGeminiModel == geminiModel && currentOpenAIModel == openAIModel && currentHuggingFaceModel == huggingFaceModel) {
                suoritaHakuElement.disabled = false;
                //tallennaHakuElement.disabled = true;
                muokkaaElement.disabled = true;
            } else {
                suoritaHakuElement.disabled = true;
                tallennaHakuElement.disabled = false;
                muokkaaElement.disabled = false;
            }
        }
    }

    container.addEventListener('input', submitInputsControl);
    container.addEventListener('change', submitInputsControl);

    const vastauksetElement = document.getElementById('vastaukset');

    suoritaHakuElement.addEventListener('click', () => {
        const vastausosaElement = document.querySelector('#uusinvastausosa');
        const prompt = promptElement.value.trim();
        const apiGemini = apiGeminiElement.checked;
        const apiOpenAI = apiOpenAIElement.checked;
        const apiHuggingFace = apiHuggingFaceElement.checked;
        const geminiModel = apiGemini ? geminiModelElement.value.trim() : "";
        const openAIModel = apiOpenAI ? openAIModelElement.value.trim() : "";
        const huggingFaceModel = apiHuggingFace ? huggingFaceModelElement.value.trim() : "";
        const groupCode = Date.now() + Math.random()
        if(!apiGemini && !apiOpenAI && !apiHuggingFace) {
            alert("Valitse ainakin yksi API.");
            return;
        }
        if(!onkoGemini && apiGemini) {
            onkoGemini = true
            document.querySelectorAll(".geminiosa").forEach((geminiosa) => {
                geminiosa.style.display = "flex"
            }) 
        }
        if(!onkoHugginFace && apiHuggingFace) {
            onkoHugginFace = true
            document.querySelectorAll(".huggingfaceosa").forEach((huggingfaceosa) => {
                huggingfaceosa.style.display = "flex"
            })
        }
        if(!onkoOpenAI && apiOpenAI) {
            onkoOpenAI = true
            document.querySelectorAll(".openaiosa").forEach((openaiosa) => {
                openaiosa.style.display = "flex"
            })
        }
        suoritaHakuElement.disabled = true;
        let kysyttyjenMaara = apiGemini + apiOpenAI + apiHuggingFace;
        let rootStyle = getComputedStyle(root)
        if(rootStyle.getPropertyValue("--eriApiVastaukset") < kysyttyjenMaara) {
            root.style.setProperty("--eriApiVastaukset", kysyttyjenMaara)
        }
        let odottavatVastaukset = kysyttyjenMaara;
        //vastausosaElement.style.width = kysyttyjenMaara * 1000 + kysyttyjenMaara * 20 + "px"; //div.osa max-width: 1000px, margin: 0px 10px

        const geminiVastausDiv = document.createElement('div');
        geminiVastausDiv.classList.add('osa', 'geminiosa');
        if(!onkoGemini) { geminiVastausDiv.style.display = "none" };
        const openaiVastausDiv = document.createElement('div');
        openaiVastausDiv.classList.add('osa', 'openaiosa');
        if(!onkoOpenAI) { openaiVastausDiv.style.display = "none" }
        const huggingfaceVastausDiv = document.createElement('div');
        huggingfaceVastausDiv.classList.add('osa', 'huggingfaceosa');
        if(!onkoHugginFace) { huggingfaceVastausDiv.style.display = "none" }
        apiJarjestys.forEach((api) => {
            switch(api[0]) {
                case "gemini":
                    vastausosaElement.appendChild(geminiVastausDiv);
                    break;
                case "openai":
                    vastausosaElement.appendChild(openaiVastausDiv);
                    break;
                case "huggingface":
                    vastausosaElement.appendChild(huggingfaceVastausDiv);
                    break;
                default:
                    alert("Apilta " + api + " puuttuu vastausdivin lisääminen.")
            }
        })
        if(apiGemini) {
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
                    groupCode: groupCode,
                    prompt: prompt,
                    Gemini: apiGemini,
                    HuggingFace: apiHuggingFace,
                    OpenAI: apiOpenAI,
                    Gemini_model: geminiModel,
                    HuggingFace_model: huggingFaceModel,
                    OpenAI_model: openAIModel,
                    api : "gemini",
                    model: geminiModel
                })
            })
            .then(response => response.json())
            .then(data => {
                const h3 = document.createElement('h3');
                if(data.status === 'success') {
                    h3.textContent = 'Gemini vastaus:';
                } else {
                    h3.textContent = 'Gemini haku epäonnistui:';
                }
                const textarea = document.createElement('textarea');
                textarea.className = 'vastaus-textarea gemini-vastaus';
                textarea.readOnly = true;
                textarea.textContent = data.message;
                geminiVastausDiv.append(h3, textarea);
                console.log("gemini error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    kaikkiVastauksetSaapuneet(vastausosaElement)
                }
            });
        }
        if(apiOpenAI) {
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
                    groupCode: groupCode,
                    prompt: prompt,
                    Gemini: apiGemini,
                    HuggingFace: apiHuggingFace,
                    OpenAI: apiOpenAI,
                    Gemini_model: geminiModel,
                    HuggingFace_model: huggingFaceModel,
                    OpenAI_model: openAIModel,
                    api : "openai",
                    model: openAIModel
                })
            })
            .then(response => response.json())
            .then(data => {
                //openaiVastausDiv.style.width = "calc(" + (100/kysyttyjenMaara) + "% - 20px)";
                const h3 = document.createElement('h3');
                if(data.status === 'success') {
                    h3.textContent = 'OpenAI vastaus:';
                } else {
                    h3.textContent = 'OpenAI haku epäonnistui:';
                }
                const textarea = document.createElement('textarea');
                textarea.className = 'vastaus-textarea openai-vastaus';
                textarea.readOnly = true;
                textarea.textContent = data.message;
                openaiVastausDiv.append(h3, textarea);
                //openaiVastausDiv.innerHTML = `<h3>OpenAI vastaus:</h3><textarea class="vastaus-textarea" name="openai-vastaus" id="openai-vastaus" readonly>${data.message}</textarea>`; Ei toimi jostain hemmetin syystä
                console.log("openai error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    kaikkiVastauksetSaapuneet(vastausosaElement);
                }
            });
        }
        if(apiHuggingFace) {
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
                    groupCode: groupCode,
                    prompt: prompt,
                    Gemini: apiGemini,
                    HuggingFace: apiHuggingFace,
                    OpenAI: apiOpenAI,
                    Gemini_model: geminiModel,
                    HuggingFace_model: huggingFaceModel,
                    OpenAI_model: openAIModel,
                    api : "huggingface",
                    model: huggingFaceModel
                })
            })
            .then(response => response.json())
            .then(data => {
                const h3 = document.createElement('h3');
                if(data.status === 'success') {
                    h3.textContent = 'Hugging Face vastaus:';
                } else {
                    h3.textContent = 'Hugging Face haku epäonnistui:';
                }
                const textarea = document.createElement('textarea');
                textarea.className = 'vastaus-textarea huggingface-vastaus';
                textarea.readOnly = true;
                textarea.textContent = data.message;
                huggingfaceVastausDiv.append(h3, textarea);
                console.log("huggingface error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    kaikkiVastauksetSaapuneet(vastausosaElement);
                }
            });
        }
    })
    function kaikkiVastauksetSaapuneet(vastausosaElement) {
        suoritaHakuElement.disabled = false;
        vastausosaElement.setAttribute('id', '');
        const uusiVastausosa = document.createElement('div');
        uusiVastausosa.classList.add('vastausosa');
        uusiVastausosa.setAttribute('id', 'uusinvastausosa');
        vastausosaElement.before(uusiVastausosa);
    }

    function showResponses(responses) {
        console.log(responses[0])
        let changedResponses = []
        let partIndex = 0
        responses.forEach((response) => {
            if(changedResponses.some((part, index) => {
                if(part.includes(response.GroupCode)) {
                    partIndex = index;
                    return true;
                }
            })) {
                changedResponses[partIndex][1][changedResponses[partIndex][1].length] = response
            } else {
                changedResponses[changedResponses.length] = [response.GroupCode, [response]]
            }
        })
        console.log(changedResponses)
        
        const tyhjaResponse = Object.keys(changedResponses[0][1]).filter(key => changedResponses[0][1][key] === null)
        let rootStyle = getComputedStyle(root)

        
        changedResponses.forEach((responseGroup) => {
            const vastausosaElement = document.querySelector('#uusinvastausosa');
            let kysyttyjenMaara = 0;
            apiJarjestys.forEach((api) => {
                let onkoApi = false
                let response = tyhjaResponse
                if(responseGroup[1].some((part, index) => {
                    if(part.Api == api[0]) {
                        kysyttyjenMaara++
                        onkoApi = true
                        response = part
                        return true;
                    }
                })) {

                }
                const apiVastausDiv = document.createElement("div")
                apiVastausDiv.classList.add('osa', api[0] + 'osa');
                if(!onkoApi) { apiVastausDiv.style.display = "none" }
                const h3 = document.createElement('h3');
                //if(response.Status === 'success') {
                    h3.textContent = api[1] + ' vastaus:';
                //} else {
                //    h3.textContent = api[1] + ' haku epäonnistui:';
                //}
                const textarea = document.createElement('textarea');
                textarea.className = 'vastaus-textarea ' + api[0] + '-vastaus';
                textarea.readOnly = true;
                textarea.textContent = response.Response;
                apiVastausDiv.append(h3, textarea);
                vastausosaElement.appendChild(apiVastausDiv);
            })
            if(rootStyle.getPropertyValue("--eriApiVastaukset") < kysyttyjenMaara) {
                root.style.setProperty("--eriApiVastaukset", kysyttyjenMaara)
            }
            vastausosaElement.setAttribute('id', '');
            const uusiVastausosa = document.createElement('div');
            uusiVastausosa.classList.add('vastausosa');
            uusiVastausosa.setAttribute('id', 'uusinvastausosa');
            vastausosaElement.before(uusiVastausosa);
        })
    }

</script>