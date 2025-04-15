<?php

$client = curl_init("https://jsonplaceholder.typicode.com/todos");

// Pour mettre le résultat de la requête en valeur de retour et non l'afficher à l'écran par défaut
curl_setopt($client, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($client);

echo $response;

// var_dump(json_decode($response, true));

curl_close($client);