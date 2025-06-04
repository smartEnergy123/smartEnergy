<?php
if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
    header('Location: /smartEnergy/login');
    exit;
}


// require_once __DIR__ . '/../../../app/Scripts/GenerateDailySummary.php';


$username = $_SESSION['user_data']['username'] ?? 'Admin';

// Variables to hold the message and its type for display
$message = null;
$messageType = null;

if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageType = 'success';
    unset($_SESSION['success']); // Clear the session variable after reading
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageType = 'error';
    unset($_SESSION['error']); // Clear the session variable after reading
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SmartEnergy Admin Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar for better aesthetics, if needed for long content */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #e2e8f0;
            /* light gray */
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            /* slate-400 */
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
            /* slate-500 */
        }

        /* Styles for the message container */
        .message-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            /* Ensure it's above other content */
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: white;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        .message-container.success {
            background-color: #4CAF50;
            /* Green for success */
        }

        .message-container.error {
            background-color: #f44336;
            /* Red for error */
        }

        .message-container.hidden {
            opacity: 0;
            pointer-events: none;
            /* Make it unclickable when hidden */
        }
    </style>
</head>

<body class="flex h-screen bg-gray-100">

    <div id="sidebar" class="fixed inset-y-0 left-0 bg-gray-800 text-white w-64 px-4 space-y-6 py-7 transition duration-200 ease-in-out z-20 md:relative md:translate-x-0">
        <a href="/smartEnergy/admin/dashboard" class="text-white flex items-center space-x-2 px-4">
            <span class="text-2xl font-extrabold">SmartEnergy</span>
        </a>

        <nav>
            <a href="/smartEnergy/admin/dashboard/" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Dashboard</a>
            <a href="/smartEnergy/admin/reports" class="block py-2.5 px-4 rounded transition duration-200 bg-gray-700 text-white font-semibold">Reports</a>
            <a href="/smartEnergy/admin/manage-users" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Manage Users</a>
            <a href="/smartEnergy/admin/view-power-stats" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">View Power Stats</a>
            <a href="/smartEnergy/admin/simulateWeather" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Simulate Weather</a>
        </nav>

        <div class="absolute bottom-0 left-0 w-full p-4">
            <a href="/smartEnergy/logout" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 text-red-400">Logout</a>
        </div>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex justify-between items-center bg-white p-4 shadow">
            <button onclick="toggleSidebar()" class="text-gray-500 focus:outline-none md:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">Admin Reports</h1>

            <div class="relative">
                <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 focus:outline-none">
                    <span class="font-medium text-gray-700"><?php echo htmlspecialchars($username); ?></span>
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-10">
                    <a href="/smartEnergy/admin/profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                    <a href="/smartEnergy/logout" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div id="messageContainer" class="message-container <?php echo $messageType; ?>">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-md col-span-full">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Report Filters</h2>
                        <div class="flex flex-wrap gap-4 items-center">
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" id="startDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            </div>
                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" id="endDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            </div>
                            <div>
                                <button id="fetchReportBtn" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Generate Report</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-800">Overall Day Classification</h3>
                        <p class="text-gray-600">Good Days: <span id="goodDays" class="font-bold text-green-600">0</span></p>
                        <p class="text-gray-600">Average Days: <span id="averageDays" class="font-bold text-yellow-600">0</span></p>
                        <p class="text-gray-600">Bad Days: <span id="badDays" class="font-bold text-red-600">0</span></p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-800">Total Energy Overview (Wh)</h3>
                        <p class="text-gray-600">Solar Generated: <span id="totalSolar" class="font-bold">0</span></p>
                        <p class="text-gray-600">Wind Generated: <span id="totalWind" class="font-bold">0</span></p>
                        <p class="text-gray-600">Total Consumption: <span id="totalConsumption" class="font-bold">0</span></p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-800">Battery Performance</h3>
                        <p class="text-gray-600">Average End-of-Day Level: <span id="avgBatteryEnd" class="font-bold">0</span> Wh</p>
                        <p class="text-gray-600">Average Net Energy Balance: <span id="avgNetBalance" class="font-bold">0</span> Wh</p>
                        <p class="text-gray-600">Average Active Houses: <span id="avgHouses" class="font-bold">0</span></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Detailed Daily Reports</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solar (Wh)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wind (Wh)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consumption (Wh)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Balance (Wh)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Battery End (Wh)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classification</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Houses</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">Select a date range and click "Generate Report"</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Energy Production/Consumption Trend</h2>
                        <canvas id="energyTrendChart"></canvas>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Battery Level Trend</h2>
                        <canvas id="batteryTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }

        // User dropdown toggle
        function toggleUserDropdown() {
            document.getElementById("userDropdown").classList.toggle("hidden");
        }

        // Chart instances
        let energyChart;
        let batteryChart;

        document.addEventListener('DOMContentLoaded', () => {
            const fetchReportBtn = document.getElementById('fetchReportBtn');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const reportTableBody = document.getElementById('reportTableBody');
            const messageContainer = document.getElementById('messageContainer'); // Get the message container

            // Initialize date inputs with today's date for convenience
            const today = new Date().toISOString().split('T')[0];
            startDateInput.value = today;
            endDateInput.value = today;

            // Display and hide messages
            if (messageContainer) {
                // messageContainer is already visible because it's rendered by PHP
                setTimeout(() => {
                    messageContainer.classList.add('hidden');
                    setTimeout(() => messageContainer.remove(), 500); // 500ms for transition
                }, 10000); // Hide after 10 seconds
            }


            // Fetch report data on button click
            fetchReportBtn.addEventListener('click', fetchReportData);

            async function fetchReportData() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (!startDate || !endDate) {
                    alert('Please select both start and end dates.');
                    return;
                }

                // Fetch daily summaries
                try {
                    const response = await fetch(`/smartEnergy/api/admin/reports/daily-summary?startDate=${startDate}&endDate=${endDate}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        updateReportTable(result.data.summaries);
                        updateSummaryCards(result.data.summaries);
                        updateCharts(result.data.summaries);
                    } else {
                        console.error('Error fetching daily summaries:', result.message);
                        alert('Failed to fetch daily summaries: ' + result.message);
                        reportTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading data: ${result.message}</td></tr>`;
                    }
                } catch (error) {
                    console.error('Network or parsing error:', error);
                    alert('An error occurred while fetching report data.');
                    reportTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Network error or API not available.</td></tr>`;
                }
            }

            function updateReportTable(summaries) {
                reportTableBody.innerHTML = ''; // Clear existing rows
                if (summaries.length === 0) {
                    reportTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No data available for the selected date range.</td></tr>`;
                    return;
                }

                summaries.forEach(summary => {
                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${summary.report_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(summary.total_solar_generated_wh).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(summary.total_wind_generated_wh).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(summary.total_consumption_wh).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(summary.net_energy_balance_wh).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(summary.battery_level_end_wh).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ${getClassificationColor(summary.day_classification)}">${summary.day_classification}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseInt(summary.num_active_houses)}</td>
                        </tr>
                    `;
                    reportTableBody.innerHTML += row;
                });
            }

            function getClassificationColor(classification) {
                switch (classification) {
                    case 'Good':
                        return 'text-green-600 font-semibold';
                    case 'Average':
                        return 'text-yellow-600';
                    case 'Bad':
                        return 'text-red-600 font-semibold';
                    default:
                        return 'text-gray-500';
                }
            }

            function updateSummaryCards(summaries) {
                let goodDays = 0,
                    averageDays = 0,
                    badDays = 0;
                let totalSolar = 0,
                    totalWind = 0,
                    totalConsumption = 0;
                let totalBatteryEnd = 0,
                    totalNetBalance = 0,
                    totalHouses = 0;

                summaries.forEach(s => {
                    if (s.day_classification === 'Good') goodDays++;
                    else if (s.day_classification === 'Average') averageDays++;
                    else if (s.day_classification === 'Bad') badDays++;

                    totalSolar += parseFloat(s.total_solar_generated_wh);
                    totalWind += parseFloat(s.total_wind_generated_wh);
                    totalConsumption += parseFloat(s.total_consumption_wh);
                    totalBatteryEnd += parseFloat(s.battery_level_end_wh);
                    totalNetBalance += parseFloat(s.net_energy_balance_wh);
                    totalHouses += parseInt(s.num_active_houses);
                });

                document.getElementById('goodDays').textContent = goodDays;
                document.getElementById('averageDays').textContent = averageDays;
                document.getElementById('badDays').textContent = badDays;
                document.getElementById('totalSolar').textContent = totalSolar.toFixed(2);
                document.getElementById('totalWind').textContent = totalWind.toFixed(2);
                document.getElementById('totalConsumption').textContent = totalConsumption.toFixed(2);

                const count = summaries.length > 0 ? summaries.length : 1;
                document.getElementById('avgBatteryEnd').textContent = (totalBatteryEnd / count).toFixed(2);
                document.getElementById('avgNetBalance').textContent = (totalNetBalance / count).toFixed(2);
                document.getElementById('avgHouses').textContent = (totalHouses / count).toFixed(0); // Round to nearest whole number
            }

            function updateCharts(summaries) {
                const dates = summaries.map(s => s.report_date).reverse(); // Reverse for chronological order
                const solarData = summaries.map(s => parseFloat(s.total_solar_generated_wh)).reverse();
                const windData = summaries.map(s => parseFloat(s.total_wind_generated_wh)).reverse();
                const consumptionData = summaries.map(s => parseFloat(s.total_consumption_wh)).reverse();
                const batteryEndData = summaries.map(s => parseFloat(s.battery_level_end_wh)).reverse();

                // Destroy old charts if they exist
                if (energyChart) energyChart.destroy();
                if (batteryChart) batteryChart.destroy();

                // Energy Trend Chart
                const energyCtx = document.getElementById('energyTrendChart').getContext('2d');
                energyChart = new Chart(energyCtx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Solar Generated (Wh)',
                            data: solarData,
                            borderColor: 'rgb(255, 205, 86)',
                            backgroundColor: 'rgba(255, 205, 86, 0.2)',
                            tension: 0.1
                        }, {
                            label: 'Wind Generated (Wh)',
                            data: windData,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.1
                        }, {
                            label: 'Total Consumption (Wh)',
                            data: consumptionData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Energy (Wh)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        }
                    }
                });

                // Battery Level Trend Chart
                const batteryCtx = document.getElementById('batteryTrendChart').getContext('2d');
                batteryChart = new Chart(batteryCtx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Battery Level at EOD (Wh)',
                            data: batteryEndData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Battery Level (Wh)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        }
                    }
                });
            }

            // Initial fetch of data when the page loads ( for today's date)
            fetchReportData();
        });
    </script>
</body>

</html>
