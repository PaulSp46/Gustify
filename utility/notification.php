<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autenticato']);
    exit;
}
include "../utility/database.php";

$maxDays = 3; // avvisa per scadenze entro X giorni
$sql = "SELECT f.idrelation AS id, p.des AS name,
               f.scadenza AS expiry
        FROM frigo f
        JOIN prodotto p ON f.prodotto_idprodotto = p.idprodotto
        WHERE f.utente_idutente = :uid
          AND f.scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :maxDays DAY)
        ORDER BY f.scadenza ASC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':uid', $_SESSION["user_id"]);
$stmt->bindParam(':maxDays', $maxDays, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $r) {
    $expiry = new DateTime($r['expiry']);
    $today  = new DateTime();
    $diff   = $today->diff($expiry)->days;
    $result[] = [
        'id'      => $r['id'],
        'name'    => $r['name'],
        'daysLeft'=> $diff
    ];
}

echo json_encode($result);
