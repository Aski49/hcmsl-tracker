<?php
// ============================================================
//  expenditure.php — CRUD for expenditure table
//  Column name is `description` (not `descr`)
// ============================================================
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list expenditure with optional filters ──────────────
if ($method === 'GET') {
    $sql    = "SELECT * FROM expenditure WHERE 1=1";
    $params = [];

    if (!empty($_GET['year'])) {
        $sql     .= " AND YEAR(date) = ?";
        $params[] = (int)$_GET['year'];
    }
    if (!empty($_GET['type'])) {
        $sql     .= " AND type = ?";
        $params[] = $_GET['type'];
    }

    $sql .= " ORDER BY date ASC, createdAt ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['id']     = (int)   $r['id'];
        $r['amount'] = (float) $r['amount'];
        // Rename `description` → `desc` so JS stays unchanged
        $r['desc']   = $r['description'];
        unset($r['description']);
    }

    echo json_encode($rows);
}

// ── POST: add a new transaction ─────────────────────────────
elseif ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);

    if (empty($d['type']) || empty($d['desc']) || !isset($d['amount']) || empty($d['date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'type, desc, amount and date are required.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO expenditure (type, description, amount, date, category, remarks)
        VALUES (:type, :description, :amount, :date, :category, :remarks)
    ");
    $stmt->execute([
        ':type'        => $d['type'],
        ':description' => trim($d['desc']),        // JS sends `desc`, stored as `description` ✓
        ':amount'      => (float)$d['amount'],
        ':date'        => $d['date'],
        ':category'    => trim($d['category'] ?? ''),
        ':remarks'     => trim($d['remarks']  ?? ''),
    ]);

    $id = (int)$pdo->lastInsertId();

    // Return full record with `desc` key for JS
    $row = $pdo->prepare("SELECT * FROM expenditure WHERE id = ?");
    $row->execute([$id]);
    $record         = $row->fetch();
    $record['id']   = (int)   $record['id'];
    $record['amount'] = (float) $record['amount'];
    $record['desc'] = $record['description'];
    unset($record['description']);

    http_response_code(201);
    echo json_encode($record);
}

// ── DELETE: remove a transaction ────────────────────────────
elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id.']);
        exit;
    }
    $pdo->prepare("DELETE FROM expenditure WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true, 'id' => $id]);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}