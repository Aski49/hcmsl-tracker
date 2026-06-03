<?php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql    = "SELECT * FROM expenditure WHERE 1=1";
    $params = [];

    if (!empty($_GET['year'])) { $sql .= " AND YEAR(date) = ?"; $params[] = $_GET['year']; }
    if (!empty($_GET['type'])) { $sql .= " AND type = ?";       $params[] = $_GET['type']; }

    $sql .= " ORDER BY date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
}

elseif ($method === 'POST') {
    $d    = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("
        INSERT INTO expenditure (type, descr, amount, date, category, remarks)
        VALUES (:type, :descr, :amount, :date, :category, :remarks)
    ");
    $stmt->execute([
        ':type'     => $d['type'],
        ':descr'    => $d['desc'],
        ':amount'   => $d['amount'],
        ':date'     => $d['date'],
        ':category' => $d['category'] ?? '',
        ':remarks'  => $d['remarks']  ?? '',
    ]);
    $d['id'] = $pdo->lastInsertId();
    echo json_encode($d);
}

elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare("DELETE FROM expenditure WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true]);
}