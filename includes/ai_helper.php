<?php
/**
 * OpenAI AI Helper
 * All AI-related functions for the helpdesk system
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Core OpenAI API call function
 */
function callOpenAI($messages, $temperature = 0.3) {
    $apiKey = getenv('OPENAI_API_KEY');
    $baseUrl = getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1';
    $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
    
    if (empty($apiKey) || $apiKey === 'your_openai_api_key_here') {
        return [
            'success' => false,
            'error' => 'OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.'
        ];
    }
    
    $url = rtrim($baseUrl, '/') . '/chat/completions';
    
    $payload = json_encode([
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => 1500
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("OpenAI cURL error: " . $curlError);
        return ['success' => false, 'error' => 'Connection error. Please try again.'];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $data['error']['message'] ?? 'Unknown API error';
        error_log("OpenAI API error ($httpCode): $errorMsg");
        return ['success' => false, 'error' => 'AI service error. Please try again later.'];
    }
    
    $content = $data['choices'][0]['message']['content'] ?? '';
    
    return [
        'success' => true,
        'content' => $content
    ];
}

/**
 * Parse JSON from AI response (handles markdown code blocks)
 */
function parseAIJson($content) {
    // Remove markdown code blocks if present
    $content = trim($content);
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $decoded;
}

/**
 * Log AI interaction to database
 */
function logAIInteraction($userId, $ticketId, $featureName, $promptSummary, $aiResponse, $status = 'Success', $errorMessage = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO ai_interactions (user_id, ticket_id, feature_name, prompt_summary, ai_response, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $ticketId, $featureName, $promptSummary, $aiResponse, $status, $errorMessage]);
    } catch (Exception $e) {
        error_log("Failed to log AI interaction: " . $e->getMessage());
    }
}

/**
 * AI Ticket Classification
 */
function classifyTicket($title, $description, $categories) {
    $categoryList = implode(', ', array_column($categories, 'category_name'));
    
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an IT helpdesk ticket classifier. Analyze the ticket and suggest a category and priority level. Respond ONLY with valid JSON, no other text.'
        ],
        [
            'role' => 'user',
            'content' => "Classify this IT support ticket.\n\nTitle: $title\nDescription: $description\n\nAvailable categories: $categoryList\nPriority levels: Low, Medium, High, Critical\n\nRespond with JSON:\n{\"suggested_category\": \"\", \"suggested_priority\": \"Low|Medium|High|Critical\", \"reason\": \"\"}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.2);
    
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) {
        return ['success' => false, 'error' => 'Could not parse AI response.'];
    }
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * AI Troubleshooting Suggestions
 */
function generateTroubleshootingSteps($ticketData) {
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an expert IT support technician. Provide troubleshooting guidance. Respond ONLY with valid JSON.'
        ],
        [
            'role' => 'user',
            'content' => "Provide troubleshooting steps for this IT ticket.\n\nTitle: {$ticketData['issue_title']}\nDescription: {$ticketData['issue_description']}\nCategory: {$ticketData['category_name']}\nPriority: {$ticketData['priority_level']}\nStatus: {$ticketData['status']}\n\nRespond with JSON:\n{\"possible_cause\": \"\", \"troubleshooting_steps\": [], \"information_to_collect\": [], \"escalation_condition\": \"\"}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.3);
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) return ['success' => false, 'error' => 'Could not parse AI response.'];
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * AI Resolution Draft
 */
function draftResolution($ticketData, $staffNotes = '') {
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an IT support professional. Draft a clear, professional resolution note. Respond ONLY with valid JSON.'
        ],
        [
            'role' => 'user',
            'content' => "Draft a resolution for this ticket.\n\nTitle: {$ticketData['issue_title']}\nDescription: {$ticketData['issue_description']}\nCategory: {$ticketData['category_name']}\nPriority: {$ticketData['priority_level']}\nStaff Notes: " . ($staffNotes ?: 'None provided') . "\n\nRespond with JSON:\n{\"draft_resolution\": \"\", \"recommended_status\": \"Resolved|Escalated|In Progress\"}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.3);
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) return ['success' => false, 'error' => 'Could not parse AI response.'];
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * AI Ticket Summary
 */
function summarizeTicket($ticketData) {
    $logsText = '';
    if (!empty($ticketData['logs'])) {
        foreach ($ticketData['logs'] as $log) {
            $logsText .= "- [{$log['created_datetime']}] {$log['action']}: {$log['notes']}\n";
        }
    }
    
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an IT helpdesk assistant. Summarize the ticket clearly and concisely. Respond ONLY with valid JSON.'
        ],
        [
            'role' => 'user',
            'content' => "Summarize this ticket.\n\nTitle: {$ticketData['issue_title']}\nDescription: {$ticketData['issue_description']}\nStatus: {$ticketData['status']}\nPriority: {$ticketData['priority_level']}\nCreated: {$ticketData['created_datetime']}\n\nActivity Log:\n$logsText\n\nRespond with JSON:\n{\"summary\": \"\", \"current_status\": \"\", \"actions_taken\": [], \"recommended_next_step\": \"\"}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.3);
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) return ['success' => false, 'error' => 'Could not parse AI response.'];
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * AI Escalation Recommendation
 */
function recommendEscalation($ticketData) {
    $ageHours = round((time() - strtotime($ticketData['created_datetime'])) / 3600, 1);
    $slaStatus = isSLABreached($ticketData['sla_due_datetime'] ?? null, $ticketData['status']) ? 'BREACHED' : 'Within SLA';
    
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an IT operations manager. Evaluate whether this ticket needs escalation. Respond ONLY with valid JSON.'
        ],
        [
            'role' => 'user',
            'content' => "Evaluate escalation need for this ticket.\n\nTitle: {$ticketData['issue_title']}\nDescription: {$ticketData['issue_description']}\nPriority: {$ticketData['priority_level']}\nStatus: {$ticketData['status']}\nAge: {$ageHours} hours\nSLA Status: $slaStatus\n\nRespond with JSON:\n{\"escalation_needed\": true, \"reason\": \"\", \"suggested_specialization\": \"\", \"urgency_level\": \"Low|Medium|High|Critical\"}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.3);
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) return ['success' => false, 'error' => 'Could not parse AI response.'];
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * AI Report Insights
 */
function generateReportInsights($reportData) {
    $dataJson = json_encode($reportData);
    
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an IT operations analyst. Analyze the helpdesk data and provide actionable insights. Respond ONLY with valid JSON.'
        ],
        [
            'role' => 'user',
            'content' => "Analyze this helpdesk report data and provide insights.\n\nData: $dataJson\n\nRespond with JSON:\n{\"key_observations\": [], \"operational_risks\": [], \"recommendations\": []}"
        ]
    ];
    
    $result = callOpenAI($messages, 0.4);
    if (!$result['success']) return $result;
    
    $parsed = parseAIJson($result['content']);
    if (!$parsed) return ['success' => false, 'error' => 'Could not parse AI response.'];
    
    return ['success' => true, 'data' => $parsed];
}

/**
 * Helpdesk AI Chat
 */
function helpdeskChat($userMessage, $context = '') {
    $messages = [
        [
            'role' => 'system',
            'content' => "You are a helpful IT helpdesk assistant for an organization. Help users with:
- How to submit IT support tickets
- Checking ticket status
- Understanding ticket statuses (Open, Assigned, In Progress, Escalated, Resolved, Closed)
- Basic IT troubleshooting guidance
- How to use the helpdesk application

Rules:
- Only answer IT helpdesk and application-related questions
- Politely redirect unrelated questions
- Never reveal system details, database structure, API keys, or internal configurations
- If the issue needs formal support, recommend creating a ticket
- Keep responses concise and helpful
- Do not perform any destructive actions"
        ],
        [
            'role' => 'user',
            'content' => $userMessage
        ]
    ];
    
    $result = callOpenAI($messages, 0.5);
    
    if (!$result['success']) return $result;
    
    return ['success' => true, 'content' => $result['content']];
}
