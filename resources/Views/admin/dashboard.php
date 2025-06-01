<?php

use Dotenv\Dotenv;

if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['user_type'] !== 'admin') {
    header('Location: /smartEnergy/login');
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

// Load Dotenv only if environment variables are not already loaded
if (!getenv('WEATHER_API_KEY')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
    $dotenv->load();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart Energy Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .rotate-slow {
            animation: spin 4s linear infinite;
            transform-origin: 75px 60px;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .shine {
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.5);
            }

            100% {
                filter: brightness(1);
            }
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="flex h-screen bg-gray-100">
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 bg-gray-800 shadow-lg p-4 w-64 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-200 ease-in-out z-50">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">SmartEnergy</h1>
            <button id="sidebar-toggle-btn" class="md:hidden text-white focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="space-y-4">
            <a href="/smartEnergy/admin/dashboard/" class="block hover:bg-gray-700 p-2 rounded text-white">
                <i class="fas fa-tachometer-alt mr-3"></i>
                Dashboard
            </a>
            <a href="/smartEnergy/admin/manage-users" class="block hover:bg-gray-700 p-2 rounded text-white">
                <i class="fas fa-users-cog mr-3"></i>
                Manage Users
            </a>
            <a href="/smartEnergy/admin/view-power-stats" class="block hover:bg-gray-700 p-2 rounded text-white">
                <i class="fas fa-chart-line mr-3"></i>
                View Power Stats
            </a>
            <a href="/smartEnergy/admin/reports" class="block hover:bg-gray-700 p-2 rounded text-white">
                <i class="fas fa-file-alt mr-3"></i>
                Reports
            </a>
            <a href="/smartEnergy/admin/simulation" class="block hover:bg-gray-700 p-2 rounded text-white">
                <i class="fas fa-microchip mr-3"></i>
                Simulate
            </a>
            <a href="/smartEnergy/logout" class="block p-2">
                <button class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition flex items-center justify-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </button>
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between bg-white shadow-md p-4">
            <button id="menu-button" class="text-gray-600 focus:outline-none md:hidden">
                <i class="fas fa-bars text-lg"></i>
            </button>
            <h2 class="text-2xl font-semibold text-gray-800">Admin Dashboard</h2>
            <div class="flex items-center">
                <span class="text-gray-700 mr-2">Welcome, <?php echo $_SESSION['user_data']['username']; ?></span>
                <i class="fas fa-user-circle text-2xl text-gray-600"></i>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Daily Quota Remaining (Wh)</p>
                        <h3 id="dailyQuotaRemaining" class="text-2xl font-bold text-gray-900">0</h3>
                    </div>
                    <i class="fas fa-percentage text-3xl text-blue-500"></i>
                </div>
                <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Current Daily Consumption (Wh)</p>
                        <h3 id="currentDailyConsumption" class="text-2xl font-bold text-gray-900">0</h3>
                    </div>
                    <i class="fas fa-plug text-3xl text-red-500"></i>
                </div>
                <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Current Total Consumption (W)</p>
                        <h3 id="currentTotalConsumption" class="text-2xl font-bold text-gray-900">0</h3>
                    </div>
                    <i class="fas fa-bolt text-3xl text-yellow-500"></i>
                </div>
                <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Current Cost Rate ($/Wh)</p>
                        <h3 id="currentCostRate" class="text-2xl font-bold text-gray-900">0</h3>
                    </div>
                    <i class="fas fa-dollar-sign text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Smart Grid Simulation</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-lg font-medium text-gray-700 mb-4">Energy Flow</h4>
                        <div class="absolute top-4 left-4">
                            <svg width="100" height="100" viewBox="0 0 150 150" class="shine">
                                <rect x="10" y="40" width="120" height="70" rx="8" fill="#FFD700" />
                                <rect x="20" y="50" width="20" height="15" fill="#FFEC8B" />
                                <rect x="50" y="50" width="20" height="15" fill="#FFEC8B" />
                                <rect x="80" y="50" width="20" height="15" fill="#FFEC8B" />
                                <rect x="35" y="75" width="20" height="15" fill="#FFEC8B" />
                                <rect x="65" y="75" width="20" height="15" fill="#FFEC8B" />
                                <rect x="95" y="75" width="20" height="15" fill="#FFEC8B" />
                                <text x="25" y="130" font-family="Arial" font-size="16" fill="#333">Solar</text>
                                <text x="25" y="25" font-family="Arial" font-size="14" fill="#333" id="liveSolarOutput">0 W</text>
                            </svg>
                        </div>

                        <div class="absolute top-4 right-4">
                            <svg width="100" height="100" viewBox="0 0 150 150">
                                <rect x="70" y="50" width="10" height="80" fill="#a0a0a0" />
                                <circle cx="75" cy="50" r="15" fill="#555" />
                                <path id="blade1" d="M75 50 L60 20 L75 30 Z" fill="#777" class="rotate-slow"
                                    transform-origin="75px 50px" />
                                <path id="blade2" d="M75 50 L90 20 L75 30 Z" fill="#777" class="rotate-slow"
                                    transform-origin="75px 50px" />
                                <text x="25" y="130" font-family="Arial" font-size="16" fill="#333">Wind</text>
                                <text x="25" y="25" font-family="Arial" font-size="14" fill="#333" id="liveWindOutput">0 W</text>
                            </svg>
                        </div>


                        <div id="houses-container"
                            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                        </div>

                        <div class="absolute bottom-4 left-4 flex flex-col items-center">
                            <i class="fas fa-battery-full text-4xl text-green-500" id="batteryIcon"></i>
                            <span class="text-sm text-gray-700 mt-1" id="batteryLevel">5000 Wh</span>
                            <span class="text-xs text-gray-500" id="batteryStatus">Charging</span>
                        </div>

                        <div class="absolute bottom-4 right-4 flex flex-col items-center">
                            <i class="fas fa-industry text-4xl text-gray-600"></i>
                            <span class="text-sm text-gray-700 mt-1" id="gridStatus">Connected</span>
                            <span class="text-xs text-gray-500" id="gridImportExport">0 W</span>
                        </div>


                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center space-y-3">
                            <button id="startStopSimulationBtn"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full text-lg shadow-lg transition duration-200 ease-in-out transform hover:scale-105">
                                Start Simulation
                            </button>
                            <span class="text-gray-600 text-sm" id="simulatedTime">00:00</span>
                            <span class="text-gray-600 text-sm" id="currentWeather">Weather: N/A, Wind: 0 km/h</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h4 class="text-lg font-medium text-gray-700 mb-4">Simulation Metrics</h4>
                            <div class="space-y-2">
                                <p class="text-gray-700">Total Solar Generated: <span id="totalSolarGenerated"
                                        class="font-semibold">0 Wh</span></p>
                                <p class="text-gray-700">Total Wind Generated: <span id="totalWindGenerated"
                                        class="font-semibold">0 Wh</span></p>
                                <p class="text-gray-700">Total Consumption: <span id="totalConsumption"
                                        class="font-semibold">0 Wh</span></p>
                                <p class="text-gray-700">Total Grid Import: <span id="totalGridImport"
                                        class="font-semibold">0 Wh</span></p>
                                <p class="text-gray-700">Total Grid Export: <span id="totalGridExport"
                                        class="font-semibold">0 Wh</span></p>
                                <p class="text-gray-700">CO2 Emissions: <span id="co2Emissions"
                                        class="font-semibold">0 kg</span></p>
                                <p class="text-gray-700">Current Energy Cost: <span id="currentEnergyCost"
                                        class="font-semibold">$0.00</span></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h4 class="text-lg font-medium text-gray-700 mb-2">Simulation Configuration</h4>
                            <div class="space-y-2">
                                <label for="numHouses" class="block text-gray-700">Number of Houses:</label>
                                <input type="number" id="numHouses"
                                    class="w-full p-2 border border-gray-300 rounded-md" value="10">

                                <label for="dailyQuota" class="block text-gray-700">Daily Quota per House (Wh):</label>
                                <input type="number" id="dailyQuota"
                                    class="w-full p-2 border border-gray-300 rounded-md" value="1000">

                                <label for="costRate" class="block text-gray-700">Energy Cost Rate ($/Wh):</label>
                                <input type="number" step="0.00001" id="costRate"
                                    class="w-full p-2 border border-gray-300 rounded-md" value="0.00015">

                                <label for="solarProfileSelect" class="block text-gray-700">Solar/Consumption Profile:</label>
                                <select id="solarProfileSelect" class="w-full p-2 border border-gray-300 rounded-md">
                                    <option value="default">Live Weather (Default)</option>
                                    <option value="may1_refined">May 1st (Cloudy)</option>
                                    <option value="may2_refined">May 2nd (Mixed)</option>
                                    <option value="may5_table">May 5th (Clear)</option>
                                </select>

                                <button id="updateConfigBtn"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md mt-3 transition duration-200 ease-in-out">
                                    Update Configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Renewable Energy Production (W)</h4>
                    <canvas id="renewableEnergyChart"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Consumption (W)</h4>
                    <canvas id="consumptionChart"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Battery Level (Wh)</h4>
                    <canvas id="batteryLevelChart"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Grid Import / CO2 Emissions</h4>
                    <canvas id="gridCo2Chart"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Daily Summary (Today)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total Solar Generated</p>
                        <h4 id="summarySolarGenerated" class="text-xl font-bold text-gray-900">0 Wh</h4>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total Wind Generated</p>
                        <h4 id="summaryWindGenerated" class="text-xl font-bold text-gray-900">0 Wh</h4>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total Consumption</p>
                        <h4 id="summaryTotalConsumption" class="text-xl font-bold text-gray-900">0 Wh</h4>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total Grid Import</p>
                        <h4 id="summaryGridImport" class="text-xl font-bold text-gray-900">0 Wh</h4>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total Grid Export</p>
                        <h4 id="summaryGridExport" class="text-xl font-bold text-gray-900">0 Wh</h4>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total CO2 Emissions</p>
                        <h4 id="summaryCo2Emissions" class="text-xl font-bold text-gray-900">0 kg</h4>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>

    <script src="/smartEnergy/js/dashboard.js"></script>


</body>

</html>
