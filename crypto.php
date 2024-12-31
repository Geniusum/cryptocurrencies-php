<?php

$selected_crypto = ["bitcoin", "bitcoin-cash", "ethereum", "cardano", "stellar", "litecoin", "eos"];  // 8 cryptomonnaies choisis, chacun d'eux a sa table dans la base de donnée distribué

// Connection base de donnée MYSQL
$host = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "cryptocurrencies";

function get_api_json() { // Fonction pour récupérer la réponse JSON de CoinCap API
    global $selected_crypto;
    return file_get_contents("https://api.coincap.io/v2/assets?ids=" . implode(",", $selected_crypto));
}

function get_sample_api_json() { // Pour éviter de faire des requêtes à l'API lors des tests
    return file_get_contents("sample.json");
}

function json_to_array(string $json) {
    return json_decode($json, true);
}

function array_to_json(array $array) {
    return json_encode($array);
}

function get_pdo() {
    global $host, $username, $password, $dbname;
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    return $pdo;
}

function put_data_to_tables(array $apiResponse, PDO $pdo): void {
    if (!isset($apiResponse['data']) || !isset($apiResponse['timestamp'])) {
        throw new Exception("Invalid API response format.");
    }

    $timestamp = floor($apiResponse['timestamp'] / 1000);
    foreach ($apiResponse['data'] as $crypto) {
        $tableName = $crypto['id'];
        $columns = array_keys($crypto);
        $columns = array_slice($columns, 1, count($columns));
        $values = array_values($crypto);
        $values = array_slice($values, 1, count($values));
        if ($values[4] == NULL) {
            $values[4] = 0;
        }

        // Ajout du timestamp à la liste des colonnes et des valeurs
        $columns[] = 'timestamp';
        $values[] = $timestamp;

        // Préparation des noms des colonnes et des placeholders
        $columnNames = implode(',', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));

        // Création de la requête SQL
        $sql = "INSERT INTO `$tableName` ($columnNames) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        // Exécution de la requête
        try {
            $stmt->execute($values);
        } catch (PDOException $e) {
            echo "Failed to insert data for table `$tableName`: " . $e->getMessage() . "\n";
        }
    }
}

function get_data_from_tables(PDO $pdo): array {
    global $selected_crypto;
    $result = ['data' => [], 'timestamp' => null];
    $tableNames = $selected_crypto;

    try {
        // Étape 1 : Trouver le dernier timestamp commun
        $timestampQuery = [];
        foreach ($tableNames as $tableName) {
            $timestampQuery[] = "(SELECT MAX(timestamp) AS timestamp FROM `$tableName`)";
        }

        $combinedQuery = implode(" UNION ALL ", $timestampQuery);
        $latestTimestampQuery = "SELECT MIN(timestamp) AS common_timestamp FROM ($combinedQuery) AS timestamps";
        $stmt = $pdo->query($latestTimestampQuery);
        $latestTimestamp = $stmt->fetchColumn();

        if (!$latestTimestamp) {
            throw new Exception("Aucun timestamp commun trouvé.");
        }

        $result['timestamp'] = $latestTimestamp;

        // Étape 2 : Récupérer les données pour le dernier timestamp
        foreach ($tableNames as $tableName) {
            $sql = "SELECT * FROM `$tableName` WHERE `timestamp` = :timestamp";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['timestamp' => $latestTimestamp]);
            $cryptoData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cryptoData) {
                unset($cryptoData['timestamp']); // Retirer la colonne `timestamp`
                $cryptoData["id"] = $tableName;
                $result['data'][] = $cryptoData;
            }
        }
    } catch (PDOException $e) {
        echo "Erreur SQL : " . $e->getMessage();
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }

    return $result;
}

function get_all_column_data(string $crypto_id, string $column): array {
    $pdo = get_pdo(); // Obtenir la connexion PDO
    $result = []; // Initialiser le tableau de résultats

    try {
        // Vérifier que la colonne demandée existe dans la table
        $query = "SHOW COLUMNS FROM `$crypto_id` LIKE :column";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['column' => $column]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("La colonne `$column` n'existe pas dans la table `$crypto_id`.");
        }

        // Récupérer toutes les données de la colonne et du timestamp
        $query = "SELECT `$column`, `timestamp` FROM `$crypto_id`";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                "column" => $row[$column],
                "timestamp" => $row['timestamp'],
            ];
        }
    } catch (PDOException $e) {
        echo "Erreur SQL : " . $e->getMessage();
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }

    return $result;
}

/* function generate_graph_for_column(array $all_column_data): void {
    if (empty($all_column_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Extraction des données pour l'axe des X (timestamp) et l'axe des Y (column)
    $timestamps = array_map(fn($data) => $data['timestamp'], $all_column_data);
    $values = array_map(fn($data) => $data['column'], $all_column_data);

    // Conversion des timestamps en format lisible (facultatif)
    $formattedTimestamps = array_map(
        fn($timestamp) => date('Y-m-d H:i:s', $timestamp),
        $timestamps
    );

    // Création du graphique
    $graph = new Graph(800, 600); // Dimensions du graphique
    $graph->SetScale('textlin'); // Type de graphique (texte en X, linéaire en Y)

    // Ajouter un titre et des labels
    $graph->title->Set('Évolution des valeurs');
    $graph->xaxis->title->Set('Temps');
    $graph->yaxis->title->Set('Valeur');
    $graph->xaxis->SetTickLabels($formattedTimestamps); // Afficher les timestamps formatés sur l'axe X
    $graph->xaxis->SetLabelAngle(45); // Incliner les labels pour meilleure lisibilité

    // Création de la courbe
    $lineplot = new LinePlot($values);
    $lineplot->SetColor("blue"); // Couleur de la courbe
    $lineplot->SetLegend('Valeur'); // Légende de la courbe

    // Ajouter la courbe au graphique
    $graph->Add($lineplot);

    // Affichage de la légende
    $graph->legend->SetFrameWeight(1);

    // Afficher le graphique
    $graph->Stroke();
} */

function generate_graph_for_column(string $chart_name,array $all_column_data, string $outputFile = "chart.html"): void {
    if (empty($all_column_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Extraction des données pour l'axe des X (timestamps) et Y (column)
    $timestamps = array_map(fn($data) => date('Y-m-d H:i:s', $data['timestamp']), $all_column_data);
    $values = array_map(fn($data) => $data['column'], $all_column_data);

    // Encodage des données en JSON
    $timestampsJson = json_encode($timestamps);
    $valuesJson = json_encode($values);

    // Génération du contenu HTML avec les données intégrées en JSON
    $html = <<<HTML
    <div style="width: 80%; margin: auto;">
        <canvas id="cryptoChart_{$chart_name}"></canvas>
    </div>
    <script>
        // Données PHP transformées en JSON pour Chart.js
        var timestamps = $timestampsJson; // Axe X : Timestamps
        var values = $valuesJson;         // Axe Y : Valeurs

        // Configuration du graphique
        var ctx = document.getElementById('cryptoChart_{$chart_name}').getContext('2d');
        new Chart(ctx, {
            type: 'line', // Type de graphique
            data: {
                labels: timestamps, // Axe X : Timestamps
                datasets: [{
                    label: 'Valeurs',
                    data: values, // Axe Y : Valeurs
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    tension: 0.4 // Lissage de la courbe
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Temps',
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Valeur',
                        }
                    }
                }
            }
        });
    </script>
HTML;

    // Sauvegarde du fichier HTML
    echo $html;
}
