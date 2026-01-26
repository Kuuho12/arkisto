<div id="chatContainer">
    <div id="chatHeader">
    <h2>Chat Ikkuna</h2>
    </div>
    <div id="chatWindow">
        <div class="viesti">
            <p>Minkä tekoälyrajapinnan kanssa haluat keskustella?</p>
        </div>
        <div class="vaihtoehdot">
            <button>Gemini</button>
            <button>Hugging Face</button>
        </div>
    </div>
    <!--<input type="text" id="chatInput" placeholder="Kirjoita viestisi...">
    <button id="sendButton" style="padding: 10px;">Lähetä</button>-->
</div>
<script>
    let valittuAPI = null;
    let GeminiMallit = ['gemini-2.5-flash', 'gemini-2.5-flash-lite'/*, 'gemini-2.0-flash', 'gemini-2.0-flash-lite' //Ilmaisella tasolla ei jonkun vuoksi voinut käyttää näitä vanhempia malleja*/];
    let HuggingFaceMallit = ['deepseek-ai/DeepSeek-V3.2:novita', 'google/gemma-3-27b-it:nebius', 'Qwen/Qwen3-32B:groq', 'zai-org/GLM-4.6V-Flash:novita', 'mistralai/Mistral-7B-Instruct-v0.1:novita'];
    const chatWindow = document.getElementById('chatWindow');
    document.querySelectorAll('.vaihtoehdot > button').forEach(button => {
        button.addEventListener('click', () => {
            valittuAPI = button.textContent;
            const viestiDiv = document.createElement('div');
            const vaihtoehdotDiv = document.querySelector('.vaihtoehdot');
            viestiDiv.classList.add('viesti', 'kayttajanViesti');
            viestiDiv.innerHTML = `<p>Valitsit: ${valittuAPI}</p>`;
            chatWindow.appendChild(viestiDiv);
            vaihtoehdotDiv.remove();
            chatWindow.scrollTop = chatWindow.scrollHeight;
            malliKysymys();
        });
    });
    let valittuMalli = null;
    let onkoMalliValinta = false;
    function malliKysymys() {
        const kysymysDiv = document.createElement('div');
        kysymysDiv.classList.add('viesti');
        kysymysDiv.innerHTML = `<p>Minkä mallin kanssa haluat keskustella?</p>`;
        chatWindow.appendChild(kysymysDiv);
        const vastausDiv = document.createElement('div');
        vastausDiv.classList.add('vastaus');
        chatWindow.appendChild(vastausDiv);
        const inputElement = document.createElement('input');
        inputElement.id = 'malliInput';
        inputElement.setAttribute("list", "mallit");
        vastausDiv.appendChild(inputElement);
        inputElement.addEventListener('keydown', (event) => {
            if(event.key === 'Enter' && !onkoMalliValinta) {
                malliValinta();
            }
        });
        const dataList = document.createElement('datalist');
        dataList.id = 'mallit';
        vastausDiv.appendChild(dataList);
        if(valittuAPI === "Gemini") {
            GeminiMallit.forEach(malli => {
                const option = document.createElement('option');
                option.value = malli;
                dataList.appendChild(option);
            });
        } else if(valittuAPI === "Hugging Face") {
            HuggingFaceMallit.forEach(malli => {
                const option = document.createElement('option');
                option.value = malli;
                dataList.appendChild(option);
            });
        }
        buttonElement = document.createElement('button');
        buttonElement.innerHTML = "Lähetä";
        vastausDiv.appendChild(buttonElement);
        buttonElement.addEventListener('click', () => {
            malliValinta();
        });
    }
    let chatti_id = null;
    function malliValinta() {
        vastausDiv = document.querySelector('.vastaus');
        valittuMalli = document.getElementById('malliInput').value;
        if(valittuMalli === "") {
            alert("Valitse malli ennen jatkamista.");
            return;
        }
        onkoMalliValinta = true;
        buttonElement.disabled = true;
        fetch('chat_handler', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({api : valittuAPI, malli : valittuMalli, pyynto : 1}) // Convert JS object to JSON string
        })
        .then(response => response.json()) // Parse the JSON from PHP
        .then(data => {
            if(data.status === 'success') {
                chatti_id = data.id;
                const viestiDiv = document.createElement('div');
                viestiDiv.classList.add('viesti', 'kayttajanViesti');
                viestiDiv.innerHTML = `<p>Valitsit mallin: ${valittuMalli}</p>`;
                chatWindow.appendChild(viestiDiv);
                vastausDiv.remove();
                viestiDiv2 = document.createElement('div');
                viestiDiv2.classList.add('viesti');
                viestiDiv2.innerHTML = `<p>Aloita keskustelu tekoälyn kanssa</p>`;
                chatWindow.appendChild(viestiDiv2);
                chatWindow.scrollTop = chatWindow.scrollHeight;
                aiKeskustelu();
            } else {
                if(data.error !== undefined) {
                    if(data.error === 1) {
                        alert('Mallin käyttö ei syystä tai toisesta onnistunut.');
                    } else {
                        alert('Mallia ei löytynyt. Valitse toinen malli.');
                    }
                } else {
                alert('Mallia ei löydy tai se ei toimi juuri nyt. Valitse toinen malli.');
                }
                console.log(data.message);
                onkoMalliValinta = false;
                buttonElement.disabled = false;
                return;
            }
        })
    }
    let onkoChat = false;
    function aiKeskustelu() {
        const keskusteluDiv = document.createElement('div');
        keskusteluDiv.classList.add('keskustelu');
        chatWindow.appendChild(keskusteluDiv);
        const inputElement = document.createElement('input');
        inputElement.id = 'chatInput';
        inputElement.placeholder = 'Kirjoita viestisi...';
        keskusteluDiv.appendChild(inputElement);
        inputElement.addEventListener('keydown', (event) => {
            if(event.key === 'Enter' && !onkoChat) {
                chat();
            }
        });
        const sendButton = document.createElement('button');
        sendButton.id = 'sendButton';
        sendButton.innerHTML = 'Lähetä';
        keskusteluDiv.appendChild(sendButton);
        sendButton.addEventListener('click', () => {
            chat();
        });
    }
    function chat() {
        const userMessage = document.getElementById('chatInput').value;
        if(userMessage === "") {
            alert("Kirjoita viesti ennen lähettämistä.");
            return;
        }
        onkoChat = true;
        const sendButton = document.getElementById('sendButton');
        const keskusteluDiv = document.querySelector('.keskustelu');
        const viestiDiv = document.createElement('div');
        viestiDiv.classList.add('viesti', 'kayttajanViesti');
        viestiDiv.innerHTML = `<p>${userMessage}</p>`;
        chatWindow.insertBefore(viestiDiv, keskusteluDiv);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        document.getElementById('chatInput').value = '';
        sendButton.disabled = true;
        fetch('chat_handler', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({api : valittuAPI, malli : valittuMalli, viesti : userMessage, onkoChattays : true, chatti_id : chatti_id})
        })
        .then(response => response.json()) // Parse the JSON from PHP
        .then(data => {
            sendButton.disabled = false;
            onkoChat = false;
            if(data.status === 'success') {
                const vastausDiv = document.createElement('div');
                vastausDiv.classList.add('viesti');
                vastausDiv.innerHTML = `<p>${data.vastaus[1]}</p>`;
                chatWindow.insertBefore(vastausDiv, keskusteluDiv);
                chatWindow.scrollTop = chatWindow.scrollHeight;
                console.log(data.vastaus);
            } else {
                //alert('Tapahtui virhe: ' + data.message);
                const virheDiv = document.createElement('div');
                virheDiv.classList.add('viesti', 'virheViesti');
                virheDiv.innerHTML = `<p>${data.message}</p>`;
                chatWindow.insertBefore(virheDiv, keskusteluDiv);
                console.log(data.message);
            }
            })
    }   
</script>