<?php
// ============================================================
//  staff.php — CRUD for staff table
// ============================================================
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: return all staff ────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY name ASC");
    $rows = $stmt->fetchAll();

    // Cast numeric fields so JS gets numbers, not strings
    foreach ($rows as &$r) {
        $r['id']                  = (int)   $r['id'];
        $r['monthlyContribution'] = (float) $r['monthlyContribution'];
    }
    echo json_encode($rows);
}

// ── POST: insert new staff member ───────────────────────────
elseif ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);

    if (empty($d['name']) || empty($d['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and title are required.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO staff (name, title, dept, email, phone, monthlyContribution)
        VALUES (:name, :title, :dept, :email, :phone, :monthlyContribution)
    ");
    $stmt->execute([
        ':name'                => trim($d['name']),
        ':title'               => trim($d['title']),
        ':dept'                => trim($d['dept']  ?? ''),
        ':email'               => trim($d['email'] ?? ''),
        ':phone'               => trim($d['phone'] ?? ''),
        ':monthlyContribution' => (float)($d['monthlyContribution'] ?? 0),
    ]);

    $id = (int) $pdo->lastInsertId();

    // Return the full inserted record
    $row = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $row->execute([$id]);
    $record = $row->fetch();
    $record['id']                  = (int)   $record['id'];
    $record['monthlyContribution'] = (float) $record['monthlyContribution'];

    http_response_code(201);
    echo json_encode($record);
}

// ── DELETE: remove a staff member ───────────────────────────
elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id.']);
        exit;
    }
    $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true, 'id' => $id]);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}