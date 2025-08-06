<!DOCTYPE html>
<html lang="tr">
<head>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yapay Zeka Destekli Rüya Tercümanı ve Hikayeleştirici</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="header-animation-container">
        <lottie-player
            src="ruya_logo.json" background="transparent"
            speed="1"
            style="width: 97%; height: 87%;"
            loop
            autoplay>
        </lottie-player>
    </div>
    <div class="header-content">
        <h1>Esrar-ı Rüya</h1>
        <p>Rüyalar yalnızca geceye ait değildir. Bu platform, onları anlamın gündüzüne taşır.</p>
    </div>
</header>

<main>
    <section class="container">
        <input type="text" id="userNameInput" placeholder="Adınız (isteğe bağlı)" class="text-input">
        <textarea id="dreamInput" placeholder="Rüyanızı buraya yazın..."></textarea>
        <div class="button-group">
            <button class="action-button" id="analyzeBtn" onclick="analyzeDream()"> Rüyamı Sembolik Yorumla</button>
            <button class="action-button" id="storyBtn" onclick="createStory()">Rüya Hikayesi Oluştur</button>
            <button class="action-button" id="emotionalAnalyzeBtn" onclick="analyzeEmotions()">Duygu Analizi</button>
            <button class="action-button" id="cardBtn" onclick="createCard()" style="display:none;">Rüya Kartına Dönüştür</button>
            <button class="action-button" id="saveBtn" onclick="saveDream()" style="display:none;">Rüyamı Kaydet</button>
        </div>
        <div id="loading" style="display: none;"></div>
        <div id="result" style="display:none;">
        </div>
    </section>
</main>

<footer>
    <p>&copy; 2025 Rüya Tercümanı. Tüm Hakları Saklıdır.</p>
</footer>

<script>
    // Markdown'dan HTML'e dönüştürme fonksiyonu (Basit bir dönüştürücü)
    function convertMarkdownToHtml(markdownText) {
        let html = markdownText;

        html = html.replace(/^## Sembolik Yorum/gim, '<h3>Sembolik Yorum</h3>');
        html = html.replace(/^## Rüya Hikayesi/gim, '<h3>Rüya Hikayesi</h3>');
        html = html.replace(/^## Duygu Analizi/gim, '<h3>Duygu Analizi</h3>'); 

        html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');

        html = html.split('\n').map(line => {
            if (line.trim() === '') {
                return '';
            }
            if (line.match(/^\s*<(h[1-6]|p|ul|ol|li|strong|em)>/i)) {
                return line;
            }
            return `<p>${line}</p>`;
        }).join('');

        html = html.replace(/<p>\s*<\/p>/g, '');
        return html;
    }

    // Ortak API çağırma fonksiyonu
    async function sendToAI(prompt, buttonId, loadingText, resultTitle, retryCount = 0) {
        const dreamText = document.getElementById('dreamInput').value;
        const resultDiv = document.getElementById('result');
        const loadingDiv = document.getElementById('loading');
        const button = document.getElementById(buttonId);

        if (dreamText.trim() === '') {
            resultDiv.innerHTML = '<p style="color: red;">Lütfen rüyanızı yazın.</p>';
            resultDiv.style.display = 'block';
            document.getElementById('cardBtn').style.display = 'none';
            document.getElementById('saveBtn').style.display = 'none';
            return;
        }

        resultDiv.innerHTML = '';
        loadingDiv.style.display = 'block';
        loadingDiv.innerHTML = loadingText;
        button.disabled = true;
        document.getElementById('cardBtn').style.display = 'none';
        document.getElementById('saveBtn').style.display = 'none';

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        try {
            const response = await fetch('process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ promptText: prompt }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (response.status === 503) {
                if (retryCount < 3) {
                    resultDiv.innerHTML = `<p style="color: orange;">Sunucu aşırı yüklü. 5 saniye sonra tekrar denenecek... (${retryCount + 1}/3)</p>`;
                    setTimeout(() => {
                        sendToAI(prompt, buttonId, loadingText, resultTitle, retryCount + 1);
                    }, 5000);
                    return;
                } else {
                    resultDiv.innerHTML = `<p style="color: red;">Sunucu hala yanıt vermiyor (Hata Kodu: 503). Lütfen daha sonra tekrar deneyin.</p>`;
                    resultDiv.style.display = 'block';
                    return;
                }
            }

            if (!response.ok) {
                try {
                    const errorData = await response.json();
                    resultDiv.innerHTML = `<p style="color: red;">API Hatası (${response.status}): ${errorData.error || 'Bilinmeyen Hata'}</p>`;
                } catch (jsonError) {
                    const errorText = await response.text();
                    resultDiv.innerHTML = `<p style="color: red;">API Hatası (${response.status}): ${errorText}</p>`;
                }
                resultDiv.style.display = 'block';
                return;
            }

            const data = await response.json();

            if (data.result) {
                const html = convertMarkdownToHtml(data.result);
                resultDiv.innerHTML = `<div class="dream-analysis-output">${html}</div>`;
                resultDiv.style.display = 'block';
                document.getElementById('cardBtn').style.display = 'block';
                document.getElementById('saveBtn').style.display = 'block';
                resultDiv.classList.add('dream-analysis-output');
            } else if (data.error) {
                resultDiv.innerHTML = `<p style="color: red;">Hata: ${data.error}</p>`;
                resultDiv.style.display = 'block';
            } else {
                resultDiv.innerHTML = `<p style="color: orange;">Yorum alınamadı. API'den beklenmeyen bir yanıt geldi.</p>`;
                resultDiv.style.display = 'block';
            }

        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                resultDiv.innerHTML = `<p style="color: red;">İstek zaman aşımına uğradı. Lütfen tekrar deneyin.</p>`;
            } else {
                resultDiv.innerHTML = `<p style="color: red;">Bir ağ hatası oluştu: ${error.message}. Bağlantınızı kontrol edin.</p>`;
            }
            resultDiv.style.display = 'block';
        } finally {
            loadingDiv.style.display = 'none';
            button.disabled = false;
        }
    }

    async function analyzeDream() {
        const dreamText = document.getElementById('dreamInput').value;
        const userName = document.getElementById('userNameInput').value;
        const prompt = "Kullanıcının Adı: " + userName + "\nRüya: " + dreamText + "\n\nBu rüyayı, ana hatlarını belirterek kısa ve net bir şekilde sembolik olarak yorumla. Yorumu 2-3 paragrafı geçmesin. Yorumu '## Sembolik Yorum' başlığı altında ver. Önemli sembolleri kalın (**) yaparak vurgula.";
        sendToAI(prompt, 'analyzeBtn', 'Yapay zeka rüyanızı sembolik olarak yorumluyor, lütfen bekleyin...', '<h3>Sembolik Yorum</h3>');
    }

    async function createStory() {
        const dreamText = document.getElementById('dreamInput').value;
        const userName = document.getElementById('userNameInput').value;
        const prompt = "Kullanıcının Adı: " + userName + "\nRüya: " + dreamText + "\n\nBu rüyadan esinlenerek yaratıcı, 2-3 paragraftan oluşan kısa bir hikaye yaz. Hikayeyi '## Rüya Hikayesi' başlığı altında ver. Hikayede, rüyanın ana temasını işleyen, fantastik veya sembolik öğeler içeren sürükleyici bir anlatım kullan. Önemli kelimeleri kalın (**) yaparak vurgula.";
        sendToAI(prompt, 'storyBtn', 'Yapay zeka rüyanızdan bir hikaye oluşturuyor, lütfen bekleyin...', '<h3>Rüya Hikayesi</h3>');
    }

    async function analyzeEmotions() {
        const dreamText = document.getElementById('dreamInput').value;
        const userName = document.getElementById('userNameInput').value;
        const prompt = "Kullanıcının Adı: " + userName + "\nRüya: " + dreamText + "\n\nBu rüyadaki duygu durumunu ve ana temaları kısa ve samimi bir dille analiz et. Rüyadan çıkan duygusal mesajı 2-3 paragrafı geçmeyecek şekilde açıkla. Yorumu '## Duygu Analizi' başlığı altında ver. Duyguları veya önemli ipuçlarını kalın (**) yaparak vurgula.";
        sendToAI(prompt, 'emotionalAnalyzeBtn', 'Yapay zeka rüyanızın duygu analizini yapıyor, lütfen bekleyin...', '<h3>Duygu Analizi</h3>');
    }

    function createCard() {
    const resultDiv = document.getElementById('result');

    if (resultDiv.style.display === 'none' || resultDiv.innerHTML.trim() === '') {
        alert('Önce bir rüya yorumu veya hikayesi oluşturmalısınız!');
        return;
    }

    const fullText = resultDiv.innerText;
    const sentences = fullText.split('.');
    const lastSentence = sentences.length > 1 ? sentences.slice(-2).join('.').trim() : fullText.trim();

    // Kartın HTML içeriğini oluştur (çok basit tutarak hata olasılığını azalt)
    const cardHTML = `
        <div style="background-color: #f0f8ff; border: 1px solid #ccc; padding: 20px; border-radius: 8px; font-family: sans-serif; text-align: center;">
            <p style="font-size: 1.2em; color: #333; margin: 0;">"${lastSentence}"</p>
            <p style="font-size: 0.8em; color: #777; margin: 10px 0 0;">Esrar-ı Rüya</p>
        </div>
    `;

    // Geçici bir div oluştur ve içeriği ona ata
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = cardHTML;
    tempDiv.style.position = 'absolute';
    tempDiv.style.top = '-9999px';
    tempDiv.style.left = '-9999px';
    document.body.appendChild(tempDiv);

    // html2canvas'ı sadece geçici div üzerinde çalıştır
    html2canvas(tempDiv, {
        scale: 2,
        backgroundColor: null
    }).then(function(canvas) {
        const imageURL = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = imageURL;
        link.download = 'ruya_ozlusozu.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        document.body.removeChild(tempDiv);
    }).catch(err => {
        console.error("HTML2Canvas hatası:", err);
        alert("Rüya kartı oluşturulamadı. Konsolu kontrol edin.");
        document.body.removeChild(tempDiv);
    });
}

    function saveDream() {
        const dreamText = document.getElementById('dreamInput').value;
        const resultDiv = document.getElementById('result');

        if (dreamText.trim() === '' || resultDiv.innerHTML.trim() === '') {
            alert('Lütfen önce rüyanızı yazın ve yorumlatın.');
            return;
        }

        const analysisText = resultDiv.innerText;

        const textContent = `Rüya: ${dreamText}\n\n${analysisText}`;

        const blob = new Blob([textContent], { type: 'text/plain' });

        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'ruya_yorumu.txt';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>