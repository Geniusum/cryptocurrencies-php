<?php
require_once '/var/www/html/jpgraph/src/jpgraph.php';
require_once '/var/www/html/jpgraph/src/jpgraph_line.php';

$selected_crypto = ["bitcoin", "bitcoin-cash", "ethereum", "cardano", "stellar", "litecoin", "eos"];  // 8 cryptomonnaies choisis, chacun d'eux a sa table dans la base de donnée distribué

// Connection base de donn

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

function get_all_column_data_limit(string $crypto_id, string $column, int $start_timestamp = null, int $end_timestamp = null): array {
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

        // Construire la condition de la plage de timestamps si les valeurs sont fournies
        $conditions = [];
        $params = [];

        if ($start_timestamp == $end_timestamp) {
            $end_timestamp += 86400;
        }

        // Ajouter la condition pour le timestamp de début
        if ($start_timestamp !== null) {
            $conditions[] = "`timestamp` >= :start_timestamp";
            $params['start_timestamp'] = $start_timestamp;
        }

        // Ajouter la condition pour le timestamp de fin
        if ($end_timestamp !== null) {
            $conditions[] = "`timestamp` <= :end_timestamp";
            $params['end_timestamp'] = $end_timestamp;
        }

        // Construire la requête avec ou sans conditions sur le timestamp
        $query = "SELECT `$column`, `timestamp` FROM `$crypto_id`";
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Récupérer les données
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                "column" => $column,
                "value" => $row[$column],
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

function get_all_column_data_limit_2(string $crypto_id, string $column, int $start_timestamp = null, int $end_timestamp = null): array {
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

        // Construire la condition de la plage de timestamps si les valeurs sont fournies
        $conditions = [];
        $params = [];

        if ($start_timestamp == $end_timestamp) {
            $end_timestamp += 86400;
        }

        // Ajouter la condition pour le timestamp de début
        if ($start_timestamp !== null) {
            $conditions[] = "`timestamp` >= :start_timestamp";
            $params['start_timestamp'] = $start_timestamp;
        }

        // Ajouter la condition pour le timestamp de fin
        if ($end_timestamp !== null) {
            $conditions[] = "`timestamp` <= :end_timestamp";
            $params['end_timestamp'] = $end_timestamp;
        }

        // Construire la requête avec ou sans conditions sur le timestamp
        $query = "SELECT `$column`, `timestamp` FROM `$crypto_id`";
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Récupérer les données
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                "crypto" => $crypto_id,
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

function get_all_column_data_2(string $crypto_id, string $column): array {
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
                "crypto" => $crypto_id,
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

function generate_graph_for_column(array $all_column_data, string $height, string $type = "line", ?string $algorithm = null): void {
    if (empty($all_column_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Extraction des données pour l'axe des X (timestamps) et Y (column)
    $timestamps = array_map(fn($data) => date('Y-m-d H:i:s', $data['timestamp']), $all_column_data);
    $values = array_map(fn($data) => $data['value'], $all_column_data);

    // Génération des prévisions si un algorithme est fourni
    $predictions = [];
    if ($algorithm !== null) {
        $timestampsCount = count($timestamps);
        $interval = strtotime($timestamps[1]) - strtotime($timestamps[0]);

        switch ($algorithm) {
            case 'moving_average':
                $windowSize = 3;
                for ($i = $timestampsCount; $i < $timestampsCount * 2; $i++) {
                    $predictions[] = array_sum(array_slice($values, max(0, $i - $windowSize), $windowSize)) / $windowSize;
                }
                break;

            case 'linear_regression':
                $slope = ($values[count($values) - 1] - $values[0]) / ($timestampsCount - 1);
                $intercept = $values[0];
                for ($i = $timestampsCount; $i < $timestampsCount * 2; $i++) {
                    $predictions[] = $slope * ($i - $timestampsCount) + $intercept;
                }
                break;

            default:
                echo "Algorithme non pris en charge.";
                return;
        }

        // Étendre les timestamps pour correspondre aux prévisions
        $lastTimestamp = strtotime(end($timestamps));
        for ($i = 0; $i < count($predictions); $i++) {
            $timestamps[] = date('Y-m-d H:i:s', $lastTimestamp + ($i + 1) * $interval);
        }

        $values = array_merge($values, $predictions);
    }

    // Encodage des données en JSON
    $timestampsJson = json_encode($timestamps);
    $valuesJson = json_encode($values);

    // Génération du contenu HTML avec les données intégrées en JSON
    $html = <<<HTML
    <div style="width: 100%; height: {$height}; margin: auto;">
        <canvas id="cryptoChart"></canvas>
    </div>
    <script>
        // Données PHP transformées en JSON pour Chart.js
        var timestamps = $timestampsJson; // Axe X : Timestamps
        var values = $valuesJson;         // Axe Y : Valeurs

        // Configuration du graphique
        var ctx = document.getElementById('cryptoChart').getContext('2d');

        const totalDuration = 2000;
        const delayBetweenPoints = totalDuration / values.length;
        const previousY = (ctx) => ctx.index === 0 ? ctx.chart.scales.y.getPixelForValue(100) : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;
        const animation = {
            x: {
                type: 'number',
                easing: 'linear',
                duration: delayBetweenPoints,
                from: NaN, // the point is initially skipped
                delay(ctx) {
                if (ctx.type !== 'data' || ctx.xStarted) {
                    return 0;
                }
                ctx.xStarted = true;
                return ctx.index * delayBetweenPoints;
                }
            },
            y: {
                type: 'number',
                easing: 'linear',
                duration: delayBetweenPoints,
                from: previousY,
                delay(ctx) {
                if (ctx.type !== 'data' || ctx.yStarted) {
                    return 0;
                }
                ctx.yStarted = true;
                return ctx.index * delayBetweenPoints;
                }
            }
        };

        var cryptoChart = new Chart(ctx, {
            type: '{$type}', // Type de graphique
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
                animation,
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

    echo $html;
}

function generate_graph_for_multiple_columns(array $all_column_data, string $height, string $type = "line"): void {
    if (empty($all_column_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Organiser les données par colonne
    $organizedData = [];
    foreach ($all_column_data as $data) {
        $column = $data['column'];
        $organizedData[$column]['timestamps'][] = date('Y-m-d H:i:s', $data['timestamp']);
        $organizedData[$column]['values'][] = $data['value'];
    }

    // Générer une liste de timestamps communs pour l'axe X
    $commonTimestamps = [];
    foreach ($organizedData as $columnData) {
        $commonTimestamps = array_merge($commonTimestamps, $columnData['timestamps']);
    }
    $commonTimestamps = array_values(array_unique($commonTimestamps));
    sort($commonTimestamps);

    // Générer des couleurs aléatoires pour chaque colonne
    $colors = [];
    foreach (array_keys($organizedData) as $column) {
        $colors[$column] = [
            'borderColor' => sprintf('rgba(%d, %d, %d, 1)', rand(0, 255), rand(0, 255), rand(0, 255)),
            'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.2)', rand(0, 255), rand(0, 255), rand(0, 255)),
        ];
    }

    // Préparer les datasets pour Chart.js
    $datasets = [];
    foreach ($organizedData as $column => $data) {
        $timestamps = $data['timestamps'];
        $values = $data['values'];

        // Aligner les valeurs sur les timestamps communs
        $valuesByTimestamp = array_combine($timestamps, $values);
        $alignedValues = [];
        foreach ($commonTimestamps as $timestamp) {
            $alignedValues[] = $valuesByTimestamp[$timestamp] ?? null; // Valeur null si le timestamp est manquant
        }

        $datasets[] = [
            'label' => $column,
            'data' => $alignedValues,
            'borderColor' => $colors[$column]['borderColor'],
            'backgroundColor' => $colors[$column]['backgroundColor'],
            'borderWidth' => 2,
            'tension' => 0.4,
            'spanGaps' => true, // Autoriser les trous dans les données
        ];
    }

    // Encoder les données pour Chart.js
    $timestampsJson = json_encode($commonTimestamps);
    $datasetsJson = json_encode($datasets);

    // Contenu HTML
    $html = <<<HTML
    <div style="width: 100%; height: {$height}; margin: auto;">
        <canvas id="cryptoChart"></canvas>
    </div>
    <script>
        const timestamps = $timestampsJson;
        const datasets = $datasetsJson;

        const ctx = document.getElementById('cryptoChart').getContext('2d');

        var cryptoChart = new Chart(ctx, {
            type: '{$type}',
            data: {
                labels: timestamps,
                datasets: datasets
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

    echo $html;
}

function generate_price_line_chart(string $chart_name, array $all_column_data, string $height): void {
    if (empty($all_column_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Extraction des données pour l'axe des X (timestamps) et Y (priceUsd)
    $timestamps = array_map(fn($data) => date('Y-m-d H:i:s', $data['timestamp']), $all_column_data);
    $prices = array_map(fn($data) => $data['priceUsd'], $all_column_data);

    // Encodage des données en JSON
    $timestampsJson = json_encode($timestamps);
    $pricesJson = json_encode($prices);

    // Génération du contenu HTML avec les données intégrées en JSON
    $html = <<<HTML
    <div style="width: 100%; height: {$height}; margin: auto;">
        <canvas id="priceChart_{$chart_name}"></canvas>
    </div>
    <script>
        // Données PHP transformées en JSON pour Chart.js
        var timestamps = $timestampsJson; // Axe X : Timestamps
        var prices = $pricesJson;         // Axe Y : Prix

        // Configuration du graphique
        var ctx = document.getElementById('priceChart_{$chart_name}').getContext('2d');
        new Chart(ctx, {
            type: 'line', // Type de graphique
            data: {
                labels: timestamps, // Axe X : Timestamps
                datasets: [{
                    label: 'Prix en USD',
                    data: prices, // Axe Y : Prix
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
                            text: 'Prix en USD',
                        }
                    }
                }
            }
        });
    </script>
HTML;

    echo $html;
}

function old_generate_candlestick_chart(string $chart_name, array $candlestick_data, string $height): void {
    if (empty($candlestick_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Encodage des données en JSON
    $dataJson = json_encode($candlestick_data);

    // Génération du contenu HTML avec les données intégrées en JSON
    $html = <<<HTML
    <div style="width: 100%; height: {$height}; margin: auto;">
        <canvas id="candlestickChart_{$chart_name}"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial@3.6.0/dist/chartjs-chart-financial.min.js"></script>
    <script>
        var candlestickData = $dataJson; // Données en chandeliers

        var ctx = document.getElementById('candlestickChart_{$chart_name}').getContext('2d');
        new Chart(ctx, {
            type: 'candlestick', // Type de graphique en chandeliers
            data: {
                datasets: [{
                    label: 'Chandeliers',
                    data: candlestickData, // Données des chandeliers
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                            text: 'Prix en USD',
                        }
                    }
                }
            }
        });
    </script>
HTML;

    echo $html;
}

function generate_candlestick_chart(string $chart_name, array $candlestick_data, string $height): void {
    if (empty($candlestick_data)) {
        echo "Aucune donnée disponible pour générer le graphique.";
        return;
    }

    // Encodage des données en JSON
    $candlestick_json = json_encode($candlestick_data);

    // Génération du contenu HTML avec les données intégrées en JSON
    $html = <<<HTML
    <div style="width: 100%; height: {$height}; margin: auto;">
        <canvas id="cryptoChart_{$chart_name}"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>
    <script>
        Chart.register(
            Chart.controllers.candlestick, // Contrôleur pour les chandeliers
            Chart.FinancialController,     // Contrôleur financier
            Chart.FinancialElement,        // Élément pour les chandeliers
            Chart.FinancialScale           // Échelle pour les données financières
        );

        var ctx = document.getElementById('cryptoChart_{$chart_name}').getContext('2d');
        var chartData = $candlestick_json;

        new Chart(ctx, {
            type: 'candlestick',
            data: {
                datasets: [{
                    label: 'Graphique en chandeliers',
                    data: chartData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time', // Type temporel
                        time: {
                            unit: 'minute', // Unité pour l'affichage
                        },
                        title: {
                            display: true,
                            text: 'Temps'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Valeurs'
                        }
                    }
                }
            }

        });
    </script>
HTML;

    echo $html;
}

function get_display_name(string $column) {
    switch ($column) {
        case "priceUsd":
            $columnDisplayName = "Price in USD";
            break;
        case "supply":
            $columnDisplayName = "Supply";
            break;
        case "volumeUsd24Hr":
            $columnDisplayName = "Volume in USD during last 24 hours";
            break;
        case "changePercent24Hr":
            $columnDisplayName = "Change in percent during last 24 hours";
            break;
        case "vwap24Hr":
            $columnDisplayName = "Average price during last 24 hours";
            break;
        case "marketCapUsd":
            $columnDisplayName = "Market capitalization in USD";
            break;
        case "rank":
            $columnDisplayName = "Rank in market capitalization";
            break;
        case "id":
            $columnDisplayName = "Identifier";
            break;
        case "symbol":
            $columnDisplayName = "Symbol";
            break;
        case "explorer":
            $columnDisplayName = "Source";
            break;
        default:
            $columnDisplayName = $column;
            break;
    }
    return $columnDisplayName;
}
