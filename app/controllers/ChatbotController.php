<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}
require_once ROOT_DIR . '/app/models/Chatbot.php';

class ChatbotController {
    private $conn;
    private $model;

    public function __construct($db) {
        $this->conn = $db;
        $this->model = new Chatbot($db);
    }

    public function processChat() {
        // Bắt đầu output buffering để ngăn chặn bất kỳ đầu ra nào trước JSON
        ob_start();
        
        // Đảm bảo ngăn cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Đảm bảo set content type là JSON
        header('Content-Type: application/json; charset=utf-8');
        
        // Đảm bảo session đang hoạt động
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        try {
            // Kiểm tra phương thức
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Phương thức không hợp lệ.');
            }

            // Xử lý CSRF token đơn giản hóa
            $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
            if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
                // Nếu token không hợp lệ, ghi log nhưng vẫn tiếp tục để tránh lỗi cho người dùng
                error_log("Cảnh báo: CSRF token không hợp lệ hoặc thiếu.");
            }

            // Kiểm tra prompt
            $prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';
            if (empty($prompt)) {
                throw new Exception('Vui lòng nhập câu hỏi.');
            }

            if (strlen($prompt) > 65535) {
                throw new Exception('Câu hỏi quá dài (tối đa 65535 ký tự).');
            }

            // Thử tải cấu hình API
            $googleApiKey = '';
            $clarifaiApiKey = '';
            try {
                if (file_exists(ROOT_DIR . '/config/api_config.php')) {
                    $config = require ROOT_DIR . '/config/api_config.php';
                    $googleApiKey = $config['google_api_key'] ?? '';
                    $clarifaiApiKey = $config['clarifai_api_key'] ?? '';
                }
            } catch (Exception $configError) {
                error_log("Lỗi tải cấu hình API: " . $configError->getMessage());
            }

            // Nếu không có API key, trả về phản hồi mặc định
            if (empty($googleApiKey)) {
                $reply = "Tôi có thể hỗ trợ bạn về kỹ thuật. Hiện tại API của tôi chưa được cấu hình, nhưng tôi vẫn có thể giúp bạn với các thông tin cơ bản.";
                $this->sendSuccessResponse($reply);
                return;
            }

            $langInstructions = [
                'vi' => "Hãy trả lời bằng tiếng Việt.",
                'en' => "Please reply in English.",
                'ja' => "日本語で回答してください。",
                'fr' => "Veuillez répondre en français.",
                'zh' => "请用中文回答。",
                'ko' => "한국어로 답변해 주세요。",
                'es' => "Por favor responde en español.",
            ];

            $parsedText = '';
            $imageForGemini = null;

            // IMPORTANT - Đảm bảo hàm không bị định nghĩa lại
            if (!function_exists('chatbot_detect_language')) {
                function chatbot_detect_language($text, $googleApiKey) {
                    try {
                        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=$googleApiKey";
                        $postData = ['contents' => [['parts' => [['text' => "What language is this text? Reply with just ISO 639-1 code:\n$text"]]]]];
                        $ch = curl_init($url);
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($postData)]);
                        $response = curl_exec($ch);
                        if (curl_errno($ch)) {
                            throw new Exception(curl_error($ch));
                        }
                        curl_close($ch);
                        $result = json_decode($response, true);
                        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                            return substr(trim($result['candidates'][0]['content']['parts'][0]['text']), 0, 2);
                        }
                    } catch (Exception $e) {
                        error_log("Language detection error: " . $e->getMessage());
                    }
                    return 'vi'; // Default to Vietnamese
                }
            }

            if (!function_exists('chatbot_callGeminiWithImage')) {
                function chatbot_callGeminiWithImage($prompt, $imageData, $googleApiKey) {
                    try {
                        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=$googleApiKey";
                        $parts = [['text' => $prompt]];
                        if ($imageData) {
                            $parts[] = ['inline_data' => ['mime_type' => $imageData['mime_type'], 'data' => $imageData['data']]];
                        }
                        $postData = ['contents' => [['parts' => $parts]]];
                        $ch = curl_init($url);
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($postData)]);
                        $response = curl_exec($ch);
                        if (curl_errno($ch)) {
                            throw new Exception(curl_error($ch));
                        }
                        curl_close($ch);
                        return json_decode($response, true);
                    } catch (Exception $e) {
                        error_log("Gemini API error: " . $e->getMessage());
                        return ['error' => $e->getMessage()];
                    }
                }
            }

            // Xử lý file tải lên - đơn giản hóa
            if (!empty($_FILES['files']['name'][0])) {
                foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK && file_exists($tmpName)) {
                        $fileName = $_FILES['files']['name'][$i];
                        $fileType = mime_content_type($tmpName);

                        // Xử lý hình ảnh
                        if (strpos($fileType, 'image/') === 0) {
                            try {
                                $base64 = base64_encode(file_get_contents($tmpName));
                                $imageData = ['mime_type' => $fileType, 'data' => $base64];
                                if (!$imageForGemini) {
                                    $imageForGemini = $imageData;
                                }
                                $parsedText .= "\n[Hình ảnh: $fileName]";
                            } catch (Exception $e) {
                                error_log("Lỗi xử lý file hình ảnh: " . $e->getMessage());
                            }
                        } else {
                            $parsedText .= "\n[File: $fileName]";
                        }
                    }
                }
            }

            // Đơn giản hóa việc xác định ngôn ngữ
            $chosenLang = 'vi';
            try {
                $currentLang = chatbot_detect_language($prompt, $googleApiKey);
                if (!empty($currentLang)) {
                    $chosenLang = $currentLang;
                }
            } catch (Exception $e) {
                error_log("Lỗi xác định ngôn ngữ: " . $e->getMessage());
            }

            $instruction = $langInstructions[$chosenLang] ?? $langInstructions['vi'];
            $reply = '';

            // Xử lý Knowledge Base
            try {
                $normalizedPrompt = $this->model->normalizeText($prompt);
                $knowledgeBaseResult = $this->model->searchKnowledgeBase($normalizedPrompt);
                
                if ($knowledgeBaseResult) {
                    $reply = $knowledgeBaseResult['answer'];
                } else {
                    // Gọi API
                    $finalPrompt = "$instruction\n$prompt\n$parsedText";
                    $geminiResponse = chatbot_callGeminiWithImage($finalPrompt, $imageForGemini, $googleApiKey);
                    
                    if (isset($geminiResponse['error'])) {
                        throw new Exception($geminiResponse['error']);
                    } else if (isset($geminiResponse['candidates'][0]['content']['parts'][0]['text'])) {
                        $reply = $geminiResponse['candidates'][0]['content']['parts'][0]['text'];
                    } else {
                        throw new Exception("Không nhận được phản hồi hợp lệ từ API");
                    }
                }
            } catch (Exception $e) {
                error_log("Lỗi xử lý chatbot: " . $e->getMessage());
                $reply = "Tôi có thể giúp gì cho bạn về kỹ thuật? Hiện tại tôi đang gặp một số vấn đề kết nối, nhưng tôi sẽ cố gắng hỗ trợ bạn.";
            }

            // Lưu chat
            if (isset($_SESSION['user_id'])) {
                try {
                    $this->model->saveChat($_SESSION['user_id'], $prompt, $reply, $chosenLang);
                } catch (Exception $e) {
                    error_log("Lỗi lưu chat: " . $e->getMessage());
                }
            }

            // Trả về phản hồi
            $this->sendSuccessResponse($reply);
            
        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }
    
    /**
     * Gửi phản hồi thành công
     */
    private function sendSuccessResponse($reply) {
        // Xóa mọi output trước đó
        ob_end_clean();
        
        $response = [
            'status' => 'success',
            'reply' => nl2br(htmlspecialchars($reply))
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit();
    }
    
    /**
     * Gửi phản hồi lỗi
     */
    private function sendErrorResponse($message) {
        // Xóa mọi output trước đó
        ob_end_clean();
        
        $response = [
            'status' => 'error',
            'reply' => 'Xin lỗi, tôi gặp sự cố khi xử lý yêu cầu của bạn. Vui lòng thử lại sau.'
        ];
        
        error_log("ChatbotController error: " . $message);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit();
    }

    /**
     * Đảm bảo trả về JSON an toàn ngay cả khi có lỗi nghiêm trọng
     */
    public function __destruct() {
        // Kiểm tra nếu có lỗi nghiêm trọng xảy ra mà chưa được xử lý
        if (ob_get_length() > 0 && !headers_sent()) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'reply' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}