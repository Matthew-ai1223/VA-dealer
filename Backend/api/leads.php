<?php
/**
 * Leads API — capture, tracking (public) + CRM (admin)
 *
 * POST   leads.php                    Create lead (form submission)
 * POST   leads.php?action=track         Log activity (view, WhatsApp click)
 * GET    leads.php                      List leads (admin)
 * GET    leads.php?id=1                 Single lead (admin)
 * GET    leads.php?stats=1              Overview stats (admin)
 * PUT    leads.php?id=1                 Update status (admin)
 * POST   leads.php?action=note          Add note (admin)
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/mail.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Car.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$leadModel = new Lead();
$carModel = new Car();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

function validateLeadInput(array $input, bool $requireContact = true): array
{
    $errors = [];
    $name = trim($input['full_name'] ?? $input['fullName'] ?? '');
    $phone = trim($input['phone_number'] ?? $input['phoneNumber'] ?? '');

    if ($requireContact && $name === '') {
        $errors[] = 'Full name is required';
    }
    if ($requireContact && $phone === '') {
        $errors[] = 'Phone number is required';
    }
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    return $errors;
}

function mapInquiryToActivity(string $type): string
{
    return match ($type) {
        'book_inspection'   => 'inspection_request',
        'request_callback'  => 'callback_request',
        'whatsapp'          => 'whatsapp_click',
        default             => 'contact_request',
    };
}

try {
    // --- Public: track activity ---
    if ($method === 'POST' && $action === 'track') {
        $type = $input['activity_type'] ?? '';
        $carId = isset($input['car_id']) ? (int) $input['car_id'] : null;
        $allowed = ['vehicle_viewed', 'whatsapp_click', 'vehicle_inquiry'];

        if (!in_array($type, $allowed, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid activity type'], 400);
        }

        $leadId = null;

        if ($type === 'whatsapp_click' && $carId) {
            $car = $carModel->getById($carId, false);
            if ($car) {
                $leadId = $leadModel->create([
                    'full_name'          => 'WhatsApp Inquiry',
                    'phone_number'       => 'Via WhatsApp',
                    'interested_vehicle' => $car['title'],
                    'car_id'             => $carId,
                    'inquiry_type'       => 'whatsapp',
                    'source'             => 'whatsapp',
                    'message'            => 'Customer clicked WhatsApp contact button',
                ]);
                $lead = $leadModel->getById($leadId);
                if ($lead) {
                    sendLeadNotification($lead);
                }
            }
        }

        $leadModel->logActivity($type, $carId, $leadId, $input['meta'] ?? null);
        jsonResponse(['success' => true, 'lead_id' => $leadId]);
    }

    // --- Public: create lead from form ---
    if ($method === 'POST' && $action !== 'note') {
        $errors = validateLeadInput($input);
        if ($errors) {
            jsonResponse(['success' => false, 'message' => implode('. ', $errors)], 422);
        }

        $carId = isset($input['car_id']) ? (int) $input['car_id'] : null;
        $vehicle = trim($input['interested_vehicle'] ?? $input['interestedVehicle'] ?? '');

        if ($vehicle === '' && $carId) {
            $car = $carModel->getById($carId, false);
            $vehicle = $car['title'] ?? 'Unknown vehicle';
        }

        $inquiryType = $input['inquiry_type'] ?? $input['inquiryType'] ?? 'request_info';
        if (!in_array($inquiryType, Lead::INQUIRY_TYPES, true)) {
            $inquiryType = 'request_info';
        }

        $source = $input['source'] ?? 'website';
        if (!in_array($source, Lead::SOURCES, true)) {
            $source = 'website';
        }

        $leadId = $leadModel->create([
            'full_name'          => trim($input['full_name'] ?? $input['fullName']),
            'phone_number'       => trim($input['phone_number'] ?? $input['phoneNumber']),
            'email'              => trim($input['email'] ?? '') ?: null,
            'car_id'             => $carId,
            'interested_vehicle' => $vehicle,
            'budget'             => isset($input['budget']) && $input['budget'] !== '' ? (float) $input['budget'] : null,
            'inquiry_type'       => $inquiryType,
            'source'             => $source,
            'message'            => trim($input['message'] ?? '') ?: null,
        ]);

        $activityType = mapInquiryToActivity($inquiryType);
        $leadModel->logActivity($activityType, $carId, $leadId, ['form' => $inquiryType]);

        $lead = $leadModel->getById($leadId);
        sendLeadNotification($lead);

        $whatsappPayload = [
            'full_name'          => $lead['full_name'],
            'phone_number'       => $lead['phone_number'],
            'email'              => $lead['email'],
            'car_id'             => $lead['car_id'],
            'interested_vehicle' => $lead['interested_vehicle'],
            'budget'             => $lead['budget'],
            'inquiry_type'       => $lead['inquiry_type'],
            'message'            => $lead['message'],
            'price_formatted'    => '',
        ];
        if (!empty($lead['car_id'])) {
            $carForWa = $carModel->getById((int) $lead['car_id'], false);
            if ($carForWa) {
                $whatsappPayload['price_formatted'] = $carForWa['price_formatted'] ?? formatPrice((float) $carForWa['price']);
            }
        }

        jsonResponse([
            'success'      => true,
            'message'      => 'Request saved! Continue on WhatsApp to send your inquiry.',
            'lead_id'      => $leadId,
            'whatsapp_url' => leadWhatsAppLink($whatsappPayload),
        ], 201);
    }

    // --- Admin routes below ---
    requireAdmin();

    if ($method === 'GET' && isset($_GET['stats'])) {
        jsonResponse([
            'success' => true,
            'overview' => $leadModel->getOverviewStats(),
            'sources'  => $leadModel->getSourceStats(),
            'whatsapp' => $leadModel->getWhatsAppStats(),
        ]);
    }

    if ($method === 'GET' && $id) {
        $lead = $leadModel->getById($id);
        if (!$lead) {
            jsonResponse(['success' => false, 'message' => 'Lead not found'], 404);
        }
        jsonResponse([
            'success'    => true,
            'data'       => $lead,
            'notes'      => $leadModel->getNotes($id),
            'activities' => $leadModel->getActivities($id),
        ]);
    }

    if ($method === 'GET') {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $leadModel->getAll([
            'status' => $_GET['status'] ?? '',
            'source' => $_GET['source'] ?? '',
            'search' => $_GET['search'] ?? '',
        ], $page, 20);

        jsonResponse(['success' => true, 'data' => $result]);
    }

    if ($method === 'PUT' && $id) {
        $status = $input['status'] ?? '';
        $assigned = $input['assigned_to'] ?? null;

        if (!$leadModel->updateStatus($id, $status, $assigned)) {
            jsonResponse(['success' => false, 'message' => 'Invalid status or lead not found'], 400);
        }
        jsonResponse(['success' => true, 'data' => $leadModel->getById($id)]);
    }

    if ($method === 'POST' && $action === 'note' && $id) {
        $note = trim($input['note'] ?? '');
        if ($note === '') {
            jsonResponse(['success' => false, 'message' => 'Note cannot be empty'], 422);
        }
        $leadModel->addNote($id, $_SESSION['admin_username'] ?? 'admin', $note);
        jsonResponse(['success' => true, 'notes' => $leadModel->getNotes($id)]);
    }

    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}
