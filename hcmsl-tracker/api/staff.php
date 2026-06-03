<?php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query("SELECT * FROM staff ORDER BY name")->fetchAll();
    echo json_encode($rows);
}

elseif ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("
        INSERT INTO staff (name, title, dept, email, phone, monthlyContribution)
        VALUES (:name, :title, :dept, :email, :phone, :monthlyContribution)
    ");
    $stmt->execute([
        ':name'                => $d['name'],
        ':title'               => $d['title'],
        ':dept'                => $d['dept']  ?? '',
        ':email'               => $d['email'] ?? '',
        ':phone'               => $d['phone'] ?? '',
        ':monthlyContribution' => $d['monthlyContribution'] ?? 0,
    ]);
    $d['id'] = $pdo->lastInsertId();
    echo json_encode($d);
}

elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$id]);
    echo json_encode(['deleted' => true]);
}