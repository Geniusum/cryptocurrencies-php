<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Candlestick</title>
</head>
<body>
    <canvas id="cryptoChart" style="width: 100%; height: 400px;"></canvas>
    <!-- Chargement de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chargement de Chartjs-financial -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>
    <!-- Chargement de l'adaptateur de date -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script>
        // Enregistrement des contrôleurs et éléments nécessaires à Chart.js
        const { CandlestickController, FinancialElement, FinancialScale } = Chart;

        Chart.register(CandlestickController, FinancialElement, FinancialScale);

        // Données pour le graphique
        const chartData = [
            { t: '2025-01-01T10:00:00Z', o: 50, h: 70, l: 40, c: 60 },
            { t: '2025-01-01T11:00:00Z', o: 60, h: 80, l: 50, c: 70 },
            { t: '2025-01-01T12:00:00Z', o: 70, h: 90, l: 60, c: 85 }
        ];

        // Créer le graphique
        const ctx = document.getElementById('cryptoChart').getContext('2d');
        new Chart(ctx, {
            type: 'candlestick',
            data: {
                datasets: [{
                    label: 'Chandeliers',
                    data: chartData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'hour'
                        },
                        title: {
                            display: true,
                            text: 'Temps'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Prix (USD)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
