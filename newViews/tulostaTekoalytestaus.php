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
    <div class="flex-direction-row vastausosa">
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

    let currentPromptId = <?php echo json_encode($promptId); ?>;
    const error = <?php echo json_encode($error); ?>;

    let currentPrompt = null;
    let currentApiGemini = null
    let currentApiOpenAI = null
    let currentApiHuggingFace = null
    let currentGeminiModel = null
    let currentOpenAIModel = null
    let currentHuggingFaceModel = null
    if(currentPromptId !== null && error === null) {
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

    if(error !== null) {
        alert(error);
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

    const vastausosaElement = document.querySelector('.vastausosa');

    suoritaHakuElement.addEventListener('click', () => {
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
        suoritaHakuElement.disabled = true;
        vastausosaElement.replaceChildren();
        let kysyttyjenMaara = apiGemini + apiOpenAI + apiHuggingFace;
        let odottavatVastaukset = kysyttyjenMaara;
        if(kysyttyjenMaara === 1) {
            vastausosaElement.style.justifyContent = "center";
        } else {
            vastausosaElement.style.justifyContent = "flex-start";
        }
        if(apiOpenAI) {
            //document.getElementById('openai-vastaus').value = "";
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
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
                if(!document.getElementById('openai-vastaus')) { 
                    const openaiVastausDiv = document.createElement('div');
                    openaiVastausDiv.classList.add('osa');
                    openaiVastausDiv.style.width = "calc(" + (100/kysyttyjenMaara) + "% - 20px)";
                    const h3 = document.createElement('h3');
                    if(data.status === 'success') {
                        h3.textContent = 'OpenAI vastaus:';
                    } else {
                        h3.textContent = 'OpenAI haku epäonnistui:';
                    }
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'openai-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    openaiVastausDiv.append(h3, textarea);
                    //openaiVastausDiv.innerHTML = `<h3>OpenAI vastaus:</h3><textarea class="vastaus-textarea" name="openai-vastaus" id="openai-vastaus" readonly>${data.message}</textarea>`; Ei toimi jostain hemmetin syystä
                    vastausosaElement.appendChild(openaiVastausDiv);
                } else {
                    document.getElementById('openai-vastaus').value = data.message;
                }
                console.log("openai error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    suoritaHakuElement.disabled = false;
                }
            });
        }
        if(apiHuggingFace) {
            //document.getElementById('huggingface-vastaus').value = "";
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
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
                 if(!document.getElementById('huggingface-vastaus')) { 
                    const huggingfaceVastausDiv = document.createElement('div');
                    huggingfaceVastausDiv.classList.add('osa');
                    huggingfaceVastausDiv.style.width = "calc(" + (100/kysyttyjenMaara) + "% - 20px)";
                    const h3 = document.createElement('h3');
                    if(data.status === 'success') {
                        h3.textContent = 'Hugging Face vastaus:';
                    } else {
                        h3.textContent = 'Hugging Face haku epäonnistui:';
                    }
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'huggingface-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    huggingfaceVastausDiv.append(h3, textarea);
                    vastausosaElement.appendChild(huggingfaceVastausDiv);
                } else {
                    document.getElementById('huggingface-vastaus').value = data.message;
                }
                console.log("huggingface error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    suoritaHakuElement.disabled = false;
                }
            });
        }
        if(apiGemini) {
            //document.getElementById('gemini-vastaus').value = "";
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 1,
                    promptId: currentPromptId,
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
                if(!document.getElementById('gemini-vastaus')) { 
                    const geminiVastausDiv = document.createElement('div');
                    geminiVastausDiv.classList.add('osa');
                    geminiVastausDiv.style.width = "calc(" + (100/kysyttyjenMaara) + "% - 20px)";
                    const h3 = document.createElement('h3');
                    if(data.status === 'success') {
                        h3.textContent = 'Gemini vastaus:';
                    } else {
                        h3.textContent = 'Gemini haku epäonnistui:';
                    }
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'gemini-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    geminiVastausDiv.append(h3, textarea);
                    vastausosaElement.appendChild(geminiVastausDiv);
                } else { 
                    document.getElementById('gemini-vastaus').value = data.message;
                }
                console.log("gemini error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    suoritaHakuElement.disabled = false;
                }
            });
        }
    })

</script>