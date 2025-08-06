<?php

header('Content-Type: application/json');

// Hataları ekranda göstermeyi devre dışı bırak, bu tür hatalar beklenmedik çıktılara neden olabilir.
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Sadece promptText'i alıyoruz
$promptText = $data['promptText'] ?? '';

if (empty($promptText)) {
    echo json_encode(['error' => 'Geçerli bir prompt metni sağlanmadı.']);
    exit;
}

// API URL ve Anahtarınızı buraya ekleyin
$api_key = 'AIzaSyDUrewZk8C7aOFG-Q2a4o9TSo7RvuhqeGE'; 
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

// API isteği için verileri hazırla
$post_data = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $promptText]
            ]
        ]
    ]
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Hata kontrolü
if ($curl_errno) {
    echo json_encode(['error' => 'cURL hatası: ' . $curl_error]);
    exit;
}

if ($http_status != 200) {
    $error_data = json_decode($response, true);
    $error_message = $error_data['error']['message'] ?? 'API yanıtı başarısız oldu.';
    echo json_encode(['error' => "Hata: API yanıtı başarısız oldu. (" . $http_status . " - " . $error_message . ")"]);
    exit;
}

// Başarılı yanıt durumunda
$api_data = json_decode($response, true);

// Gemini API'nin yanıt yapısı
$ai_response_text = $api_data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($ai_response_text) {
    echo json_encode(['result' => $ai_response_text]);
} else {
    // API'den başarılı yanıt geldi ama beklenen metin içeriği yoksa
    echo json_encode(['error' => 'API yanıtı beklenen içeriği içermiyor.']);
}
?>