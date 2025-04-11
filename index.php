<?php

require_once __DIR__ . '/users.php';

// Headers : avant le corps de la requête
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');

// J'affiche le résultat de l'encodage en JSON
// de mon tableau $users
// JSON est un format textuel donc le résultat
// de json_encode est une string
echo json_encode($users);
