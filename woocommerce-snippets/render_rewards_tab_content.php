/**
 * Render Rewards Tab Content (Recompensas)
 * Modular content renderer for WooCommerce My Account custom tab.
 * Shows reward chart + table or fallback message if no data found.
 */
function render_rewards_tab_content() {
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;

    $chart_data = [];
    $html = get_user_rewards_from_sheet($username, $chart_data);

    echo '<div class="rewards-card">';
    echo '<div class="rewards-header">';
    echo '<div class="rewards-title">Tus Recompensas</div>';

    if (!empty($chart_data['total'])) {
        echo '<div class="rewards-total">Total acumulado: ' . $chart_data['total'] . ' GEOD</div>';
    }

    echo '</div>'; // .rewards-header

    echo $html; // Either table or fallback message

    if (!empty($chart_data['labels'])) {
        echo '<div class="rewards-chart-wrapper"><canvas id="rewardsChart"></canvas></div>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById("rewardsChart").getContext("2d");
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, "rgba(78,115,223,0.3)");
            gradient.addColorStop(1, "rgba(78,115,223,0)");

            new Chart(ctx, {
                type: "line",
                data: {
                    labels: ' . json_encode($chart_data["labels"]) . ',
                    datasets: [{
                        label: "GEOD",
                        data: ' . json_encode($chart_data["values"]) . ',
                        fill: true,
                        backgroundColor: gradient,
                        borderColor: "#4e73df",
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: "#4e73df"
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: "#fff",
                            titleColor: "#111",
                            bodyColor: "#333",
                            borderColor: "#ccc",
                            borderWidth: 1,
                            titleFont: { weight: "bold" }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: Math.max(...' . json_encode($chart_data["values"]) . ') + 10,
                            title: { display: true, text: "GEOD", color: "#666" },
                            ticks: { stepSize: 10 }
                        },
                        x: {
                            title: { display: true, text: "Periodo de Pago", color: "#666" }
                        }
                    }
                }
            });
        });
        </script>';
    }

    echo '</div>'; // .rewards-card
}

/**
 * Fetch reward data from Google Sheet for a given username.
 */
function get_user_rewards_from_sheet($username, &$chart_data = []) {
    $sheet_url = 'https://docs.google.com/spreadsheets/d/13wRS_hYiczuU4WMCXX5lU6aLYcyZsoadjAI3tcg05eg/gviz/tq?tqx=out:json';
    $response = wp_remote_get($sheet_url);

    if (is_wp_error($response)) {
        return '<p style="color:red;">Error al conectar con la hoja de recompensas.</p>';
    }

    $body = wp_remote_retrieve_body($response);
    $json = trim(substr($body, strpos($body, '{'), -2));
    $data = json_decode($json, true);

    if (!$data || !isset($data['table']['rows'])) {
        return '<p style="color:red;">Datos de recompensa no válidos o vacíos.</p>';
    }

    $rows = $data['table']['rows'];
    $cols = $data['table']['cols'];

    foreach ($rows as $row) {
        $row_username = $row['c'][0]['v'] ?? '';
        if (strtolower($row_username) === strtolower($username)) {
            $total = $row['c'][1]['v'] ?? 0;
            $entries = [];

            for ($i = 2; $i < count($row['c']); $i++) {
                $label = $cols[$i]['label'] ?? "Pago $i";
                $value = $row['c'][$i]['v'] ?? null;
                if ($value !== null && $value !== 0) {
                    $entries[] = [
                        'label' => $label,
                        'value' => (int)$value
                    ];
                }
            }

            usort($entries, fn($a, $b) => strcmp($b['label'], $a['label']));
            $top5 = array_slice($entries, 0, 5);

            $chart_data['labels'] = array_column(array_reverse($top5), 'label');
            $chart_data['values'] = array_column(array_reverse($top5), 'value');
            $chart_data['total'] = $total;

            $html = "<div class='rewards-table-wrapper'><table class='rewards-table'><thead><tr><th>Periodo</th><th>Tokens</th></tr></thead><tbody>";
            foreach ($entries as $entry) {
                $html .= "<tr><td>{$entry['label']}</td><td>{$entry['value']}</td></tr>";
            }
            $html .= "</tbody></table></div>";

            return $html;
        }
    }

    return '<p>No se encontraron recompensas para tu usuario.</p>';
}

/**
 * Load Chart.js + inline styles only on My Account page.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_account_page()) {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_add_inline_style('woocommerce-inline', '
            .rewards-card {
                background: #ffffff;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
                max-width: 100%;
                margin-bottom: 40px;
            }
            .rewards-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 25px;
            }
            .rewards-title {
                font-size: 26px;
                font-weight: 700;
                color: #222;
            }
            .rewards-total {
                font-size: 18px;
                font-weight: 500;
                color: #4e73df;
            }
            .rewards-table-wrapper {
                max-height: 260px;
                overflow-y: auto;
                border: 1px solid #f0f0f0;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .rewards-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 15px;
            }
            .rewards-table th,
            .rewards-table td {
                padding: 12px 18px;
                border-bottom: 1px solid #eee;
                text-align: left;
            }
            .rewards-table th {
                background-color: #f9f9f9;
                position: sticky;
                top: 0;
                z-index: 2;
            }
            .rewards-chart-wrapper {
                text-align: center;
                padding-top: 20px;
                overflow-x: auto;
            }
            #rewardsChart {
                max-width: 100%;
                width: 600px;
                height: auto;
            }
        ');
    }
});
