<?php
$knowledgeBase = [
    ["keywords" => ["đăng ký", "khóa học"], "answer" => "Để đăng ký khóa học, bạn hãy nhấp vào nút 'Đăng ký khóa học' ở trang khóa học, sau đó làm theo hướng dẫn. Nếu bạn chưa đăng nhập, hãy đăng nhập với vai trò sinh viên trước nhé!"],
    ["keywords" => ["tài liệu", "học tập", "tìm"], "answer" => "Tài liệu học tập có thể được tìm thấy trong trang khóa học mà bạn đã đăng ký. Sau khi đăng nhập, vào phần 'Khoá Học Của Tôi' để truy cập tài liệu."],
    ["keywords" => ["lịch học"], "answer" => "Lịch học của bạn có thể được xem trong phần 'Lịch Học' sau khi đăng nhập. Vào trang 'Dashboard' của bạn và chọn mục 'Lịch Học' để xem chi tiết."],
    ["keywords" => ["liên hệ", "giáo viên"], "answer" => "Bạn có thể liên hệ với giáo viên qua phần 'Liên Hệ Giáo Viên' trong trang khóa học. Ngoài ra, bạn cũng có thể gửi tin nhắn qua mục 'Liên Hệ' ở cuối trang."],
    ["keywords" => ["hỗ trợ", "kỹ thuật"], "answer" => "Tất nhiên rồi! Chúng tôi cung cấp hỗ trợ kỹ thuật 24/7 qua email và chat trực tiếp. Bạn có thể gửi email đến hoctap435@gmail.com hoặc tiếp tục chat với tôi để được hỗ trợ ngay."],
    ["keywords" => ["nội dung", "cập nhật"], "answer" => "Có, các khóa học được cập nhật định kỳ để đảm bảo thông tin luôn mới và phù hợp với nhu cầu học tập."],
    ["keywords" => ["đặt lại", "mật khẩu"], "answer" => "Để đặt lại mật khẩu, nhấp vào 'Quên Mật Khẩu' ở form đăng nhập, nhập email của bạn và làm theo hướng dẫn để nhận mã xác nhận."]
];

// Log feedback
function logFeedback($message, $isHelpful) {
    $logEntry = date('Y-m-d H:i:s') . " | Message: " . $message . " | Helpful: " . ($isHelpful ? "Yes" : "No") . "\n";
    file_put_contents('chatbot_feedback.log', $logEntry, FILE_APPEND);
}

// Chatbot logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    header('Content-Type: application/json');

    $googleApiKey = 'AIzaSyCTJDNiyCo2bDWI2qqvlUVzgpgdBI-sszc'; 
    $clarifaiApiKey = '115ff8c3a2094c7a928b3e3e8dbc7a78'; 

    $langInstructions = [
        'vi' => "Hãy trả lời bằng tiếng Việt.",
        'en' => "Please reply in English.",
        'ja' => "日本語で回答してください。",
        'fr' => "Veuillez répondre en français.",
        'zh' => "请用中文回答。",
        'ko' => "한국어로 답변해 주세요。",
        'es' => "Por favor responde en español.",
    ];

    $prompt = trim($_POST['prompt'] ?? '');
    $parsedText = '';
    $reply = '';

    function detectLanguage($text, $googleApiKey) {
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=$googleApiKey";
        $postData = [
            'contents' => [
                ['parts' => [['text' => "What language is this text? Reply with just ISO 639-1 code:\n$text"]]]
            ]
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($postData)]);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return substr(trim($result['candidates'][0]['content']['parts'][0]['text'] ?? 'en'), 0, 2);
    }

    function parseDocx($filePath) {
        $zip = new ZipArchive;
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
                $text = strip_tags($xml->asXML());
                $zip->close();
                return $text;
            }
            $zip->close();
        }
        return '';
    }

    function parsePdf($filePath) {
        $output = null;
        $returnVar = null;
        $pdftotextPath = __DIR__ . '/../../Poppler/Library/bin/pdftotext.exe';
        exec($pdftotextPath . " " . escapeshellarg($filePath) . " -", $output, $returnVar);
        return $returnVar === 0 ? implode("\n", $output) : '';
    }

    function callClarifai($imageData, $clarifaiApiKey) {
        $url = "https://api.clarifai.com/v2/models/general-image-detection/outputs";
        $postData = json_encode(["inputs" => [["data" => ["image" => ["base64" => $imageData['data']]]]]]);
        $headers = ['Authorization: Key ' . $clarifaiApiKey, 'Content-Type: application/json'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $postData]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    function callGeminiWithImage($prompt, $imageData, $googleApiKey) {
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=$googleApiKey";
        $parts = [['text' => $prompt]];
        if ($imageData) {
            $parts[] = ['inline_data' => ['mime_type' => $imageData['mime_type'], 'data' => $imageData['data']]];
        }
        $postData = ['contents' => [['parts' => $parts]]];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($postData)]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    if (!empty($_FILES['files']['name'][0])) {
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['files']['tmp_name'][$i];
                $fileName = $_FILES['files']['name'][$i];
                $fileType = mime_content_type($tmpName);

                if ($fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $parsedText .= "\n[DOCX: $fileName] " . parseDocx($tmpName);
                } elseif ($fileType === 'application/pdf') {
                    $parsedText .= "\n[PDF: $fileName] " . parsePdf($tmpName);
                } elseif (strpos($fileType, 'image/') === 0) {
                    $base64 = base64_encode(file_get_contents($tmpName));
                    $imageData = ['mime_type' => $fileType, 'data' => $base64];
                    $clarifaiResult = callClarifai($imageData, $clarifaiApiKey);
                    if (!empty($clarifaiResult['outputs'][0]['data']['concepts'])) {
                        $concepts = array_map(fn($c) => $c['name'], $clarifaiResult['outputs'][0]['data']['concepts']);
                        $parsedText .= "\n[Image: $fileName] Objects detected: " . implode(", ", $concepts);
                    }
                }
            }
        }
    }

    if ($prompt !== '') {
        $currentLang = detectLanguage($prompt, $googleApiKey);
        $_SESSION['user_languages'] = $_SESSION['user_languages'] ?? [];
        $_SESSION['user_languages'][] = $currentLang;
        $chosenLang = $currentLang;
    } else {
        $langCounts = array_count_values($_SESSION['user_languages'] ?? []);
        arsort($langCounts);
        $chosenLang = array_key_first($langCounts) ?? 'vi';
    }
    $instruction = $langInstructions[$chosenLang] ?? $langInstructions['en'];

    function normalizeText($text) {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[đ]/u', 'd', $text);
        $text = preg_replace('/[áàảãạăắằẳẵặâấầẩẫậ]/u', 'a', $text);
        $text = preg_replace('/[éèẻẽẹêếềểễệ]/u', 'e', $text);
        $text = preg_replace('/[íìỉĩị]/u', 'i', $text);
        $text = preg_replace('/[óòỏõọôốồổỗộơớờởỡợ]/u', 'o', $text);
        $text = preg_replace('/[úùủũụưứừửữự]/u', 'u', $text);
        $text = preg_replace('/[ýỳỷỹỵ]/u', 'y', $text);
        return $text;
    }

    $normalizedPrompt = normalizeText($prompt);
    $foundInKnowledgeBase = false;

    foreach ($knowledgeBase as $entry) {
        $keywords = array_map('normalizeText', $entry['keywords']);
        $matchCount = 0;
        foreach ($keywords as $keyword) {
            if (strpos($normalizedPrompt, $keyword) !== false) $matchCount++;
        }
        if ($matchCount >= count($keywords) * 0.7) {
            $reply = $entry['answer'];
            $foundInKnowledgeBase = true;
            break;
        }
    }

    if (!$foundInKnowledgeBase) {
        $finalPrompt = "$instruction\n$prompt\n$parsedText";
        $imageForGemini = null;
        if (!empty($_FILES['files']['name'][0])) {
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['files']['tmp_name'][$i];
                    $fileType = mime_content_type($tmpName);
                    if (strpos($fileType, 'image/') === 0) {
                        $base64 = base64_encode(file_get_contents($tmpName));
                        $imageForGemini = ['mime_type' => $fileType, 'data' => $base64];
                        break;
                    }
                }
            }
        }
        try {
            $geminiResponse = callGeminiWithImage($finalPrompt, $imageForGemini, $googleApiKey);
            $reply = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '(Không có phản hồi)';
        } catch (Exception $e) {
            $reply = "Lỗi khi gọi API: " . $e->getMessage();
        }
    }

    echo json_encode(['reply' => nl2br(htmlspecialchars($reply))]);
    exit;
}
?>