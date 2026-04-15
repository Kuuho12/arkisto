<div id="tekoalytestaus">
    <h3>Prompt:</h3>
    <textarea name="prompt" id="prompt"></textarea>
    <h3>Api ja malli:</h3>
    <div class="flex-direction-row">
        <div class="osa">
            <div class="osa2">
                <input type="checkbox" id="api-gemini" name="api-gemini" value="gemini">
                <label for="api-gemini">Gemini</label>
            </div>
            <input type="text" name="gemini-model" id="gemini-model" value="gemini-2.5-flash">
        </div>
        <div class="osa">
            <div class="osa2">
                <input type="checkbox" id="api-openai" name="api-openai" value="openai">
                <label for="api-openai">OpenAI</label>
            </div>
            <input type="text" name="openai-model" id="openai-model" value="gpt-5-nano">
        </div>
        <div class="osa">
            <div class="osa2">
                <input type="checkbox" id="api-huggingface" name="api-huggingface" value="huggingface">
                <label for="api-huggingface">Hugging Face</label>
            </div>
            <input type="text" name="huggingface-model" id="huggingface-model" value="Qwen/Qwen3-32B:groq">
        </div>
    </div>
    <div class="osa3">
        <input type="submit" id="suorita-haku">
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
    document.getElementById('suorita-haku').addEventListener('click', () => {
        const prompt = document.getElementById('prompt').value;
        const apiGemini = document.getElementById('api-gemini').checked;
        const apiOpenAI = document.getElementById('api-openai').checked;
        const apiHuggingFace = document.getElementById('api-huggingface').checked;
        const geminiModel = document.getElementById('gemini-model').value;
        const openAIModel = document.getElementById('openai-model').value;
        const huggingFaceModel = document.getElementById('huggingface-model').value;
        if(!apiGemini && !apiOpenAI && !apiHuggingFace) {
            alert("Valitse ainakin yksi API.");
            return;
        }
        document.getElementById('suorita-haku').disabled = true;
        document.querySelector('.vastausosa').replaceChildren();
        let kysyttyjenMaara = apiGemini + apiOpenAI + apiHuggingFace;
        let odottavatVastaukset = kysyttyjenMaara;
        if(apiOpenAI) {
            //document.getElementById('openai-vastaus').value = "";
            fetch('testisivu_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    prompt: prompt,
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
                    h3.textContent = 'OpenAI vastaus:';
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'openai-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    openaiVastausDiv.append(h3, textarea);
                    //openaiVastausDiv.innerHTML = `<h3>OpenAI vastaus:</h3><textarea class="vastaus-textarea" name="openai-vastaus" id="openai-vastaus" readonly>${data.message}</textarea>`; Ei toimi jostain hemmetin syystä
                    document.querySelector('.vastausosa').appendChild(openaiVastausDiv);
                } else {
                    document.getElementById('openai-vastaus').value = data.message;
                }
                console.log("openai error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    document.getElementById('suorita-haku').disabled = false;
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
                    prompt: prompt,
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
                    h3.textContent = 'Hugging Face vastaus:';
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'huggingface-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    huggingfaceVastausDiv.append(h3, textarea);
                    document.querySelector('.vastausosa').appendChild(huggingfaceVastausDiv);
                } else {
                    document.getElementById('huggingface-vastaus').value = data.message;
                }
                console.log("huggingface error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    document.getElementById('suorita-haku').disabled = false;
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
                    prompt: prompt,
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
                    h3.textContent = 'Gemini vastaus:';
                    const textarea = document.createElement('textarea');
                    textarea.className = 'vastaus-textarea';
                    textarea.id = 'gemini-vastaus';
                    textarea.readOnly = true;
                    textarea.textContent = data.message;
                    geminiVastausDiv.append(h3, textarea);
                    document.querySelector('.vastausosa').appendChild(geminiVastausDiv);
                } else { 
                    document.getElementById('gemini-vastaus').value = data.message;
                }
                console.log("gemini error:", data.error)
            })
            .finally(() => {
                odottavatVastaukset--;
                if(odottavatVastaukset === 0) {
                    document.getElementById('suorita-haku').disabled = false;
                }
            });
        }
    })
/*document.getElementById('suorita-haku').addEventListener('click', async () => {
    const prompt = document.getElementById('prompt').value;
    const apiGemini = document.getElementById('api-gemini').checked;
    const apiOpenAI = document.getElementById('api-openai').checked;
    const apiHuggingFace = document.getElementById('api-huggingface').checked;
    const geminiModel = document.getElementById('gemini-model').value;
    const openAIModel = document.getElementById('openai-model').value;
    const huggingFaceModel = document.getElementById('huggingface-model').value;
    document.getElementById('suorita-haku').disabled = true;
    
    const response = await fetch('testisivu_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            prompt: prompt,
            apiGemini: apiGemini,
            apiOpenAI: apiOpenAI,
            apiHuggingFace: apiHuggingFace,
            geminiModel: geminiModel,
            openAIModel: openAIModel,
            huggingFaceModel: huggingFaceModel
        })
    });

    const data = await response.json();
    console.log(data);
    document.getElementById('suorita-haku').disabled = false;
});*/
</script>