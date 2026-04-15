
<div class="paaosa">
    <div class="linkkihaku">
        <div class="linkkiyksikkö">
            <input type="text" placeholder="Syötä artikkelin linkki" id="linkkiInput">
            <div class="linkkirivi" id="tagityyppiRivi">
                <input type="radio" id="tagit_null" name="tagityyppi" value="null" checked>
                <label for="tagit_null">Oletustägit</label>
                <input type="radio" id="tagit_true" name="tagityyppi" value="true">
                <label for="tagit_true">Ehdottaa tägejä</label>
                <input type="radio" id="tagit_false" name="tagityyppi" value="false">
                <label for="tagit_false">Ei tägejä</label>
            </div>
        </div>
        <div class="linkkiyksikkö">
            <select name="apiSelect" id="apiSelect">
                <option value="gemini" selected>Gemini</option>
                <option value="huggingface">Hugging Face</option>
                <option value="openai">OpenAI</option>
            </select>
            <input type="text" name="model" id="model" placeholder="" value="gemini-2.5-flash"> <!--Qwen/Qwen3-32B:groq gemini-2.5-flash gpt-5-nano-->
        </div>
        <button name="haeButton" id="haeButton">Hae</button>
    </div>
        <form action="">
            <div class="linkkilomake">
                <div class="linkkirivi">
                    <div class="linkkiyksikkö">
                        <div class="linkkiyksikkö">
                            <label for="otsikko">Otsikko</label>
                            <textarea name="otsikko" id="otsikko"></textarea>
                        </div>
                        <div class="linkkiyksikkö">
                            <label for="lehti">Lehden nimi</label>
                            <textarea name="lehti" id="lehti"></textarea>
                        </div>
                    </div>
                    <div class="linkkiyksikkö">
                        <div class="linkkiyksikkö">
                            <label for="julkaisuvuosi">Julkaisuvuosi</label>
                            <input type="number" id="julkaisuvuosi" name="julkaisuvuosi" min="1800" max="2026" value="2026">
                        </div>
                        <div class="linkkiyksikkö">
                            <label for="maksullinen">Maksullinen</label>
                            <input type="checkbox" id="maksullinen" name="maksullinen" value="true">
                        </div>
                        <div class="linkkiyksikkö">
                            <label for="kieli">Kieli</label>
                            <input type="text" id="kieli" name="kieli">
                        </div>
                    </div>
                </div>
                <div class="linkkirivi">
                    <div class="linkkiyksikkö">
                        <label for="tekijat">Tekijät</label>
                        <textarea name="tekijat" id="tekijat"></textarea>
                    </div>
                    <div class="linkkiyksikkö">
                        <label for="organisaatiot">Tekijöiden organisaatiot</label>
                        <textarea name="organisaatiot" id="organisaatiot"></textarea>
                    </div>
                </div>
                <div class="linkkirivi">
                    <div class="linkkiyksikkö">
                    <label for="esittely">Esittely</label>
                    <textarea name="esittely" id="esittely"></textarea>
                    </div>
                </div>
                <div class="linkkirivi">
                    <div class="linkkiyksikkö">
                        <label for="tagit">Tägit</label>
                        <textarea name="tagit" id="tagit"></textarea>
                    </div>
                </div>
                <!---<div class="linkkirivi">
                    <div class="linkkiyksikkö">
                        <button type="submit">Tallenna (Ei toimi vielä)</button>
                    </div>
                </div>-->
            </div>
        </form>
</div>
<script>
    const apiSelect = document.getElementById('apiSelect');
    const modelInput = document.getElementById('model');
    apiSelect.addEventListener('change', () => {
        const api = apiSelect.value;
        switch(api) {
            case "gemini":
                modelInput.value = "gemini-2.5-flash";
                break;
            case "huggingface":
                modelInput.value = "Qwen/Qwen3-32B:groq";
                break;
            case "openai":
                modelInput.value = "gpt-5-nano";
                break;
            default:
                modelInput.value = "";
        }
    });
    const linkkiInput = document.getElementById('linkkiInput');
    const haeButton = document.getElementById('haeButton');
    haeButton.addEventListener('click', () => {
        const linkki = linkkiInput.value.trim();
        const api = apiSelect.value;
        const model = modelInput.value.trim();
        const tagityyppi = document.querySelector('input[name="tagityyppi"]:checked').value;
        if(linkki === "") {
            alert("Syötä artikkelin linkki ennen hakua.");
            return;
        }
        haeButton.disabled = true;
        let responseData = null;
        fetch('linkkihaku_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({linkki : linkki, api : api, model : model, tagityyppi : tagityyppi})
        })
        .then(response => {responseData = response; return responseData.json(); })
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('otsikko').value = data.otsikko;
                document.getElementById('lehti').value = data.lehti;
                document.getElementById('julkaisuvuosi').value = data.julkaisuvuosi;
                document.getElementById('maksullinen').checked = data.maksullinen;
                document.getElementById('kieli').value = data.kieli;
                document.getElementById('esittely').value = data.esittely;
                let tekijatTeksti = "";
                data.tekijat.forEach((tekija, index) => {
                    tekijatTeksti += tekija + "\n";
                });
                document.getElementById('tekijat').value = tekijatTeksti;
                let organisaatiotTeksti = "";
                data.organisaatiot.forEach((organisaatio, index) => {
                    organisaatiotTeksti += organisaatio + "\n";
                });
                document.getElementById('organisaatiot').value = organisaatiotTeksti;
                let tagitTeksti = "";
                if(data.tagit !== null) {
                    data.tagit.forEach((tagi, index) => {
                        tagitTeksti += tagi;
                        if(index < data.tagit.length - 1) {
                            tagitTeksti += ", ";
                        }
                    });
                    document.getElementById('tagit').value = tagitTeksti;
                }
            } else {
                alert("Haku epäonnistui: " + data.message);
                if(data.error_details !== null) {
                    console.error("Error details:", data.error_details);
                }
                document.getElementById('otsikko').value = "";
                document.getElementById('lehti').value = "";
                document.getElementById('julkaisuvuosi').value = 2026;
                document.getElementById('maksullinen').checked = false;
                document.getElementById('kieli').value = "";
                document.getElementById('esittely').value = "";
                document.getElementById('tekijat').value = "";
                document.getElementById('organisaatiot').value = "";
                document.getElementById('tagit').value = "";
            }
        })
        .catch(error => {
            console.error('Error:', error, responseData);
            alert("Tapahtui virhe haun aikana. Yritä uudestaan.");
            document.getElementById('otsikko').value = "";
            document.getElementById('lehti').value = "";
            document.getElementById('julkaisuvuosi').value = 2026;
            document.getElementById('maksullinen').checked = false;
            document.getElementById('kieli').value = "";
            document.getElementById('esittely').value = "";
            document.getElementById('tekijat').value = "";
            document.getElementById('organisaatiot').value = "";
            document.getElementById('tagit').value = "";
        })
        .finally(() => {
            haeButton.disabled = false;
        });
    });
</script>