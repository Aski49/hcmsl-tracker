<?php
// ============================================================
//  contributions.php — CRUD for contributions table
//  staffName + staffTitle are stored at insert time,
//  so they always display correctly (no JOIN required).
// ============================================================
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list contributions with optional filters ────────────
if ($method === 'GET') {
    $sql    = "SELECT * FROM contributions WHERE 1=1";
    $params = [];

    if (!empty($_GET['staffId'])) {
        $sql     .= " AND staffId = ?";
        $params[] = (int)$_GET['staffId'];
    }
    if (!empty($_GET['month'])) {
        $sql     .= " AND month = ?";
        $params[] = $_GET['month'];
    }
    if (!empty($_GET['year'])) {
        $sql     .= " AND year = ?";
        $params[] = (int)$_GET['year'];
    }

    $sql .= " ORDER BY year DESC,
              FIELD(month,
                'January','February','March','April','May','June',
                'July','August','September','October','November','December'
              ) DESC,
              createdAt DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['id']      = (int)   $r['id'];
        $r['staffId'] = (int)   $r['staffId'];
        $r['year']    = (int)   $r['year'];
        $r['amount']  = (float) $r['amount'];
    }

    echo json_encode($rows);
}

// ── POST: record a new contribution ─────────────────────────
elseif ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($d['staffId']) || empty($d['month']) || empty($d['year']) || !isset($d['amount'])) {
        http_response_code(400);
        echo json_encode(['error' => 'staffId, month, year and amount are required.']);
        exit;
    }

    // Fetch staff name + title from DB so they are always accurate
    $staffStmt = $pdo->prepare("SELECT name, title FROM staff WHERE id = ?");
    $staffStmt->execute([(int)$d['staffId']]);
    $staff = $staffStmt->fetch();

    if (!$staff) {
        http_response_code(404);
        echo json_encode(['error' => 'Staff member not found.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO contributions (staffId, staffName, staffTitle, month, year, amount, date, notes)
        VALUES (:staffId, :staffName, :staffTitle, :month, :year, :amount, :date, :notes)
    ");
    $stmt->execute([
        ':staffId'    => (int)$d['staffId'],
        ':staffName'  => $staff['name'],          // pulled from staff table ✓
        ':staffTitle' => $staff['title'],          // pulled from staff table ✓
        ':month'      => $d['month'],
        ':year'       => (int)$d['year'],
        ':amount'     => (float)$d['amount'],
        ':date'       => !empty($d['date']) ? $d['date'] : null,
        ':notes'      => trim($d['notes'] ?? ''),
    ]);

    $id = (int)$pdo->lastInsertId();

    // Return full inserted record
    $row = $pdo->prepare("SELECT * FROM contributions WHERE id = ?");
    $row->execute([$id]);
    $record = $row->fetch();
    $record['id']      = (int)   $record['id'];
    $record['staffId'] = (int)   $record['staffId'];
    $record['year']    = (int)   $record['year'];
    $record['amount']  = (float) $record['amount'];

    http_response_code(201);
    echo json_encode($record);
}

// ── DELETE: remove a contribution ───────────────────────────
elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id.']);
        exit;
    }
    $pdo->prepare("DELETE FROM contributions WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true, 'id' => $id]);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}