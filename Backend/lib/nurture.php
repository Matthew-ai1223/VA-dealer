<?php
/**
 * Automated Lead Nurturing Engine
 * Backend/lib/nurture.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/../models/Car.php';

function runLeadNurturing(): array
{
    $db = Database::getConnection();
    $carModel = new Car();
    $now = new DateTime();
    $logs = [];

    // Select leads in nurturing pipeline
    $sql = "SELECT * FROM leads 
            WHERE status NOT IN ('closed_won', 'closed_lost') 
              AND (follow_up_status IS NULL OR follow_up_status NOT IN ('completed', 'paused'))";
    $stmt = $db->query($sql);
    $leads = $stmt->fetchAll();

    foreach ($leads as $lead) {
        $leadId = (int) $lead['id'];
        $createdAt = new DateTime($lead['created_at']);
        $interval = $now->diff($createdAt);
        $days = (int) $interval->days;

        $currentStatus = $lead['follow_up_status'] ?? 'pending';
        $nextStatus = null;
        $nextDate = null;
        $subject = "";
        $body = "";

        $history = json_decode($lead['follow_up_history'] ?? '[]', true) ?: [];

        // Check nurturing rules
        if ($currentStatus === 'pending' && $days >= 1) {
            // Day 1: Thank you for interest
            $nextStatus = 'day1_sent';
            $nextDate = (new DateTime())->modify('+2 days'); // Schedule Day 3 (2 days from now)
            
            $subject = "Thank you for your interest in " . $lead['interested_vehicle'];
            $body = "Hi " . $lead['full_name'] . ",\n\n" .
                    "Thank you for your interest in the " . $lead['interested_vehicle'] . " at VA Auto Sales.\n" .
                    "Our team has received your request, and we are reviewing it.\n\n" .
                    "If you have any questions or would like to speak to a representative immediately, please contact us on WhatsApp at https://wa.me/" . appConfig()['whatsapp_number'] . ".\n\n" .
                    "Best regards,\n" .
                    "The VA Auto Sales Team";

        } elseif ($currentStatus === 'day1_sent' && $days >= 3) {
            // Day 3: Are you still interested?
            $nextStatus = 'day3_sent';
            $nextDate = (new DateTime())->modify('+4 days'); // Schedule Day 7 (4 days from now)

            $subject = "Still interested in the " . $lead['interested_vehicle'] . "?";
            $body = "Hi " . $lead['full_name'] . ",\n\n" .
                    "We noticed you were looking at the " . $lead['interested_vehicle'] . " a few days ago.\n" .
                    "Are you still interested in this vehicle? It is still available, but we've had several inquiries.\n\n" .
                    "Let us know if you'd like to schedule an inspection or test drive!\n\n" .
                    "Reply to this email or chat with us on WhatsApp: https://wa.me/" . appConfig()['whatsapp_number'] . "\n\n" .
                    "Best regards,\n" .
                    "The VA Auto Sales Team";

        } elseif ($currentStatus === 'day3_sent' && $days >= 7) {
            // Day 7: Similar vehicles available
            $nextStatus = 'day7_sent';
            $nextDate = (new DateTime())->modify('+7 days'); // Schedule Day 14 (7 days from now)

            $subject = "Similar vehicles available at VA Auto Sales";
            
            // Query similar vehicles from same brand or within 25% price range
            $similarList = "";
            try {
                $carId = (int) $lead['car_id'];
                $carBudget = $lead['budget'] ? (float) $lead['budget'] : 0.0;
                
                // Fetch up to 3 alternative available cars
                $filters = ['status' => 'available'];
                $allCars = $carModel->getAll($filters, false);
                $alternatives = [];
                
                foreach ($allCars as $car) {
                    if ($car['id'] === $carId) continue;
                    
                    // Brand match or price range match
                    $priceDiff = $carBudget > 0 ? abs($car['price'] - $carBudget) / $carBudget : 999;
                    if (strcasecmp($car['brand'], $lead['interested_vehicle']) === 0 || $priceDiff <= 0.3) {
                        $alternatives[] = $car;
                    }
                    if (count($alternatives) >= 3) break;
                }
                
                // Fallback to featured active cars if no close match
                if (empty($alternatives)) {
                    $alternatives = array_slice(array_filter($allCars, function($c) { return $c['featured']; }), 0, 3);
                }

                if (!empty($alternatives)) {
                    $similarList = "\nHere are some alternative vehicles you might like:\n";
                    foreach ($alternatives as $alt) {
                        $similarList .= "- " . $alt['title'] . " (" . $alt['price_formatted'] . ") - View details: " . fullUrl("Frontend/car.php?id=" . $alt['id']) . "\n";
                    }
                }
            } catch (Exception $e) {}

            $body = "Hi " . $lead['full_name'] . ",\n\n" .
                    "Since you are browsing for vehicles like the " . $lead['interested_vehicle'] . ", we wanted to share some similar deals that just arrived on our lot.\n" .
                    $similarList . "\n" .
                    "Would you like to book an inspection for any of these?\n\n" .
                    "Reply to this email or reach us on WhatsApp: https://wa.me/" . appConfig()['whatsapp_number'] . "\n\n" .
                    "Best regards,\n" .
                    "The VA Auto Sales Team";

        } elseif ($currentStatus === 'day3_sent' && $days >= 14) {
            // Day 14: Personal assistance finding right vehicle
            $nextStatus = 'completed';
            $nextDate = null;

            $subject = "Need help finding the right car?";
            $body = "Hi " . $lead['full_name'] . ",\n\n" .
                    "We'd love to help you find the perfect car. If you haven't found what you're looking for, our team can source specific models directly for you based on your budget.\n\n" .
                    "Let us know your budget and target vehicle, and we will do the searching for you!\n\n" .
                    "Get in touch on WhatsApp: https://wa.me/" . appConfig()['whatsapp_number'] . "\n\n" .
                    "Best regards,\n" .
                    "The VA Auto Sales Team";
        }

        // If a follow up is triggered, send the email and save history
        if ($nextStatus !== null) {
            $to = $lead['email'];
            $emailSent = false;
            
            if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $emailSent = sendNurtureEmail($to, $lead['full_name'], $subject, $body);
            }

            // Append to follow up history JSON
            $history[] = [
                'type' => $nextStatus,
                'sent_at' => date('Y-m-d H:i:s'),
                'subject' => $subject,
                'email' => $to ?: 'No email provided',
                'success' => $emailSent
            ];

            // Update lead record
            $updateSql = "UPDATE leads SET 
                            follow_up_status = ?, 
                            follow_up_history = ?, 
                            next_follow_up_date = ? 
                          WHERE id = ?";
            $stmtUpdate = $db->prepare($updateSql);
            $stmtUpdate->execute([
                $nextStatus,
                json_encode($history, JSON_UNESCAPED_UNICODE),
                $nextDate ? $nextDate->format('Y-m-d H:i:s') : null,
                $leadId
            ]);

            // Add an internal note to the lead's history for the CRM
            $stmtNote = $db->prepare("INSERT INTO lead_notes (lead_id, admin_username, note) VALUES (?, 'System Automation', ?)");
            $stmtNote->execute([
                $leadId,
                "Automated Nurturing: " . ucfirst(str_replace('_', ' ', $nextStatus)) . " sent to buyer. Subject: \"$subject\""
            ]);

            $logs[] = [
                'lead_id' => $leadId,
                'status' => $nextStatus,
                'email' => $to,
                'sent' => $emailSent
            ];
        }
    }

    return $logs;
}
