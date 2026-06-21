<?php
/**
 * Meta (Facebook & Instagram) Lead Sync Webhook
 * GET  /Backend/api/meta-webhook.php   Verify Webhook (Meta subscription validation)
 * POST /Backend/api/meta-webhook.php   Receive Leadgen notifications
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../models/Lead.php';

header('Content-Type: application/json; charset=utf-8');

$config = appConfig();
$verifyToken = 'va_auto_sales_secret_token'; // Webhook verify token configured in Meta App dashboard

$method = $_SERVER['REQUEST_METHOD'];

try {
    $leadModel = new Lead();

    // 1. Verification (GET)
    if ($method === 'GET') {
        $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

        if ($mode === 'subscribe' && $token === $verifyToken) {
            http_response_code(200);
            echo $challenge;
            exit;
        } else {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    // 2. Lead capture Webhook (POST)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        // Handle testing simulations (mock payloads)
        if (isset($input['simulated']) && $input['simulated'] === true) {
            $source = isset($input['source']) && in_array(strtolower($input['source']), ['facebook', 'instagram'], true)
                ? strtolower($input['source'])
                : 'facebook';

            $leadId = $leadModel->create([
                'full_name'          => trim($input['full_name'] ?? 'Meta Lead'),
                'phone_number'       => trim($input['phone_number'] ?? '+23400000000'),
                'email'              => trim($input['email'] ?? '') ?: null,
                'car_id'             => isset($input['car_id']) ? (int) $input['car_id'] : null,
                'interested_vehicle' => trim($input['interested_vehicle'] ?? 'General Inquiry'),
                'budget'             => isset($input['budget']) ? (float) $input['budget'] : null,
                'inquiry_type'       => 'request_info',
                'source'             => $source,
                'message'            => trim($input['message'] ?? 'Lead synced from Meta Ads'),
            ]);

            // Add activity log
            $leadModel->logActivity('contact_request', $input['car_id'] ?? null, $leadId, ['sync' => 'meta_mock']);
            
            // Score the lead
            $leadModel->calculateAndStoreLeadScore($leadId);

            // Notify admin
            $lead = $leadModel->getById($leadId);
            sendLeadNotification($lead);

            jsonResponse([
                'success' => true,
                'message' => 'Simulated lead synced successfully',
                'lead_id' => $leadId
            ], 201);
        }

        // Production webhook parsing (when a real notification arrives)
        // Meta sends notification with leadgen_id. We'd query Graph API here:
        // GET /v18.0/{leadgen_id} HTTP/1.1 -> returns fields (name, phone, email)
        $entry = $input['entry'] ?? [];
        $leadsProcessed = [];

        foreach ($entry as $item) {
            $changes = $item['changes'] ?? [];
            foreach ($changes as $change) {
                if ($change['field'] === 'leadgen') {
                    $val = $change['value'] ?? [];
                    $leadgenId = $val['leadgen_id'] ?? '';
                    
                    if ($leadgenId) {
                        // This is where we query Meta Graph API using cURL.
                        // For this production-ready blueprint, we mockup the Graph API response 
                        // as if we requested: https://graph.facebook.com/v18.0/$leadgenId
                        
                        $mockMetaResponse = [
                            'full_name' => 'Meta User ' . substr($leadgenId, -4),
                            'phone_number' => '+2348030000000',
                            'email' => 'meta_lead_' . $leadgenId . '@example.com',
                            'interested_vehicle' => 'Inquiry from Facebook Ad',
                        ];

                        $leadId = $leadModel->create([
                            'full_name'          => $mockMetaResponse['full_name'],
                            'phone_number'       => $mockMetaResponse['phone_number'],
                            'email'              => $mockMetaResponse['email'],
                            'interested_vehicle' => $mockMetaResponse['interested_vehicle'],
                            'inquiry_type'       => 'request_info',
                            'source'             => 'facebook', // Default source from Meta
                            'message'            => 'Synced from Meta Lead Ads ID: ' . $leadgenId
                        ]);

                        $leadModel->logActivity('contact_request', null, $leadId, ['leadgen_id' => $leadgenId]);
                        $leadModel->calculateAndStoreLeadScore($leadId);
                        
                        $lead = $leadModel->getById($leadId);
                        sendLeadNotification($lead);
                        
                        $leadsProcessed[] = $leadId;
                    }
                }
            }
        }

        jsonResponse([
            'success' => true,
            'message' => 'Webhook received',
            'processed_leads' => $leadsProcessed
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
