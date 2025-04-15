<?php

// Headers : avant le corps de la requête
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

set_exception_handler(function (Throwable $exception) {
    http_response_code(500);

    echo json_encode([
        'error' => 'An unexpected error occured, please try again later'
    ]);
});

$dsn = "mysql:host=127.0.0.1;port=3640;dbname=training_db;charset=utf8mb4";
$pdo = new PDO($dsn, 'root', 'mysqltests');

// x /getUsers
// x /findAllUsers
// o GET /users

// x /insertUser
// x /newUser
// o POST /users { JSON body }

// o DELETE /users/4
// o GET /categories
// o GET /categories/2

// /resource[/id]
$uri = $_SERVER['REQUEST_URI'];
$httpMethod = $_SERVER['REQUEST_METHOD'];

// Analyser $uri pour extraire la ressource, et éventuellement l'ID
$uriParts = explode('/', ltrim($uri, '/'));
// explode avec le '/' en séparateur : "users/4" => ['users', '4']
// explode avec "s" : ['/u', 'er', '/4']

$resource = $uriParts[0];
$id = null;

if (count($uriParts) === 2) {
    $id = $uriParts[1];
}

const SUPPORTED_RESOURCES = ['users', 'categories'];

if (!in_array($resource, SUPPORTED_RESOURCES)) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown resource']);
    exit;
}

// Je sais que si je me trouve à cet endroit de l'algorithme,
// alors ça signifie que la ressource est connue et valide
// Si l'URI ne contient qu'une partie
// et que la méthode est GET
// Alors, je veux une liste de la ressource demandée
if ($httpMethod === "GET" && count($uriParts) === 1) {
    // Affichage de tous les éléments de la table
    $stmt = $pdo->query("SELECT * FROM $resource");
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // "uri" => "/resource/id"
    $results = [];

    foreach ($list as $element) {
        $element["uri"] = '/' . $resource . '/' . $element['id'];

        $results[] = $element;
    }

    // Au format JSON
    echo json_encode($results);
    exit;
}

// Afficher un élément seul
if ($httpMethod === "GET" && count($uriParts) === 2) {
    $stmt = $pdo->prepare("SELECT * FROM $resource WHERE id=?");
    $stmt->execute([$id]);

    $resourceItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resourceItem === false) {
        http_response_code(404);
        echo json_encode(["error" => "Not found"]);
        exit;
    }

    echo json_encode([
        "uri" => '/' . $resource . '/' . $resourceItem['id'],
        ...$resourceItem
    ]);
    exit;
}

// Créer un élément
// POST /categories
// POST /users
// ...
if ($httpMethod === "POST" && count($uriParts) === 1) {
    // Je récupère le contenu brut du corps de la requête
    // Donc du texte
    $rawData = file_get_contents("php://input");
    // Je demande à PHP de décoder ce texte (que j'attends au format JSON)
    // pour en faire un tableau associatif
    $data = json_decode($rawData, true);

    if ($resource === 'users') {
        $query = "INSERT INTO users (`username`, `email`) VALUES (:username, :email)";
    } elseif ($resource === 'categories') {
        $query = "INSERT INTO categories (`name`) VALUES (:name)";
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($data);
    } catch (PDOException $e) {
        http_response_code(400); // Bad request
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }

    http_response_code(201); // Created
    $newResourceItemId = $pdo->lastInsertId();
    echo json_encode([
        "uri" => '/' . $resource . '/' . $newResourceItemId,
        "id" => intval($newResourceItemId),
        ...$data
    ]);
}

// DELETE /users/2
// DELETE /categories/6
if ($httpMethod === 'DELETE' && count($uriParts) === 2) {
    $stmt = $pdo->prepare("DELETE FROM $resource WHERE id=:id");
    $stmt->execute(['id' => $id]);

    http_response_code(204); // No Content
}