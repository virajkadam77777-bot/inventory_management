<?php
// ==============================================
// Save Bill Handler
// File: save_bill.php
// ==============================================

header('Content-Type: application/json');

// Include your database connection (provides $db)
require_once 'db_connection.php'; // uses $db (PDO)

// ====== CONFIGURE THIS ======
// Change to the actual column name in your 'agencies' table
$agencyNameColumn = 'agency';   // <-- PUT YOUR COLUMN NAME HERE (e.g., 'name', 'agency', 'agency_name')
// ============================

// Get JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received or invalid JSON']);
    exit;
}

try {
    // 1. Find or create agency using the configured column
    $agencyName = trim($data['agency_name']);
    if (empty($agencyName)) {
        throw new Exception('Agency name is required');
    }

    // Use the dynamic column name in the WHERE clause
    $stmt = $db->prepare("SELECT id FROM agencies WHERE `$agencyNameColumn` = ?");
    $stmt->execute([$agencyName]);
    $agency = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agency) {
        // Generate a short code (max 5 alphanumeric chars)
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $agencyName), 0, 5));
        if (empty($code)) $code = 'CLIENT';
        // Insert new agency using the same column name
        $stmt = $db->prepare("INSERT INTO agencies (`$agencyNameColumn`, code) VALUES (?, ?)");
        $stmt->execute([$agencyName, $code]);
        $agencyId = $db->lastInsertId();
    } else {
        $agencyId = $agency['id'];
    }

    // 2. Insert into bills table
    $billNumber = trim($data['bill_number']);
    $billDate   = trim($data['bill_date']);
    $grandTotal = floatval($data['grand_total'] ?? 0);
    $status     = trim($data['status'] ?? 'Unpaid');

    if (empty($billNumber) || empty($billDate)) {
        throw new Exception('Bill number and date are required');
    }

    $stmt = $db->prepare("
        INSERT INTO bills 
        (agency_id, bill_type, bill_number, bill_date, amount, status, pdf_path) 
        VALUES (?, 'invoice', ?, ?, ?, ?, NULL)
    ");
    $stmt->execute([
        $agencyId,
        $billNumber,
        $billDate,
        $grandTotal,
        $status
    ]);

    $billId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'bill_id' => $billId,
        'message' => 'Bill saved successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>