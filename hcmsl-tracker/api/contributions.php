<?php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql    = "SELECT * FROM contributions WHERE 1=1";
    $params = [];

    if (!empty($_GET['staffId'])) { $sql .= " AND staffId = ?";    $params[] = $_GET['staffId']; }
    if (!empty($_GET['month']))   { $sql .= " AND month = ?";      $params[] = $_GET['month'];   }
    if (!empty($_GET['year']))    { $sql .= " AND year = ?";       $params[] = $_GET['year'];    }

    $sql .= " ORDER BY year DESC, FIELD(month,
        'January','February','March','April','May','June',
        'July','August','September','October','November','December') DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
}

elseif ($method === 'POST') {
    $d    = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("
        INSERT INTO contributions (staffId, staffName, staffTitle, month, year, amount, date, notes)
        VALUES (:staffId, :staffName, :staffTitle, :month, :year, :amount, :date, :notes)
    ");
    $stmt->execute([
        ':staffId'    => $d['staffId'],
        ':staffName'  => $d['staffName'],
        ':staffTitle' => $d['staffTitle'] ?? '',
        ':month'      => $d['month'],
        ':year'       => $d['year'],
        ':amount'     => $d['amount'],
        ':date'       => $d['date']  ?: null,
        ':notes'      => $d['notes'] ?? '',
    ]);
    $d['id'] = $pdo->lastInsertId();
    echo json_encode($d);
}

elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare("DELETE FROM contributions WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true]);
}