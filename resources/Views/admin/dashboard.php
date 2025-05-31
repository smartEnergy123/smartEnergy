<?php

use Dotenv\Dotenv;
// dashboard.php

if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
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

            0%,
            100% {
                opacity: 0.9;
            }

            50% {
                opacity: 0.4;
            }
        }

        .bolt {
            display: none;
            opacity: 1;
            font-size: 24px;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="flex min-h-screen">
        <aside id="sidebar" class="fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 w-64 bg-blue-800 text-white p-4 z-50">
            <h2 class="text-2xl font-bold mb-6">Admin Panel</h2>
            <nav class="space-y-4">
                <a href="/smartEnergy/admin/dashboard/" class="block hover:bg-blue-700 p-2 rounded">Dashboard</a>
                <a href="/smartEnergy/admin/manage-users" class="block hover:bg-blue-700 p-2 rounded">Manage Users</a>
                <a href="/smartEnergy/admin/view-power-stats" class="block hover:bg-blue-700 p-2 rounded">View Power Stats</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Reports</a>
                <a href="/smartEnergy/logout" class="block p-2">
                    <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-200 transition">Logout</button>
                </a>
            </nav>
        </aside>

        <button onclick="toggleSidebar()" class="fixed top-4 left-4 md:hidden z-50 bg-blue-800 text-white p-2 rounded shadow">☰ Menu</button>

        <main class="flex-1 p-6 ml-0 md:ml-30 transition-all duration-300">
            <h1 class="text-3xl text-center font-bold">Smart Energy Admin Dashboard</h1>
            <div class="flex justify-between items-center mb-6 mt-2">
                <h1 class="text-3xl font-bold">Welcome <span class="text-green-600"><?php echo $_SESSION['user_data']['username'] ?? 'user'; ?></span></h1>
                <button id="simButton" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Start Simulation</button>
            </div>

            <div class="bg-white shadow-lg rounded p-4 mb-6">
                <h2 class="text-xl font-semibold mb-4">Simulation Controls</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="solarProfileSelect" class="block text-sm font-medium text-gray-700">Select Solar Scenario:</label>
                        <select id="solarProfileSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="default">Default (Live Weather)</option>
                            <option value="good_day">Good Day</option>
                            <option value="average_day">Average Day</option>
                            <option value="bad_day">Bad Day</option>
                        </select>
                    </div>
                    <div>
                        <label for="windProfile" class="block text-sm font-medium text-gray-700">Select Wind Profile:</label>
                        <select id="windProfile" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="variable">Variable Wind (Uses OpenWeatherMap)</option>
                            <option value="constant_medium">Constant Medium Wind</option>
                            <option value="low">Low Wind</option>
                        </select>
                    </div>
                    <div>
                        <label for="numHouses" class="block text-sm font-medium text-gray-700">Number of Houses:</label>
                        <input type="number" id="numHouses" value="4" min="1" class="mt-1 block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="dailyQuota" class="block text-sm font-medium text-gray-700">Daily Quota Per House (Wh):</label>
                        <input type="number" id="dailyQuota" value="7000" min="1000" step="1000" class="mt-1 block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="costRateInput" class="block text-sm font-medium text-gray-700">Set Current Energy Cost Rate:</label>
                        <select id="costRateInput" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="Low">Low</option>
                            <option value="Standard" selected>Standard</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-lg rounded p-4 text-center">
                    <h2 class="text-xl font-semibold mb-4">Solar Panel</h2>
                    <p id="bolt" class="bolt relative top-20 ml-20">⚡</p>
                    <svg viewBox="0 0 200 150" class="mx-auto h-40 relative bottom-2">
                        <circle id="sun" cx="160" cy="30" r="20" fill="yellow" class="shine" />
                        <g id="solarPanelGroup" transform="rotate(0 100 115)">
                            <rect x="30" y="100" width="140" height="30" fill="#4B5563" />
                            <line x1="40" y1="100" x2="40" y2="130" stroke="white" />
                            <line x1="60" y1="100" x2="60" y2="130" stroke="white" />
                            <line x1="80" y1="100" x2="80" y2="130" stroke="white" />
                            <line x1="100" y1="100" x2="100" y2="130" stroke="white" />
                            <line x1="120" y1="100" x2="120" y2="130" stroke="white" />
                            <line x1="140" y1="100" x2="140" y2="130" stroke="white" />
                            <line x1="160" y1="100" x2="160" y2="130" stroke="white" />
                        </g>
                    </svg>
                    <p>Simulated Time: <span id="panelSimulatedTime">00:00</span></p>
                    <p>Panel Tilt: <span id="panelTiltAngle">0</span> degrees</p>
                    <p>Live Solar Output: <span id="liveSolarOutput">0</span> Wh</p>
                </div>

                <div class="bg-white shadow-lg rounded p-4 text-center">
                    <h2 class="text-xl font-semibold mb-4">Wind Turbine</h2>
                    <svg viewBox="0 0 150 150" class="mx-auto h-40">
                        <rect x="70" y="60" width="10" height="70" fill="#9CA3AF" />
                        <g id="turbine" class="rotate-slow">
                            <line x1="75" y1="60" x2="75" y2="20" stroke="gray" stroke-width="4" />
                            <line x1="75" y1="60" x2="105" y2="80" stroke="gray" stroke-width="4" />
                            <line x1="75" y1="60" x2="45" y2="80" stroke="gray" stroke-width="4" />
                        </g>
                    </svg>
                    <p>Live Wind Output: <span id="liveWindOutput">0</span> Wh</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">Daily Summary Stats</h2>
                    <div class="space-y-2">
                        <p>Simulated Time Today: <span id="summarySimulatedTime">00:00</span></p>
                        <p>Total Solar Output: <span id="summarySolarOutput">0.00</span> Wh</p>
                        <p>Total Wind Output: <span id="summaryWindOutput">0.00</span> Wh</p>
                        <p>Total Consumption: <span id="summaryConsumption">0.00</span> Wh</p>
                        <p>Total Renewable Used: <span id="summaryRenewableAvailable">0.00</span> Wh</p>
                        <p>Total Daily Quota (Street): <span id="summaryTotalDailyQuota">0</span> Wh</p>
                        <p>CO2 Emissions (Today): <span id="summaryCo2EmissionsToday" class="font-semibold text-green-600">0.00</span> kg</p>
                        <p>Current Weather: <span id="summaryWeatherCondition">N/A</span></p>
                        <p>Current Energy Cost Rate: <span id="summaryCostRate" class="font-semibold">Standard</span></p>
                    </div>
                </div>

                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">Battery Status (End of Interval)</h2>
                    <div class="space-y-2">
                        <p>Current Level: <span id="summaryBatteryLevel">0</span> Wh</p>
                        <p>Status: <span id="summaryBatteryStatus" class="font-semibold">Idle</span></p>
                        <p>Reserve Level: <span id="summaryReserveBatteryLevel">0</span> Wh</p>
                        <p>Reserve Threshold: <span id="summaryReserveThresholdDisplay">0</span> Wh</p>
                    </div>
                </div>

                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">Grid Connection (Today)</h2>
                    <div class="space-y-2">
                        <p>Status: <span id="summaryGridStatus" class="font-semibold">Offline</span></p>
                        <p>Energy Imported (Today): <span id="summaryGridImportToday">0.00</span> Wh</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // --- admin_simulation.js code starts here ---

        console.log("--- admin_simulation.js started ---");

        // --- Configuration ---
        const apiKey = "<?php echo $_ENV['WEATHER_API_KEY']; ?>";
        const city = "Alba Iulia";
        const CO2_EMISSION_RATE_KG_PER_KWH = 0.4; // Approx. kg CO2 per kWh from average grid source
        const SIMULATION_INTERVAL_MS = 1000; // Real-world milliseconds per simulation step (1 second)
        const SIMULATION_TIME_INCREMENT_MINUTES = 1; // Simulated minutes advanced per simulation step
        const AUTO_SYNC_INTERVAL_MS = 5000; // Interval for automatic updates to DB (5 seconds)
        const SUMMARY_DISPLAY_UPDATE_INTERVAL_MS = 2000; // Interval for updating summary stats from DB (2 seconds)


        // Pricing Tiers (Cost per Wh - example values)
        const PRICE_RATE = {
            LOW: 0.00005,
            STANDARD: 0.00015,
            HIGH: 0.00030
        };

        // Battery Configuration
        const batteryCapacity = 10000; // Wh
        const batteryReserveThreshold = 8000; // Wh
        const batteryMinDischarge = 0; // Minimum operational level (0 Wh)


        // --- Data Profiles (Extracted from Graphs/Tables) ---
        function generateSmoothProfile(sparseDataKw, sparseIntervalMinutes, denseIntervalMinutes) {
            const denseDataWh = [];
            const pointsPerSparseInterval = sparseIntervalMinutes / denseIntervalMinutes;

            // Add the first point
            if (sparseDataKw.length > 0) {
                const valueInWh = sparseDataKw[0] * (denseIntervalMinutes / 60) * 1000;
                denseDataWh.push(Math.max(0, Math.round(valueInWh)));
            }

            for (let i = 0; i < sparseDataKw.length - 1; i++) {
                const startValueKw = sparseDataKw[i];
                const endValueKw = sparseDataKw[i + 1];
                for (let j = 1; j < pointsPerSparseInterval; j++) {
                    const interpolatedValueKw = startValueKw + (endValueKw - startValueKw) * (j / pointsPerSparseInterval);
                    const valueInWh = interpolatedValueKw * (denseIntervalMinutes / 60) * 1000;
                    denseDataWh.push(Math.max(0, Math.round(valueInWh)));
                }
                const endValueInWh = endValueKw * (denseIntervalMinutes / 60) * 1000;
                denseDataWh.push(Math.max(0, Math.round(endValueInWh)));
            }

            const expectedLength = 1440 / denseIntervalMinutes;
            while (denseDataWh.length < expectedLength) {
                denseDataWh.push(0);
            }
            if (denseDataWh.length > expectedLength) {
                denseDataWh.length = expectedLength;
            }
            return denseDataWh;
        }

        const sparseIntervalMinutes = 30;

        const consumed_pv_may5_table_kw_sparse = [
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.9,
            1.0, 2.63, 2.85, 2.55, 2.55, 2.70,
            2.60, 2.45, 2.81, 2.95,
            3.00, 3.00, 3.00, 3.00,
            3.00, 2.75, 1.95, 2.20,
            2.73, 2.50, 1.95, 1.50,
            1.50, 1.75, 1.58, 1.20,
            2.00, 0.70, 0.00, 0.00,
            0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00
        ];
        while (consumed_pv_may5_table_kw_sparse.length < 48) {
            consumed_pv_may5_table_kw_sparse.push(0.0);
        }
        const may5_profile = generateSmoothProfile(consumed_pv_may5_table_kw_sparse.slice(0, 48), sparseIntervalMinutes, SIMULATION_TIME_INCREMENT_MINUTES);

        const consumed_pv_may1_refined_kw_sparse = [
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.2,
            0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 3.8,
            4.0, 4.1, 4.2, 4.1,
            4.0, 3.8, 3.5, 3.0,
            3.0, 2.7,
            0.8,
            2.1, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0
        ];
        while (consumed_pv_may1_refined_kw_sparse.length < 48) {
            consumed_pv_may1_refined_kw_sparse.push(0.0);
        }
        const may1_profile = generateSmoothProfile(consumed_pv_may1_refined_kw_sparse.slice(0, 48), sparseIntervalMinutes, SIMULATION_TIME_INCREMENT_MINUTES);

        const consumed_pv_may2_refined_kw_sparse = [
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.2,
            0.5, 1.0, 1.5, 2.0, 2.5,
            3.0,
            3.5,
            4.0,
            4.3,
            4.5,
            4.4,
            3.0,
            4.2,
            4.5,
            1.0,
            1.8,
            0.8,
            3.9,
            4.2,
            0.7,
            1.0,
            0.5,
            0.4,
            0.8,
            0.5,
            0.2,
            0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0
        ];
        while (consumed_pv_may2_refined_kw_sparse.length < 48) {
            consumed_pv_may2_refined_kw_sparse.push(0.0);
        }
        const may2_profile = generateSmoothProfile(consumed_pv_may2_refined_kw_sparse.slice(0, 48), sparseIntervalMinutes, SIMULATION_TIME_INCREMENT_MINUTES);

        const dayProfilesWh = {
            may5_table: may5_profile,
            may1_refined: may1_profile,
            may2_refined: may2_profile,
        };

        function mapWeatherConditionToProfileKey(apiCondition, cloudiness) {
            const mainCondition = apiCondition.toLowerCase();
            if (mainCondition === 'clear') {
                return 'may5_table';
            } else if (mainCondition === 'clouds') {
                if (cloudiness > 60) {
                    return 'may1_refined';
                } else {
                    return 'may2_refined';
                }
            } else if (mainCondition === 'rain' || mainCondition === 'drizzle' || mainCondition === 'thunderstorm' || mainCondition === 'snow') {
                return 'may1_refined';
            } else if (mainCondition === 'mist' || mainCondition === 'smoke' || mainCondition === 'haze' || mainCondition === 'dust' || mainCondition === 'fog' || mainCondition === 'sand' || mainCondition === 'ash' || mainCondition === 'squall' || mainCondition === 'tornado') {
                return 'may1_refined';
            }
            return 'may1_refined';
        }

        async function fetchWeather() {
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
                const data = await response.json();

                if (response.ok) {
                    const weather = {
                        condition: data.weather[0].main,
                        wind: data.wind.speed * 3.6, // Convert m/s to km/h
                        clouds: data.clouds.all, // Cloudiness percentage
                        temperature: data.main.temp
                    };
                    return weather;
                } else {
                    console.error("Error fetching live weather:", data.message);
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error("Error fetching weather data:", error);
                return {
                    condition: 'N/A',
                    wind: 0,
                    clouds: 0,
                    temperature: 0
                };
            }
        }

        function getSolarOutputForTime(minutes) {
            const index = minutes % 1440;
            if (!currentSolarProfileDataWh || currentSolarProfileDataWh.length === 0) {
                return 0;
            }
            if (index >= 0 && index < currentSolarProfileDataWh.length) {
                return currentSolarProfileDataWh[index];
            } else {
                return 0;
            }
        }

        async function getWindOutput(selectedWindProfileKey, liveWindSpeed) {
            let baseWindOutputWh = 0;
            if (selectedWindProfileKey === 'constant_medium') {
                baseWindOutputWh = 250;
            } else if (selectedWindProfileKey === 'low') {
                baseWindOutputWh = 50;
            } else { // 'variable' or default
                if (liveWindSpeed > 20) {
                    baseWindOutputWh = 500;
                } else if (liveWindSpeed > 10) {
                    baseWindOutputWh = 200;
                } else if (liveWindSpeed > 3) {
                    baseWindOutputWh = 50;
                } else {
                    baseWindOutputWh = 0;
                }
            }
            return baseWindOutputWh;
        }


        // --- Simulation Variables (LOCAL STATE for minute-by-minute visual) ---
        let currentSimulationId = null; // NEW: ID of the current simulation run
        let battery = batteryCapacity / 2; // LOCAL: Current battery level for the running sim
        let intervalId = null; // For the minute-by-minute simulation
        let autoSyncIntervalId = null; // For syncing to DB
        let summaryUpdateIntervalId = null; // For fetching daily summary from DB
        let simulatedMinutes = 0; // LOCAL: Track simulated time in minutes (0 to 1439) for visuals

        // LOCAL: Incremental values for the current minute
        let gridImportThisInterval = 0;
        let co2EmissionsThisInterval = 0;
        let solarOutputThisInterval = 0;
        let windOutputThisInterval = 0;
        let consumptionThisInterval = 0;
        let renewableAvailableThisInterval = 0;


        // LOCAL: Last fetched weather data (for determining solar profile and wind speed)
        let lastWeatherData = {
            condition: 'N/A',
            wind: 0,
            clouds: 0,
            temperature: 0
        };

        let currentSolarProfileDataWh = []; // LOCAL: The active solar profile data

        // LOCAL: Battery status for the running simulation
        let batteryStatus = 'Idle';

        // Admin-set configurations (fetched once and used locally)
        let numberOfHouses = 20;
        let dailyQuotaPerHouse = 7000;
        let currentCostRate = PRICE_RATE.STANDARD;
        let currentCostRateText = 'Standard'; // For display

        // Get the solar panel SVG group element
        const solarPanelGroup = document.getElementById("solarPanelGroup");
        const solarPanelRotationCenterX = 100;
        const solarPanelRotationCenterY = 115;


        // --- DOM Elements for LIVE SIMULATION VISUALS ---
        const simButton = document.getElementById("simButton");
        const turbine = document.getElementById("turbine");
        const sun = document.getElementById("sun");
        const bolt = document.getElementById("bolt");
        const panelSimulatedTimeSpan = document.getElementById("panelSimulatedTime");
        const panelTiltAngleSpan = document.getElementById("panelTiltAngle");
        const liveSolarOutputSpan = document.getElementById("liveSolarOutput"); // NEW
        const liveWindOutputSpan = document.getElementById("liveWindOutput"); // NEW


        // --- DOM Elements for ADMIN CONTROLS ---
        const solarProfileSelect = document.getElementById("solarProfileSelect");
        const windProfileSelect = document.getElementById("windProfile");
        const numHousesInput = document.getElementById("numHouses");
        const dailyQuotaInput = document.getElementById("dailyQuota");
        const costRateInput = document.getElementById("costRateInput");


        // --- DOM Elements for DAILY SUMMARY (from DB) ---
        const summarySimulatedTimeSpan = document.getElementById("summarySimulatedTime");
        const summarySolarOutputSpan = document.getElementById("summarySolarOutput");
        const summaryWindOutputSpan = document.getElementById("summaryWindOutput");
        const summaryConsumptionSpan = document.getElementById("summaryConsumption");
        const summaryRenewableAvailableSpan = document.getElementById("summaryRenewableAvailable");
        const summaryTotalDailyQuotaSpan = document.getElementById("summaryTotalDailyQuota");
        const summaryCo2EmissionsTodaySpan = document.getElementById("summaryCo2EmissionsToday");
        const summaryWeatherConditionSpan = document.getElementById("summaryWeatherCondition");
        const summaryCostRateSpan = document.getElementById("summaryCostRate");
        const summaryBatteryLevelSpan = document.getElementById("summaryBatteryLevel");
        const summaryBatteryStatusSpan = document.getElementById("summaryBatteryStatus");
        const summaryReserveBatteryLevelSpan = document.getElementById("summaryReserveBatteryLevel");
        const summaryReserveThresholdDisplaySpan = document.getElementById("summaryReserveThresholdDisplay");
        const summaryGridStatusSpan = document.getElementById("summaryGridStatus");
        const summaryGridImportTodaySpan = document.getElementById("summaryGridImportToday");


        // Function to get the selected solar profile data based on the dropdown selection
        async function getSelectedSolarProfileData(scenario) {
            if (scenario === 'default') {
                if (lastWeatherData.condition === 'N/A') {
                    lastWeatherData = await fetchWeather();
                }
                const profileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                return dayProfilesWh[profileKey];
            } else if (scenario === 'good_day') {
                return dayProfilesWh['may5_table'];
            } else if (scenario === 'average_day') {
                return dayProfilesWh['may2_refined'];
            } else if (scenario === 'bad_day') {
                return dayProfilesWh['may1_refined'];
            }
            console.error(`Unknown solar scenario selected: ${scenario}. Using default profile.`);
            return dayProfilesWh['may1_refined'];
        }

        // Function to calculate solar panel tilt angle based on simulated time
        function calculateSolarPanelTilt(minutes) {
            const sunRiseMinutes = 6 * 60; // 6:00 AM
            const sunSetMinutes = 20 * 60; // 8:00 PM
            const solarNoonMinutes = 12 * 60; // 12:00 PM
            const minAngle = -30;
            const maxAngle = 30;

            if (minutes < sunRiseMinutes || minutes >= sunSetMinutes) {
                return 0; // Horizontal during night
            } else if (minutes < solarNoonMinutes) {
                const progress = (minutes - sunRiseMinutes) / (solarNoonMinutes - sunRiseMinutes);
                return minAngle + (0 - minAngle) * progress;
            } else {
                const progress = (minutes - solarNoonMinutes) / (sunSetMinutes - solarNoonMinutes);
                return 0 + (maxAngle - 0) * progress;
            }
        }

        // UPDATED: Now only updates the LIVE UI visuals for the current minute
        function updateLiveUI(simTime, solar, wind, consumption, batteryLevelValue, batteryStatusText) {
            const hours = Math.floor(simTime / 60);
            const minutes = simTime % 60;
            const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

            // Update SVG animations based on solar and wind output magnitude (Wh)
            sun.style.opacity = solar > 2000 ? 1 : (solar > 500 ? 0.7 : 0.4);
            if (wind > 1000) {
                turbine.style.animation = "spin 0.5s linear infinite";
            } else if (wind > 300) {
                turbine.style.animation = "spin 1s linear infinite";
            } else if (wind > 100) {
                turbine.style.animation = "spin 2s linear infinite";
            } else {
                turbine.style.animation = "none";
            }
            bolt.style.display = (solar > 500 || wind > 200) ? 'block' : 'none';

            // Update display elements below the panel
            panelSimulatedTimeSpan.textContent = formattedTime;
            panelTiltAngleSpan.textContent = `${calculateSolarPanelTilt(simTime).toFixed(1)}`;
            liveSolarOutputSpan.textContent = `${Math.round(solar)}`;
            liveWindOutputSpan.textContent = `${Math.round(wind)}`;
        }


        // NEW: Function to fetch and display the daily summary from the database
        async function fetchAndDisplayDailySummary() {
            try {
                const response = await fetch('/smartEnergy/api/simulation/get-daily-summary');
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const data = result.data.data;
                    const hours = Math.floor(data.simulated_minutes / 60);
                    const minutes = data.simulated_minutes % 60;
                    const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

                    summarySimulatedTimeSpan.textContent = formattedTime;
                    summarySolarOutputSpan.textContent = `${data.total_solar_output_wh.toFixed(2)}`;
                    summaryWindOutputSpan.textContent = `${data.total_wind_output_wh.toFixed(2)}`;
                    summaryConsumptionSpan.textContent = `${data.total_consumption_wh.toFixed(2)}`;
                    summaryRenewableAvailableSpan.textContent = `${data.total_renewable_available_wh.toFixed(2)}`;
                    summaryCo2EmissionsTodaySpan.textContent = `${data.total_co2_emissions.toFixed(2)}`;
                    summaryBatteryLevelSpan.textContent = `${Math.round(data.current_battery_level_wh)} / ${batteryCapacity}`;
                    summaryBatteryStatusSpan.textContent = data.battery_status;
                    summaryGridImportTodaySpan.textContent = `${data.total_grid_import_wh.toFixed(2)}`;
                    summaryWeatherConditionSpan.textContent = data.weather_condition;
                    summaryCostRateSpan.textContent = data.current_cost_rate;
                    summaryTotalDailyQuotaSpan.textContent = `${data.num_houses * data.daily_quota_per_house_wh}`; // This is calculated on the fly from DB config

                    // Update UI colors based on status
                    summaryGridStatusSpan.textContent = data.total_grid_import_wh > 0 ? 'Importing' : 'Offline';
                    summaryGridStatusSpan.className = data.total_grid_import_wh > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600';

                    if (data.current_cost_rate === 'Low') summaryCostRateSpan.className = 'font-semibold text-green-600';
                    else if (data.current_cost_rate === 'High') summaryCostRateSpan.className = 'font-semibold text-red-600';
                    else summaryCostRateSpan.className = 'font-semibold text-yellow-600';

                    // Update reserve display from the local config values
                    summaryReserveBatteryLevelSpan.textContent = `${Math.max(0, Math.round(data.current_battery_level_wh - batteryReserveThreshold))}`;
                    summaryReserveThresholdDisplaySpan.textContent = `${Math.round(batteryReserveThreshold)}`;


                } else {
                    console.warn("No daily simulation data found or error fetching summary:", result.message);
                    // Reset summary display to zeros if no data for today
                    summarySimulatedTimeSpan.textContent = "00:00";
                    summarySolarOutputSpan.textContent = "0.00";
                    summaryWindOutputSpan.textContent = "0.00";
                    summaryConsumptionSpan.textContent = "0.00";
                    summaryRenewableAvailableSpan.textContent = "0.00";
                    summaryCo2EmissionsTodaySpan.textContent = "0.00";
                    summaryBatteryLevelSpan.textContent = `5000 / ${batteryCapacity}`; // Default battery level
                    summaryBatteryStatusSpan.textContent = "Idle";
                    summaryGridImportTodaySpan.textContent = "0.00";
                    summaryWeatherConditionSpan.textContent = "N/A";
                    summaryCostRateSpan.textContent = "Standard";
                    summaryTotalDailyQuotaSpan.textContent = "0"; // Will be updated by getAdminConfig if available
                    summaryGridStatusSpan.textContent = "Offline";
                    summaryGridStatusSpan.className = 'font-semibold text-green-600';
                    summaryCostRateSpan.className = 'font-semibold text-yellow-600'; // Standard
                    summaryReserveBatteryLevelSpan.textContent = `${Math.max(0, Math.round(5000 - batteryReserveThreshold))}`; // Default battery level for display
                    summaryReserveThresholdDisplaySpan.textContent = `${Math.round(batteryReserveThreshold)}`;
                }
            } catch (error) {
                console.error("Error fetching daily simulation summary:", error);
                // Optionally reset summary display to indicate error
            }
        }


        // Function to fetch admin configuration (numHouses, dailyQuota, costRate)
        async function fetchAdminConfig() {
            try {
                const url = '/smartEnergy/api/admin/get-simulation-config';
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.numHouses !== undefined) {
                    numHousesInput.value = data.numHouses;
                    dailyQuotaInput.value = data.dailyQuotaPerHouse;
                    costRateInput.value = data.costRate;

                    // Update local variables for simulation logic
                    numberOfHouses = parseInt(data.numHouses);
                    dailyQuotaPerHouse = parseInt(data.dailyQuotaPerHouse);
                    currentCostRateText = data.costRate;
                    currentCostRate = PRICE_RATE[currentCostRateText.toUpperCase()] || PRICE_RATE.STANDARD;

                    console.log("Admin config fetched:", data);
                } else {
                    console.log("Admin config or data completely:", data.message);
                }
            } catch (error) {
                console.error("Error fetching admin config:", error);
                // Keep default values if fetch fails
            }
        }

        // Function to send admin configuration to the backend
        async function sendAdminConfigToBackend() {
            const dataToSend = {
                numHouses: parseInt(numHousesInput.value),
                dailyQuota: parseInt(dailyQuotaInput.value),
                costRate: costRateInput.value
            };
            try {
                const response = await fetch('/smartEnergy/api/admin/set-simulation-config', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataToSend)
                });

                if (!response.ok) {
                    console.error(`Failed to set simulation config. Status: ${response.status}`);
                } else {
                    const result = await response.json();
                    if (result.status === 'success') {
                        console.log("Simulation config sent successfully.");
                    } else {
                        console.error("Error setting config:", result.message);
                    }
                }
            } catch (error) {
                console.error("Error sending admin config to backend:", error);
            }
        }

        // NEW FUNCTION: Syncs the current local simulation state to the database (incremental values)
        async function syncSimulationStateToBackend() {
            if (!currentSimulationId) {
                console.warn("No active simulation ID to sync to backend.");
                return;
            }

            const dataToSend = {
                simulationId: currentSimulationId,
                simulated_minutes: simulatedMinutes,
                current_battery_level_wh: battery,
                grid_import_wh_interval: gridImportThisInterval, // Send incremental
                co2_emissions_kg_interval: co2EmissionsThisInterval, // Send incremental
                solar_output_wh_interval: solarOutputThisInterval, // Send incremental
                wind_output_wh_interval: windOutputThisInterval, // Send incremental
                consumption_wh_interval: consumptionThisInterval, // Send incremental
                renewable_available_wh_interval: renewableAvailableThisInterval, // Send incremental
                battery_status: batteryStatus,
                weather_condition: lastWeatherData.condition,
                current_cost_rate: currentCostRateText, // Use the text value
                num_houses: numberOfHouses, // Send current config for the run
                daily_quota_per_house_wh: dailyQuotaPerHouse // Send current config for the run
            };

            // Reset incremental values after sending
            gridImportThisInterval = 0;
            co2EmissionsThisInterval = 0;
            solarOutputThisInterval = 0;
            windOutputThisInterval = 0;
            consumptionThisInterval = 0;
            renewableAvailableThisInterval = 0;


            try {
                const response = await fetch('/smartEnergy/api/simulation/update-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataToSend)
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`SYNC TO BACKEND: Failed to update simulation data: HTTP error! status: ${response.status}, raw message: ${errorText.substring(0, 500)}...`);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.status === 'success') {
                    // console.log("SYNC TO BACKEND: Simulation data updated successfully on backend. Backend response:", result);
                } else {
                    console.error("SYNC TO BACKEND: Error updating simulation data on backend. Backend message:", result.message);
                }
            } catch (error) {
                console.error("SYNC TO BACKEND: Failed to send simulation state to backend:", error);
            }
        }

        // NEW FUNCTION: Ends the simulation run in the database (sets end_time)
        async function endSimulationRunInDB() {
            if (!currentSimulationId) return;

            try {
                const response = await fetch('/smartEnergy/api/simulation/end-run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        simulationId: currentSimulationId
                    })
                });

                if (!response.ok) {
                    console.error(`Failed to end simulation run. Status: ${response.status}`);
                } else {
                    const result = await response.json();
                    if (result.status === 'success') {
                        console.log(`Simulation run ${currentSimulationId} ended successfully in DB.`);
                    } else {
                        console.error("Error ending simulation run:", result.message);
                    }
                }
            } catch (error) {
                console.error("Error sending end simulation request to backend:", error);
            } finally {
                currentSimulationId = null; // Clear the ID after ending
            }
        }


        async function toggleSimulation() {
            if (intervalId) {
                // Stop simulation
                console.log("Stopping simulation.");
                clearInterval(intervalId);
                clearInterval(autoSyncIntervalId);
                intervalId = null;
                autoSyncIntervalId = null;
                simButton.textContent = "Start Simulation";
                turbine.style.animation = "none";
                bolt.style.display = 'none';

                if (solarPanelGroup) {
                    solarPanelGroup.setAttribute('transform', `rotate(0 ${solarPanelRotationCenterX} ${solarPanelRotationCenterY})`);
                    panelSimulatedTimeSpan.textContent = "00:00";
                    panelTiltAngleSpan.textContent = "0";
                }

                // Make a final sync to persist last state and then mark as ended
                await syncSimulationStateToBackend();
                await endSimulationRunInDB();

                // Re-fetch and display the updated daily summary immediately after stopping
                fetchAndDisplayDailySummary();

            } else {
                // Start simulation
                console.log("Starting simulation.");

                // Before starting simulation, send the current admin config to DB
                await sendAdminConfigToBackend();

                // Request a new simulation run ID from the backend
                try {
                    const response = await fetch('/smartEnergy/api/simulation/start-new-run', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    const result = await response.json();

                    if (result.status === 'success' && result.data.simulationId) {
                        currentSimulationId = result.data.simulationId;
                        console.log("New simulation run ID:", currentSimulationId);
                    } else {
                        console.error("Failed to get new simulation ID from backend:", result.message);
                        alert("Could not start simulation. Please check server logs.");
                        return; // Exit if we can't get an ID
                    }
                } catch (error) {
                    console.error("Error requesting new simulation run:", error);
                    alert("Network error starting simulation. Please try again.");
                    return; // Exit if network error
                }

                simButton.textContent = "Stop Simulation";

                // Reset LOCAL simulation variables for the new run
                simulatedMinutes = 0;
                battery = batteryCapacity / 2; // Initial battery level for THIS run
                gridImportThisInterval = 0;
                co2EmissionsThisInterval = 0;
                solarOutputThisInterval = 0;
                windOutputThisInterval = 0;
                consumptionThisInterval = 0;
                renewableAvailableThisInterval = 0;
                batteryStatus = 'Idle';


                // Fetch initial weather data (for 'default' scenario)
                lastWeatherData = await fetchWeather();
                currentSolarProfileDataWh = await getSelectedSolarProfileData(solarProfileSelect.value);
                // Update `lastWeatherData.condition` based on selected scenario for consistent display
                if (solarProfileSelect.value !== 'default') {
                    lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text;
                }

                // Initial update for LIVE UI visuals
                updateLiveUI(simulatedMinutes, solarOutputThisInterval, windOutputThisInterval, consumptionThisInterval, battery, batteryStatus);


                // *** This is the setInterval call that runs the simulation steps ***
                intervalId = setInterval(async () => {
                    const currentScenarioInLoop = solarProfileSelect.value;

                    // Fetch weather data periodically (e.g., every simulated hour) for Default scenario
                    if (currentScenarioInLoop === 'default' && simulatedMinutes % 60 === 0) {
                        const fetchedData = await fetchWeather();
                        if (fetchedData) {
                            lastWeatherData = fetchedData;
                            const newProfileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                            const newProfileData = dayProfilesWh[newProfileKey];
                            if (newProfileData && newProfileData !== currentSolarProfileDataWh) {
                                currentSolarProfileDataWh = newProfileData;
                            }
                        }
                    } else if (simulatedMinutes === 0) { // Ensure correct profile at start of new day for fixed scenarios
                        currentSolarProfileDataWh = await getSelectedSolarProfileData(currentScenarioInLoop);
                        if (currentScenarioInLoop !== 'default') {
                            lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text;
                        }
                    }


                    // --- Get Outputs and Consumption for the current minute ---
                    solarOutputThisInterval = getSolarOutputForTime(simulatedMinutes);
                    windOutputThisInterval = await getWindOutput(windProfileSelect.value, lastWeatherData.wind);
                    consumptionThisInterval = Math.floor(Math.random() * (numberOfHouses * 30) + (numberOfHouses * 10)); // Scaled per minute


                    // --- Energy Balance and Battery Management ---
                    const totalGenerationWh = solarOutputThisInterval + windOutputThisInterval;
                    const energyNeededWh = consumptionThisInterval;
                    let energyFromBatteryWh = 0;
                    let energyToBatteryWh = 0;
                    let energyFromGridCurrentMinute = 0; // NEW: Energy imported for THIS minute

                    let energyBalanceWh = totalGenerationWh - energyNeededWh;

                    batteryStatus = 'Idle';

                    if (energyBalanceWh > 0) {
                        const chargeAmount = Math.min(energyBalanceWh, batteryCapacity - battery);
                        battery += chargeAmount;
                        energyBalanceWh -= chargeAmount;
                        if (chargeAmount > 0) {
                            batteryStatus = (battery > batteryReserveThreshold) ? 'Charging (Reserve)' : 'Charging (Daily)';
                        }
                    } else if (energyBalanceWh < 0) {
                        const energyNeededFromOtherSourcesWh = Math.abs(energyBalanceWh);
                        let energyToSupplyWh = energyNeededFromOtherSourcesWh;
                        let dischargeAmount = 0;

                        if (currentScenarioInLoop === 'good_day') {
                            const batteryAboveReserve = Math.max(0, battery - batteryReserveThreshold);
                            dischargeAmount = Math.min(energyToSupplyWh, batteryAboveReserve);
                            battery -= dischargeAmount;
                            energyFromBatteryWh = dischargeAmount;
                            energyToSupplyWh -= dischargeAmount;
                            energyFromGridCurrentMinute = 0; // Explicitly no grid for Good Day
                            if (dischargeAmount > 0) {
                                batteryStatus = 'Discharging (Daily)';
                            }
                        } else {
                            let maxDischargePossible = battery - batteryMinDischarge;
                            dischargeAmount = Math.min(energyToSupplyWh, maxDischargePossible);
                            battery -= dischargeAmount;
                            energyFromBatteryWh = dischargeAmount;
                            energyToSupplyWh -= dischargeAmount;

                            if (dischargeAmount > 0) {
                                batteryStatus = (battery >= batteryReserveThreshold) ? 'Discharging (Daily)' : 'Discharging (Reserve)';
                            }
                            energyFromGridCurrentMinute = energyToSupplyWh;
                        }
                    }

                    // Ensure battery level stays within bounds
                    if (currentScenarioInLoop === 'good_day') {
                        battery = Math.max(batteryReserveThreshold, Math.min(batteryCapacity, battery));
                    } else {
                        battery = Math.max(batteryMinDischarge, Math.min(batteryCapacity, battery));
                    }

                    // Accumulate incremental grid import for this minute
                    gridImportThisInterval += energyFromGridCurrentMinute;

                    // Accumulate incremental CO2 emissions for this minute
                    co2EmissionsThisInterval += (energyFromGridCurrentMinute / 1000) * CO2_EMISSION_RATE_KG_PER_KWH;

                    // Accumulate incremental renewable available for this minute
                    renewableAvailableThisInterval += (solarOutputThisInterval + windOutputThisInterval);

                    // Update LIVE UI visuals
                    updateLiveUI(simulatedMinutes, solarOutputThisInterval, windOutputThisInterval, consumptionThisInterval, battery, batteryStatus);


                    // --- Advance Simulated Time ---
                    simulatedMinutes += SIMULATION_TIME_INCREMENT_MINUTES;
                    if (simulatedMinutes >= 1440) { // 1440 minutes in a day
                        simulatedMinutes = 0; // Reset to start of the next day
                        // Battery level carries over to the next day based on its state
                        if (currentScenarioInLoop === 'good_day') {
                            battery = batteryCapacity; // Always full at start of good day
                        }
                        // Reset incremental values for the new day
                        gridImportThisInterval = 0;
                        co2EmissionsThisInterval = 0;
                        solarOutputThisInterval = 0;
                        windOutputThisInterval = 0;
                        consumptionThisInterval = 0;
                        renewableAvailableThisInterval = 0;
                    }

                }, SIMULATION_INTERVAL_MS);


                // Start the 5-second auto-sync for simulation data to DB
                autoSyncIntervalId = setInterval(syncSimulationStateToBackend, AUTO_SYNC_INTERVAL_MS);
            }
        }

        simButton.addEventListener("click", toggleSimulation);

        // Update local config variables when inputs change
        numHousesInput.addEventListener("change", () => {
            numberOfHouses = parseInt(numHousesInput.value) || 20;
            sendAdminConfigToBackend(); // Send updated config to DB
        });

        dailyQuotaInput.addEventListener("change", () => {
            dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000;
            sendAdminConfigToBackend(); // Send updated config to DB
        });

        costRateInput.addEventListener("change", () => {
            currentCostRateText = costRateInput.value;
            currentCostRate = PRICE_RATE[currentCostRateText.toUpperCase()] || PRICE_RATE.STANDARD;
            sendAdminConfigToBackend(); // Send updated config to DB
            // Update summary display immediately for cost rate
            summaryCostRateSpan.textContent = currentCostRateText;
            if (currentCostRateText === 'Low') summaryCostRateSpan.className = 'font-semibold text-green-600';
            else if (currentCostRateText === 'High') summaryCostRateSpan.className = 'font-semibold text-red-600';
            else summaryCostRateSpan.className = 'font-semibold text-yellow-600';
        });

        // Event listener for solar scenario dropdown to update `lastWeatherData.condition` for display
        solarProfileSelect.addEventListener("change", async () => {
            const selectedScenario = solarProfileSelect.value;
            if (selectedScenario === 'default') {
                lastWeatherData = await fetchWeather(); // Update weather object
            } else {
                lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text; // Update for display
            }
            // If simulation is NOT running, also update the local battery preview
            if (!intervalId) {
                battery = (selectedScenario === 'good_day') ? batteryCapacity : (batteryCapacity / 2);
                updateLiveUI(simulatedMinutes, solarOutputThisInterval, windOutputThisInterval, consumptionThisInterval, battery, batteryStatus); // Update live battery display
                // The summary display is handled by `WorkspaceAndDisplayDailySummary`
            }
        });


        // Initial setup on page load
        async function initialSetup() {
            console.log("--- initialSetup started ---");

            // 1. Fetch Admin Configuration (numHouses, dailyQuota, costRate)
            await fetchAdminConfig();

            // 2. Fetch initial weather for `lastWeatherData` object
            lastWeatherData = await fetchWeather();
            // After fetching weather, determine the initial solar profile data
            currentSolarProfileDataWh = await getSelectedSolarProfileData(solarProfileSelect.value);
            // Ensure lastWeatherData.condition is set correctly for display in non-default modes initially
            if (solarProfileSelect.value !== 'default') {
                lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text;
            }


            // 3. Populate initial LIVE UI visuals with zeros/defaults (simulation not running yet)
            updateLiveUI(0, 0, 0, 0, batteryCapacity / 2, 'Idle'); // Initial state for live visuals
            liveSolarOutputSpan.textContent = "0"; // Explicitly set if updateLiveUI doesn't reset it fully
            liveWindOutputSpan.textContent = "0"; // Explicitly set if updateLiveUI doesn't reset it fully


            // 4. Start automatic updates for the DAILY SUMMARY section from the database
            // This ensures the summary section always shows the latest persisted state for today,
            // whether the simulation is running or not.
            summaryUpdateIntervalId = setInterval(fetchAndDisplayDailySummary, SUMMARY_DISPLAY_UPDATE_INTERVAL_MS);
            fetchAndDisplayDailySummary(); // Call once immediately on load

            console.log("--- initialSetup finished ---");
        }

        // Call initial setup function on page load
        initialSetup();

        // --- Sidebar Toggle ---
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }
    </script>

</body>

</html>
