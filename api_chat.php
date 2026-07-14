<?php
include_once("dbcon.php");

header('Content-Type: application/json');

// Ensure database table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS hs_ai_chats (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(255) NOT NULL,
    patient_id INT NULL,
    sender VARCHAR(10) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_session (session_key),
    KEY idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($conn, $createTableQuery);

$session_key = session_id();
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'history') {
    // Retrieve previous messages
    if ($patient_id) {
        $sql = "SELECT sender, message, created_at FROM hs_ai_chats 
                WHERE session_key = ? OR patient_id = ? 
                ORDER BY chat_id ASC LIMIT 50";
        list($stmt, $result) = db_query_prepared($conn, $sql, [$session_key, $patient_id], 'si');
    } else {
        $sql = "SELECT sender, message, created_at FROM hs_ai_chats 
                WHERE session_key = ? 
                ORDER BY chat_id ASC LIMIT 50";
        list($stmt, $result) = db_query_prepared($conn, $sql, [$session_key], 's');
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = [
            'sender' => $row['sender'],
            'message' => $row['message'],
            'created_at' => $row['created_at']
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'history' => $history]);
    exit();
}

if ($action === 'send') {
    // Read JSON POST body
    $inputData = json_decode(file_get_contents('php://input'), true);
    $message = isset($inputData['message']) ? trim($inputData['message']) : '';
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit();
    }
    
    // Save user's message
    $sql = "INSERT INTO hs_ai_chats (session_key, patient_id, sender, message) VALUES (?, ?, 'user', ?)";
    list($stmt, $res) = db_query_prepared($conn, $sql, [$session_key, $patient_id, $message], 'sis');
    mysqli_stmt_close($stmt);
    
    // Fetch doctors from database for context
    $docSql = "SELECT d.doctor_fname, d.doctor_lname, c.category_title FROM hs_doctor d 
               LEFT JOIN hs_category c ON d.category_id = c.category_id";
    $docRes = mysqli_query($conn, $docSql);
    $doctors_list = "";
    if ($docRes) {
        while ($docRow = mysqli_fetch_assoc($docRes)) {
            $doctors_list .= "- Dr. " . $docRow['doctor_fname'] . " " . $docRow['doctor_lname'] . " (" . ($docRow['category_title'] ?? 'General Medicine') . ")\n";
        }
    }
    
    // If Gemini key is empty, use a smart fallback
    if (empty(GEMINI_API_KEY)) {
        $fallback_response = "Hello! I am the Hope Rise Medical Assistant. I'm currently running in sandbox mode (the Gemini API key is not configured yet). However, I can help you with some common inquiries:
- **Hospital Hours**: Mon-Fri 8am-5pm, Sat 8am-12pm.
- **Helpline**: +1-800-HOPERISE (24/7).
- **Book an Appointment**: You can book online at [Appointment Booking Page](add_appointment.php).
- **Our Doctors**: We have specialists in Cardiology, Pediatrics, Neurology, and more.
Please ask your administrator to configure the API key in `dbcon.php` to enable full AI capability!";
        
        // Save fallback response as AI
        $sql = "INSERT INTO hs_ai_chats (session_key, patient_id, sender, message) VALUES (?, ?, 'ai', ?)";
        list($stmt, $res) = db_query_prepared($conn, $sql, [$session_key, $patient_id, $fallback_response], 'sis');
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => true, 'response' => $fallback_response]);
        exit();
    }
    
    // Construct System Prompt / Instructions
    $systemPrompt = "You are the Hope Rise Medical AI Assistant. Your goal is to guide patients and answer queries about doctors, appointment booking, disease information, and hospital details.
Keep responses professional, polite, concise, and structured. Use short markdown paragraphs, lists, or line breaks to make it readable.
When suggesting doctors or matching symptoms, recommend ONLY doctors from our hospital if relevant. Here is our list of doctors:\n" . $doctors_list . "\n
Important Hospital Details:
- Hours: Monday - Friday (8:00 AM - 5:00 PM), Saturday (8:00 AM - 12:00 PM), Sunday (Emergency Only).
- Phone & 24/7 Helpline: +1-800-HOPERISE (1-800-467-3747) or +1-234-567-8900.
- Address: 123 Health Ave, Medical District.
- Key Links:
  - Online Appointment Booking: add_appointment.php (Suggest this page link for booking appointments).
  - Medical Services list: service.php
  - Contact Us: contact.php
  - Patient Registration: patient_reg.php
  - Login Page: login.php
Rules:
1. Always advise patients to seek emergency care or consult with a physical doctor for critical symptoms.
2. Link to add_appointment.php when helping them book appointments.
3. Be helpful, concise, and polite.";

    // Get last 10 messages for conversation history
    if ($patient_id) {
        $histSql = "SELECT sender, message FROM hs_ai_chats 
                    WHERE session_key = ? OR patient_id = ? 
                    ORDER BY chat_id DESC LIMIT 11"; // user's current message is already in DB, retrieve previous ones
        list($stmt, $histRes) = db_query_prepared($conn, $histSql, [$session_key, $patient_id], 'si');
    } else {
        $histSql = "SELECT sender, message FROM hs_ai_chats 
                    WHERE session_key = ? 
                    ORDER BY chat_id DESC LIMIT 11";
        list($stmt, $histRes) = db_query_prepared($conn, $histSql, [$session_key], 's');
    }
    
    $historyRows = [];
    while ($row = mysqli_fetch_assoc($histRes)) {
        $historyRows[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // Reverse to chronological order
    $historyRows = array_reverse($historyRows);
    
    // Build Gemini request content array
    $contents = [];
    foreach ($historyRows as $hist) {
        $role = ($hist['sender'] === 'user') ? 'user' : 'model';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $hist['message']]]
        ];
    }
    
    // Prepare API call payload
    $payload = [
        'contents' => $contents,
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];
    
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // Fallback response in case of API error (like invalid key, quota, etc.)
        $errResponse = "I apologize, but I am having trouble connecting to my central brain right now. However, I can still provide the following quick assistance:\n" .
                       "- **Helpline**: +1-800-HOPERISE (24/7)\n" .
                       "- **Book an Appointment**: [Click here to book online](add_appointment.php)\n" .
                       "- **Working Hours**: Mon-Fri 8am-5pm, Sat 8am-12pm.";
        
        // Save fallback response as AI
        $sql = "INSERT INTO hs_ai_chats (session_key, patient_id, sender, message) VALUES (?, ?, 'ai', ?)";
        list($stmt, $res) = db_query_prepared($conn, $sql, [$session_key, $patient_id, $errResponse], 'sis');
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => true, 'response' => $errResponse, 'debug_info' => 'Gemini API status code: ' . $httpCode]);
        exit();
    }
    
    $resData = json_decode($response, true);
    $aiText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    if (empty($aiText)) {
        $aiText = "I received an empty response. Please try rephrasing or consulting our directory.";
    }
    
    // Save AI response to DB
    $sql = "INSERT INTO hs_ai_chats (session_key, patient_id, sender, message) VALUES (?, ?, 'ai', ?)";
    list($stmt, $res) = db_query_prepared($conn, $sql, [$session_key, $patient_id, $aiText], 'sis');
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'response' => $aiText]);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
