<?php

use Dotenv\Dotenv;
// dashboard.php
if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
    header('Location: /smartEnergy/login');
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

// Load Dotenv only if environment variables are not already loaded
// This check helps avoid errors if dotenv is loaded elsewhere in your framework
if (!getenv('WEATHER_API_KEY')) { // Check for one of the expected env vars
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
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Dashboard</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Manage Users</a>
                <a href="/smartEnergy/admin/viewPowerStats" class="block hover:bg-blue-700 p-2 rounded">View Power Stats</a>
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
                        <input type="number" id="numHouses" value="20" min="1" class="mt-1 block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="dailyQuota" class="block text-sm font-medium text-gray-700">Daily Quota Per House (Wh):</label>
                        <input type="number" id="dailyQuota" value="7000" min="1000" step="1000" class="mt-1 block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">General Stats</h2>
                    <div class="space-y-2">
                        <p>Simulated Time: <span id="simulatedTime">00:00</span></p>
                        <p>Solar Output: <span id="solarOutput">0</span> Wh</p>
                        <p>Wind Output: <span id="windOutput">0</span> Wh</p>
                        <p>Total Renewable Available: <span id="renewableAvailable">0</span> Wh</p>
                        <p>Total Consumption (Street): <span id="consumption">0</span> Wh</p>
                        <p>Active Users Consuming: <span id="activeUsersCount">0</span></p>
                        <p>Total Daily Quota (Street): <span id="totalDailyQuota">0</span> Wh</p>
                        <p>CO2 Emissions (Today): <span id="co2EmissionsToday" class="font-semibold text-green-600">0</span> kg</p>
                        <p>Condition: <span id="weatherCondition">N/A</span></p>
                        <p>Current Energy Cost Rate: <span id="costRate" class="font-semibold">Standard</span></p>
                    </div>
                </div>

                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">Battery Status</h2>
                    <div class="space-y-2">
                        <p>Total Level: <span id="batteryLevel">0</span> Wh</p>
                        <p>Status: <span id="batteryStatus" class="font-semibold">Idle</span></p>
                        <p>Reserve Level: <span id="reserveBatteryLevel">0</span> Wh</p>
                        <p>Reserve Threshold: <span id="reserveThresholdDisplay">0</span> Wh</p>
                    </div>
                </div>

                <div class="bg-white shadow p-6 rounded">
                    <h2 class="text-xl font-bold mb-4">Grid Connection</h2>
                    <div class="space-y-2">
                        <p>Status: <span id="gridStatus" class="font-semibold">Offline</span></p>
                        <p>Energy Imported (Today): <span id="gridImportToday">0</span> Wh</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // --- admin_simulation.js code starts here ---

        console.log("--- admin_simulation.js started ---"); // Confirm script execution


        // --- Configuration ---
        const apiKey = "<?php echo $_ENV['WEATHER_API_KEY']; ?>"; // Keep for fetching live weather
        const city = "Alba Iulia"; // Keep for fetching live weather
        const CO2_EMISSION_RATE_KG_PER_KWH = 0.4; // Approx. kg CO2 per kWh from average grid source (can vary)
        // Corrected simulation speed: 1 simulated minute = 1 real second (1 simulated hour = 1 real minute)
        const SIMULATION_INTERVAL_MS = 1000; // Real-world milliseconds per simulation step (1 second)
        const SIMULATION_TIME_INCREMENT_MINUTES = 1; // Simulated minutes advanced per simulation step (1 minute)

        // Pricing Tiers (Cost per Wh - example values)
        const PRICE_RATE = {
            LOW: 0.00005,
            STANDARD: 0.00015,
            HIGH: 0.00030
        };

        // Battery Configuration
        const batteryCapacity = 10000; // Wh
        // Changed Reserve Threshold to 8000 Wh as requested
        const batteryReserveThreshold = 8000; // Wh
        const batteryMinDischarge = 0; // Minimum operational level (0 Wh)


        // --- Data Profiles (Extracted from Graphs/Tables) ---
        // Data for a 24-hour period in 1-minute intervals (1440 data points)
        // Values represent PV Output in Wh, interpolated from original kW data.
        // These profiles are based on the "Consumed from PV" data as per user's focus.

        // Helper function to generate smoother data points by interpolating and converting kW to Wh
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
                for (let j = 1; j < pointsPerSparseInterval; j++) { // Start from j=1 as j=0 was the end of previous interval
                    const interpolatedValueKw = startValueKw + (endValueKw - startValueKw) * (j / pointsPerSparseInterval);
                    // Convert kW (interpolated) to Wh for the 1-minute interval in the simulation
                    const valueInWh = interpolatedValueKw * (denseIntervalMinutes / 60) * 1000;
                    denseDataWh.push(Math.max(0, Math.round(valueInWh))); // Ensure non-negative and round
                }
                // Add the end point of the interval
                const endValueInWh = endValueKw * (denseIntervalMinutes / 60) * 1000;
                denseDataWh.push(Math.max(0, Math.round(endValueInWh)));

            }

            // Ensure length is exactly 1440 / denseIntervalMinutes (1440 points for 1-min intervals)
            const expectedLength = 1440 / denseIntervalMinutes;
            while (denseDataWh.length < expectedLength) {
                denseDataWh.push(0); // Pad with zeros for remaining minutes
            }
            // Corrected: Use a separate if statement after the while loop
            if (denseDataWh.length > expectedLength) {
                denseDataWh.length = expectedLength; // Trim if somehow too long
            }


            return denseDataWh;
        }

        // Define the sparse interval at which the original data was extracted
        const sparseIntervalMinutes = 30;

        // --- Consumed from PV Data Profiles (in kW at sparseIntervalMinutes) ---
        // Based on the refined data from graphs and table.

        // May 5th Profile (Based on Table Data - 30min Sampled)
        const consumed_pv_may5_table_kw_sparse = [
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0,
            0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.9, // Value at 07:30 from table
            1.0, 2.63, 2.85, 2.55, 2.55, 2.70, // Using values at 08:00, 08:30, 08:50~09:00 etc.
            2.60, 2.45, 2.81, 2.95, // Using values at 10:00, 10:30, 10:50~11:00 etc.
            3.00, 3.00, 3.00, 3.00, // Values at 12:00, 12:30, 12:50~13:00 etc. - Adjusted last value slightly for consistency based on graph peak
            3.00, 2.75, 1.95, 2.20, // Using values at 13:30, 14:00, 14:30, 15:00 etc. - Adjusted first value based on table
            2.73, 2.50, 1.95, 1.50, // Using values at 15:30, 16:00, 16:30, 17:00 etc.
            1.50, 1.75, 1.58, 1.20, // Using values at 17:30, 18:00, 18:30, 19:00 etc.
            2.00, 0.70, 0.00, 0.00, // Using values at 19:30, 19:45~20:00, 20:30, 21:00 etc. - Adjusted 19:30 slightly
            0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00 // Assuming 0 after 21:00
        ];
        // Need to pad/trim this list to exactly 48 points if the table data isn't perfectly aligned
        while (consumed_pv_may5_table_kw_sparse.length < 48) {
            consumed_pv_may5_table_kw_sparse.push(0.0);
        }
        const may5_profile = generateSmoothProfile(consumed_pv_may5_table_kw_sparse.slice(0, 48), sparseIntervalMinutes, SIMULATION_TIME_INCREMENT_MINUTES);


        // May 1st Profile (Based on Refined Estimation - 30min Sampled)
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


        // May 2nd Profile (Based on Refined Estimation - 30min Sampled)
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

        // Map profile keys to the generated dense data (in Wh)
        const dayProfilesWh = {
            may5_table: may5_profile,
            may1_refined: may1_profile,
            may2_refined: may2_profile,
            // Add other profiles here if you create them
        };


        // --- Mapping API Weather Conditions to Day Profiles (Used only for Default scenario) ---
        function mapWeatherConditionToProfileKey(apiCondition, cloudiness) {
            const mainCondition = apiCondition.toLowerCase();
            if (mainCondition === 'clear') {
                return 'may5_table'; // Assuming May 5th table data represents a 'Clear' or 'Sunny' day
            } else if (mainCondition === 'clouds') {
                if (cloudiness > 60) { // Example threshold for heavier clouds
                    return 'may1_refined'; // Assuming May 1st refined data represents a cloudier day
                } else {
                    return 'may2_refined'; // Assuming May 2nd refined data represents a partly cloudy/fluctuating day
                }
            } else if (mainCondition === 'rain' || mainCondition === 'drizzle' || mainCondition === 'thunderstorm' || mainCondition === 'snow') {
                // You might want a profile with very low/zero output for rain/snow
                // For now, let's use a low output profile or default to one of the cloudy ones.
                return 'may1_refined'; // Example mapping for precipitation
            } else if (mainCondition === 'mist' || mainCondition === 'smoke' || mainCondition === 'haze' || mainCondition === 'dust' || mainCondition === 'fog' || mainCondition === 'sand' || mainCondition === 'ash' || mainCondition === 'squall' || mainCondition === 'tornado') {
                return 'may1_refined'; // These conditions typically reduce solar radiation - use a lower output profile
            }
            // Fallback to a default profile for unhandled conditions
            return 'may1_refined'; // Defaulting to May 1st profile
        }

        // --- Weather Fetching Function (Placeholder) ---
        // This function will simulate fetching weather data.
        // In a real application, you would make an actual API call here.
        async function fetchWeather() {
            console.log(`Fetching weather for ${city} using API key: ${apiKey.substring(0, 5)}...`); // Log API key partially for security
            try {
                // Simulate an API call with a delay
                await new Promise(resolve => setTimeout(resolve, 500)); // Simulate network latency

                // Return dummy weather data for now
                const dummyWeather = {
                    condition: "Clouds", // Can be "Clear", "Clouds", "Rain", etc.
                    wind: Math.floor(Math.random() * 15) + 5, // Random wind speed between 5 and 20 km/h
                    clouds: Math.floor(Math.random() * 100), // Random cloudiness percentage
                    temperature: Math.floor(Math.random() * 15) + 10 // Random temperature between 10 and 25
                };
                console.log("Dummy weather data fetched:", dummyWeather);
                return dummyWeather;
            } catch (error) {
                console.error("Error fetching weather data (simulated):", error);
                // Return a default/fallback weather object in case of error
                return {
                    condition: 'N/A',
                    wind: 0,
                    clouds: 0,
                    temperature: 0
                };
            }
        }

        // --- Solar Output Calculation Function ---
        // This function retrieves the solar output for a given simulated minute from the active profile.
        function getSolarOutputForTime(minutes) {
            // Ensure the minutes are within the 24-hour cycle (0 to 1439)
            const index = minutes % 1440; // Use modulo to loop around for new days if needed

            if (!currentSolarProfileDataWh || currentSolarProfileDataWh.length === 0) {
                console.warn("Solar profile data is not loaded or empty. Returning 0 solar output.");
                return 0;
            }

            if (index >= 0 && index < currentSolarProfileDataWh.length) {
                return currentSolarProfileDataWh[index];
            } else {
                console.warn(`Invalid index for solar profile data: ${index}. Array length: ${currentSolarProfileDataWh.length}. Returning 0 solar output.`);
                return 0; // Fallback for out-of-bounds index
            }
        }

        // --- Wind Output Calculation Function (Placeholder) ---
        // This function simulates wind turbine output based on a selected wind profile and live wind speed.
        // You'll need to define windProfilesWh similar to dayProfilesWh if you want different wind patterns.
        async function getWindOutput(selectedWindProfileKey, liveWindSpeed) {
            // For now, let's simplify and just use liveWindSpeed to determine output.
            // In a real scenario, you'd have wind profiles (e.g., 'high_wind', 'low_wind')
            // and map liveWindSpeed to the turbine's power curve.

            let baseWindOutputWh = 0;

            // Example simple mapping:
            if (liveWindSpeed > 20) {
                baseWindOutputWh = 500; // High wind
            } else if (liveWindSpeed > 10) {
                baseWindOutputWh = 200; // Medium wind
            } else if (liveWindSpeed > 3) {
                baseWindOutputWh = 50; // Low wind
            } else {
                baseWindOutputWh = 0; // No wind
            }

            // You can add more complex logic here using selectedWindProfileKey
            // For example, if selectedWindProfileKey is 'offshore_wind_farm', it might have higher output.
            // For now, it's a direct mapping from liveWindSpeed.

            return baseWindOutputWh;
        }


        // --- Simulation Variables ---
        let battery = batteryCapacity / 2; // Default start battery at half full
        // batteryMinDischarge is already a const defined globally, no need to redefine here
        // const batteryMinDischarge = 0; // Minimum operational level (0 Wh)

        let intervalId = null;
        let simulatedMinutes = 0; // Track simulated time in minutes (0 to 1440)

        let totalGridImportToday = 0; // Wh
        let totalCO2EmissionsToday = 0; // kg
        let currentCostRate = PRICE_RATE.STANDARD; // Current price tier

        // User/Street configuration (Admin inputs)
        let numberOfHouses = 20; // Default
        let dailyQuotaPerHouse = 7000; // Wh, Default (7 kWh)
        let totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse; // Calculated total

        // Store the last fetched weather data to use condition and wind consistently in intervals
        let lastWeatherData = {
            condition: 'N/A',
            wind: 0,
            clouds: 0
        }; // Still needed for Default mode and wind simulation

        // Store the currently active solar profile data (Wh)
        let currentSolarProfileDataWh = [];

        // Track battery status
        let batteryStatus = 'Idle';

        // Get the solar panel SVG group element
        const solarPanelGroup = document.getElementById("solarPanelGroup");
        const solarPanelRotationCenterX = 100; // X coordinate for rotation center in SVG viewBox
        const solarPanelRotationCenterY = 115; // Y coordinate for rotation center in SVG viewBox


        // --- DOM Elements ---
        const simButton = document.getElementById("simButton");
        const turbine = document.getElementById("turbine");
        const sun = document.getElementById("sun");
        const bolt = document.getElementById("bolt");

        const simulatedTimeSpan = document.getElementById("simulatedTime");
        // Battery related spans
        const batteryLevelSpan = document.getElementById("batteryLevel");
        const batteryStatusSpan = document.getElementById("batteryStatus");
        const reserveBatteryLevelSpan = document.getElementById("reserveBatteryLevel");
        const reserveThresholdDisplaySpan = document.getElementById("reserveThresholdDisplay");

        // Grid related spans
        const gridStatusSpan = document.getElementById("gridStatus");
        const gridImportTodaySpan = document.getElementById("gridImportToday");

        // Other stats spans
        const solarOutputSpan = document.getElementById("solarOutput");
        const windOutputSpan = document.getElementById("windOutput");
        const renewableAvailableSpan = document.getElementById("renewableAvailable");
        const consumptionOutputSpan = document.getElementById("consumption");
        const totalDailyQuotaSpan = document.getElementById("totalDailyQuota");
        const co2EmissionsTodaySpan = document.getElementById("co2EmissionsToday");
        const weatherConditionSpan = document.getElementById("weatherCondition");
        const costRateSpan = document.getElementById("costRate");

        // New Solar Profile Select element
        const solarProfileSelect = document.getElementById("solarProfileSelect");

        const windProfileSelect = document.getElementById("windProfile"); // Wind profile select
        const numHousesInput = document.getElementById("numHouses");
        const dailyQuotaInput = document.getElementById("dailyQuota");

        // *** NEW DOM Elements for Panel Display ***
        const panelSimulatedTimeSpan = document.getElementById("panelSimulatedTime");
        const panelTiltAngleSpan = document.getElementById("panelTiltAngle");


        // Get the current active users
        const activeUsersCountSpan = document.getElementById("activeUsersCount"); // Placeholder


        // Function to get the selected solar profile data based on the dropdown selection
        async function getSelectedSolarProfileData() {
            const selectedScenario = solarProfileSelect.value;

            if (selectedScenario === 'default') {
                // Use live weather mapping
                // Ensure lastWeatherData is fetched before mapping
                if (lastWeatherData.condition === 'N/A') { // Fetch weather if not already fetched (should be done in initialSetup)
                    // This case might occur if fetchWeather failed in initialSetup
                    console.warn("Attempting to map weather condition before initial weather fetch completed.");
                    lastWeatherData = await fetchWeather(); // Try fetching again
                }
                const profileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                return dayProfilesWh[profileKey];
            } else if (selectedScenario === 'good_day') {
                return dayProfilesWh['may5_table']; // Map Good Day to May 5th table data
            } else if (selectedScenario === 'average_day') {
                return dayProfilesWh['may2_refined']; // Map Average Day to May 2nd refined data
            } else if (selectedScenario === 'bad_day') {
                return dayProfilesWh['may1_refined']; // Map Bad Day to May 1st refined data
            }
            // Fallback to a default profile if something goes wrong
            console.error(`Unknown solar scenario selected: ${selectedScenario}. Using default profile.`);
            return dayProfilesWh['may1_refined'];
        }

        // Function to calculate solar panel tilt angle based on simulated time (minutes 0-1440)
        function calculateSolarPanelTilt(minutes) {
            const totalMinutesInDay = 1440; // 24 hours * 60 minutes
            const sunRiseMinutes = 6 * 60; // Estimate sunrise around 6:00 (360 minutes)
            const sunSetMinutes = 20 * 60; // Estimate sunset around 20:00 (8:00 PM, 1200 minutes)
            const solarNoonMinutes = 12 * 60; // Solar noon around 12:00 (720 minutes)

            // Angle range (e.g., from -30 degrees facing East to +30 degrees facing West)
            const minAngle = -30;
            const maxAngle = 30;

            if (minutes < sunRiseMinutes || minutes >= sunSetMinutes) { // Use >= for sunset minute onwards
                // During night (before sunrise or after sunset), tilt to horizontal or a fixed position
                return 0; // Horizontal
            } else if (minutes < solarNoonMinutes) { // Use < for time before solar noon
                // Morning: tilt from minAngle (at sunrise) to 0 degrees (at solar noon)
                const progress = (minutes - sunRiseMinutes) / (solarNoonMinutes - sunRiseMinutes);
                return minAngle + (0 - minAngle) * progress; // Linear interpolation
            } else { // minutes >= solarNoonMinutes
                // Afternoon: tilt from 0 degrees (at solar noon) to maxAngle (at sunset)
                const progress = (minutes - solarNoonMinutes) / (sunSetMinutes - solarNoonMinutes);
                return 0 + (maxAngle - 0) * progress; // Linear interpolation
            }
        }


        function updateUI(simTime, solar, wind, consumption, batteryLevelValue, batteryStatusText, reserveBatteryLevelValue, gridImport, co2Emissions, condition, costRateText, renewableAvailable) {
            const hours = Math.floor(simTime / 60);
            const minutes = simTime % 60;
            const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

            // Update general simulated time display
            simulatedTimeSpan.textContent = formattedTime;

            // Battery related updates
            batteryLevelSpan.textContent = `${Math.round(batteryLevelValue)} / ${batteryCapacity}`; // Round battery display
            batteryStatusSpan.textContent = batteryStatusText; // Update battery status
            reserveBatteryLevelSpan.textContent = `${Math.round(reserveBatteryLevelValue)}`; // Update reserve battery level
            reserveThresholdDisplaySpan.textContent = `${Math.round(batteryReserveThreshold)}`; // Display reserve threshold

            // Grid related updates
            gridStatusSpan.textContent = gridImport > 0 ? 'Importing' : 'Offline';
            gridStatusSpan.className = gridImport > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'; // Color based on status
            gridImportTodaySpan.textContent = `${Math.round(gridImport)}`; // Update energy imported from grid


            solarOutputSpan.textContent = `${Math.round(solar)}`;
            windOutputSpan.textContent = `${Math.round(wind)}`;
            renewableAvailableSpan.textContent = `${Math.round(renewableAvailable)}`;
            consumptionOutputSpan.textContent = `${Math.round(consumption)}`;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
            co2EmissionsTodaySpan.textContent = `${co2Emissions.toFixed(2)}`; // Show CO2 with 2 decimal places
            weatherConditionSpan.textContent = condition; // This shows the live API condition or selected scenario
            costRateSpan.textContent = costRateText;
            // Color the cost rate text
            if (costRateText === 'Low') costRateSpan.className = 'font-semibold text-green-600';
            else if (costRateText === 'High') costRateSpan.className = 'font-semibold text-red-600';
            else costRateSpan.className = 'font-semibold text-yellow-600'; // Standard

            // Update SVG animations based on solar and wind output magnitude (Wh)
            sun.style.opacity = solar > 2000 ? 1 : (solar > 500 ? 0.7 : 0.4); // Shine based on solar Wh
            // Adjust wind animation speed based on wind output (Wh)
            if (wind > 1000) {
                turbine.style.animation = "spin 0.5s linear infinite";
            } else if (wind > 300) {
                turbine.style.animation = "spin 1s linear infinite";
            } else if (wind > 100) {
                turbine.style.animation = "spin 2s linear infinite";
            } else {
                turbine.style.animation = "none";
            }

            // Show/hide bolt based on significant energy flow from solar/wind (simplified)
            bolt.style.display = (solar > 500 || wind > 200) ? 'block' : 'none';

            // *** Update Solar Panel Tilt Animation and Display ***
            if (solarPanelGroup) {
                const tiltAngle = calculateSolarPanelTilt(simTime);

                // Removed the visual tilting of the panel as requested
                // solarPanelGroup.setAttribute('transform', `rotate(${tiltAngle} ${solarPanelRotationCenterX} ${solarPanelRotationCenterY})`);

                // Update the display elements below the panel
                panelSimulatedTimeSpan.textContent = formattedTime; // Display time below panel too
                panelTiltAngleSpan.textContent = `${tiltAngle.toFixed(1)}`; // Display angle, rounded to 1 decimal place
            }
        }

        async function toggleSimulation() {
            console.log("Simulation button clicked. Toggling simulation..."); // Added console log
            const selectedScenario = solarProfileSelect.value; // Get selected scenario here

            if (intervalId) {
                // Stop simulation
                console.log("Stopping simulation.");
                clearInterval(intervalId);
                intervalId = null;
                simButton.textContent = "Start Simulation";
                turbine.style.animation = "none"; // Stop wind animation when simulation stops
                bolt.style.display = 'none'; // Hide bolt when simulation stops
                // Optionally reset solar panel tilt on stop
                if (solarPanelGroup) {
                    solarPanelGroup.setAttribute('transform', `rotate(0 ${solarPanelRotationCenterX} ${solarPanelRotationCenterY})`);
                    // Reset display elements on stop
                    panelSimulatedTimeSpan.textContent = "00:00";
                    panelTiltAngleSpan.textContent = "0";
                }
            } else {
                // Start simulation
                console.log("Starting simulation.");
                simButton.textContent = "Stop Simulation"; // Change button text to Stop immediately
                simulatedMinutes = 0; // Reset time to the start of the day
                totalGridImportToday = 0; // Reset daily grid import
                totalCO2EmissionsToday = 0; // Reset daily CO2 emissions

                // Set initial battery level based on the selected scenario
                if (selectedScenario === 'good_day') {
                    battery = batteryCapacity; // Start at full capacity for Good Day
                    console.log("Starting Good Day scenario with battery fully charged.");
                } else {
                    battery = batteryCapacity / 2; // Start at half capacity for other scenarios
                    console.log("Starting simulation with battery half charged.");
                }

                numberOfHouses = parseInt(numHousesInput.value) || 20; // Get num houses from input
                dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000; // Get quota per house from input
                totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse; // Calculate total quota

                // Fetch initial weather data if the Default scenario is selected
                if (selectedScenario === 'default') {
                    lastWeatherData = await fetchWeather();
                    console.log("Initial Weather Fetch for Default scenario:", lastWeatherData);
                } else {
                    // For predefined scenarios, we don't need to wait for weather fetch initially
                    // Set condition for display, but wind will default to 0 unless variable is chosen
                    lastWeatherData = {
                        wind: 0,
                        condition: solarProfileSelect.options[solarProfileSelect.selectedIndex].text
                    };
                }


                // Select and load the appropriate solar profile based on the SELECTED scenario
                currentSolarProfileDataWh = await getSelectedSolarProfileData(); // Use the new function
                if (!currentSolarProfileDataWh) {
                    console.error(`Could not load solar profile for selected scenario. Using default profile.`);
                    currentSolarProfileDataWh = dayProfilesWh['may1_refined']; // Fallback to a default profile
                }
                console.log(`Starting simulation with solar profile determined by: ${selectedScenario}`);


                batteryStatus = 'Idle'; // Set initial battery status
                const initialReserveLevel = Math.max(0, battery - batteryReserveThreshold);

                // Initial UI update - pass 'N/A' or fetched condition for display
                const initialDisplayCondition = selectedScenario === 'default' ? (lastWeatherData.condition || 'N/A') : lastWeatherData.condition;


                updateUI(simulatedMinutes, 0, 0, 0, battery, batteryStatus, initialReserveLevel, 0, 0, initialDisplayCondition, 'Starting...', 0);


                // *** This is the setInterval call that runs the simulation steps ***
                intervalId = setInterval(async () => {
                    const currentScenarioInLoop = solarProfileSelect.value; // Get current scenario inside the loop

                    // --- Update Solar Profile based on Scenario (Fetch weather only if Default) ---
                    if (currentScenarioInLoop === 'default') {
                        // Fetch weather data periodically (e.g., every simulated hour) for Default scenario
                        if (simulatedMinutes % 60 === 0) { // Fetch weather at the start of each simulated hour
                            const fetchedData = await fetchWeather();
                            if (fetchedData) { // Only update if fetch was successful
                                lastWeatherData = fetchedData;
                                console.log(`Weather updated at simulated ${Math.floor(simulatedMinutes/60).toString().padStart(2, '0')}:00`, lastWeatherData);

                                // Update the active solar profile based on the NEW weather condition
                                const newProfileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                                // Need to check if the profile *data* is different, not just the key string
                                const newProfileData = dayProfilesWh[newProfileKey];
                                if (newProfileData && newProfileData !== currentSolarProfileDataWh) { // Only update if the profile data changes
                                    currentSolarProfileDataWh = newProfileData;
                                    console.log(`Switched solar profile to: ${newProfileKey}`);
                                } else if (!newProfileData) {
                                    console.error(`Could not load new solar profile for key: ${newProfileKey}. Using default.`);
                                    currentSolarProfileDataWh = dayProfilesWh['may1_refined']; // Fallback
                                }
                            }
                        }
                    } else {
                        // For predefined scenarios, ensure the correct profile is loaded at the start of a new day
                        if (simulatedMinutes === 0) {
                            currentSolarProfileDataWh = await getSelectedSolarProfileData(); // Reloads based on dropdown selection
                            console.log(`Ensured correct profile for scenario '${currentScenarioInLoop}' at start of day.`);
                            // Update lastWeatherData condition for display in non-default mode
                            lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text;
                        }
                    }


                    // --- Get Solar Output (Based on Simulated Time and Active Solar Profile) ---
                    const currentSolarOutputWh = getSolarOutputForTime(simulatedMinutes);


                    // --- Get Wind Output (Based on Selected Wind Profile and Live Wind Speed) ---
                    const selectedWindProfileKey = windProfileSelect.value;
                    // Wind output comes from getWindOutput which uses the live wind speed from lastWeatherData (even in non-default solar modes)
                    const currentWindOutputWh = await getWindOutput(selectedWindProfileKey, lastWeatherData.wind);


                    // --- Simulate Total Consumption for the Street ---
                    // This is a simplified random consumption for the admin view.
                    // In a real system, this would be the sum of consumption from all user dashboards.
                    const currentConsumptionWh = Math.floor(Math.random() * (numberOfHouses * 30) + (numberOfHouses * 10)); // Scale consumption with number of houses (Wh)


                    // --- Energy Balance and Battery Management (with Reserve Logic) ---
                    const totalGenerationWh = currentSolarOutputWh + currentWindOutputWh;
                    const energyNeededWh = currentConsumptionWh;
                    let energyFromBatteryWh = 0;
                    let energyToBatteryWh = 0;
                    let energyFromGridWh = 0; // Initialize grid import for this interval
                    let renewableEnergyUsedDirectlyWh = Math.min(energyNeededWh, totalGenerationWh); // Energy used directly from solar/wind

                    let energyBalanceWh = totalGenerationWh - energyNeededWh; // Positive if surplus, negative if deficit

                    batteryStatus = 'Idle'; // Default to Idle


                    if (energyBalanceWh > 0) {
                        // Surplus energy -> Charge battery or feed to grid

                        // Prioritize charging the 'daily use' portion (up to reserve threshold)
                        // Charge up to full capacity
                        const chargeAmount = Math.min(energyBalanceWh, batteryCapacity - battery);
                        battery += chargeAmount;
                        energyBalanceWh -= chargeAmount; // Reduce surplus

                        if (chargeAmount > 0) {
                            // Determine status based on where the battery level ends up after charging
                            batteryStatus = (battery > batteryReserveThreshold) ? 'Charging (Reserve)' : 'Charging (Daily)';
                        }


                        // Any remaining energyBalanceWh > 0 is surplus fed back to grid (not simulated here)

                    } else if (energyBalanceWh < 0) {
                        // Deficit energy -> Discharge battery or draw from grid
                        const energyNeededFromOtherSourcesWh = Math.abs(energyBalanceWh);
                        let energyToSupplyWh = energyNeededFromOtherSourcesWh; // Energy that still needs to be supplied
                        let dischargeAmount = 0;


                        // Special logic for "Good Day": Rely only on generation and battery above reserve. NO GRID.
                        if (currentScenarioInLoop === 'good_day') {
                            const batteryAboveReserve = Math.max(0, battery - batteryReserveThreshold);
                            // Energy available from battery is limited to what's above the reserve threshold
                            const energyAvailableFromBatteryForDeficit = batteryAboveReserve;

                            // The amount we can discharge is limited by the energy needed AND what's available above reserve
                            dischargeAmount = Math.min(energyToSupplyWh, energyAvailableFromBatteryForDeficit);

                            battery -= dischargeAmount; // Discharge from the battery (only the portion above reserve)
                            energyFromBatteryWh = dischargeAmount;
                            energyToSupplyWh -= dischargeAmount; // Update remaining energy needed

                            // If energyToSupplyWh is still > 0 here, it means consumption exceeded Generation + Battery Above Reserve.
                            // In the Good Day scenario, this deficit is NOT met by the grid.
                            energyFromGridWh = 0; // Explicitly set grid import to 0 for Good Day


                            if (dischargeAmount > 0) {
                                batteryStatus = 'Discharging (Daily)'; // Discharging from the portion above reserve
                            }
                            // Note: If energyToSupplyWh > 0 here, the UI will still show a Consumption value higher than available renewables + battery,
                            // and the Grid Status will remain 'Offline' (because energyFromGridWh is 0),
                            // and the Battery level will not drop below the reserve threshold.
                            // This accurately models that the demand is simply not met by local sources + usable battery.


                        } else {
                            // Existing logic for other scenarios: Discharge battery fully if needed, then draw from grid

                            // Determine maximum possible discharge from the battery for this interval
                            let maxDischargePossible = battery - batteryMinDischarge; // Cannot go below 0 Wh

                            // The actual amount discharged is the minimum of energy needed and max possible discharge
                            dischargeAmount = Math.min(energyToSupplyWh, maxDischargePossible);

                            battery -= dischargeAmount;
                            energyFromBatteryWh = dischargeAmount;
                            energyToSupplyWh -= dischargeAmount; // Update remaining energy needed


                            if (dischargeAmount > 0) {
                                // Update battery status based on whether we are discharging from the daily or reserve portion
                                // Check against the threshold AFTER discharging
                                batteryStatus = (battery >= batteryReserveThreshold) ? 'Discharging (Daily)' : 'Discharging (Reserve)';
                            }

                            // Any remaining energyToSupplyWh after battery discharge must come from the grid
                            energyFromGridWh = energyToSupplyWh; // Assign remaining deficit to grid import
                        }
                    } else {
                        // energyBalanceWh is 0 - generation equals consumption
                        batteryStatus = 'Idle';
                        energyFromGridWh = 0; // Ensure grid import is 0 when balanced
                    }

                    // Ensure battery level stays within bounds (between min discharge and capacity)
                    // For Good Day, this also enforces the lower bound at the reserve threshold after any operations
                    if (currentScenarioInLoop === 'good_day') {
                        battery = Math.max(batteryReserveThreshold, Math.min(batteryCapacity, battery));
                    } else {
                        battery = Math.max(batteryMinDischarge, Math.min(batteryCapacity, battery));
                    }

                    // Calculate reserve battery level for display - always shows energy ABOVE the threshold
                    const currentReserveBatteryLevelWh = Math.max(0, battery - batteryReserveThreshold);


                    const currentRenewableAvailableWh = renewableEnergyUsedDirectlyWh + energyFromBatteryWh; // Energy supplied by renewables or battery discharge

                    // Accumulate total grid import for the day
                    totalGridImportToday += energyFromGridWh;


                    // --- CO2 Emission Calculation ---
                    // Emissions are based on energy drawn from the grid (convert Wh to kWh for calculation)
                    // This is already handled correctly as energyFromGridWh will be 0 for Good Day
                    const co2EmissionsIntervalKg = (energyFromGridWh / 1000) * CO2_EMISSION_RATE_KG_PER_KWH;
                    totalCO2EmissionsToday += co2EmissionsIntervalKg;


                    // --- Dynamic Pricing Calculation ---
                    // Base rate on the proportion of consumption met by renewables + battery and total available renewable generation
                    const renewableContributionRatio = currentConsumptionWh > 0 ? currentRenewableAvailableWh / currentConsumptionWh : 1; // Assume 100% if no consumption

                    // Adjust pricing based on renewable contribution, generation, and battery *above reserve*
                    const batteryAboveReserve = Math.max(0, battery - batteryReserveThreshold);

                    if (renewableContributionRatio >= 0.8 && totalGenerationWh > currentConsumptionWh * 0.5 && batteryAboveReserve > batteryCapacity * 0.1) { // High renewable contribution, significant generation, decent battery *above reserve*
                        currentCostRate = PRICE_RATE.LOW;
                    } else if (renewableContributionRatio >= 0.4 || batteryAboveReserve > 0) { // Moderate renewable contribution or *some* battery above reserve
                        currentCostRate = PRICE_RATE.STANDARD;
                    } else { // Low renewable contribution and no usable battery (in daily portion) -> Relying heavily on grid
                        currentCostRate = PRICE_RATE.HIGH;
                    }

                    let costRateText = Object.keys(PRICE_RATE).find(key => PRICE_RATE[key] === currentCostRate);


                    // --- Update UI ---
                    // When not in Default mode, display the selected scenario in the 'Condition' span
                    const displayCondition = currentScenarioInLoop === 'default' ? (lastWeatherData.condition || 'N/A') : lastWeatherData.condition;
                    // Pass the calculated energyFromGridWh to updateUI
                    updateUI(simulatedMinutes, currentSolarOutputWh, currentWindOutputWh, currentConsumptionWh, battery, batteryStatus, currentReserveBatteryLevelWh, totalGridImportToday, totalCO2EmissionsToday, displayCondition, costRateText, currentRenewableAvailableWh);


                    // --- Advance Simulated Time ---
                    simulatedMinutes += SIMULATION_TIME_INCREMENT_MINUTES;
                    if (simulatedMinutes >= 1440) { // 1440 minutes in a day
                        simulatedMinutes = 0; // Reset to start of the next day
                        totalGridImportToday = 0; // Reset daily grid import
                        totalCO2EmissionsToday = 0; // Reset daily CO2 emissions
                        // Battery level carries over to the next day based on its state at the end of the day
                        // For "Good Day", the battery should remain full at the start of the new day if it ended full
                        if (currentScenarioInLoop === 'good_day') {
                            // If simulating Good Day repeatedly, the battery should stay at full capacity
                            battery = batteryCapacity;
                            console.log("Good Day simulated day finished. Resetting daily stats. Battery remains full.");
                        } else {
                            // For other scenarios, the battery level at the end of the day carries over
                            console.log("Simulated day finished. Resetting daily stats. Battery level carried over.");
                        }

                        // Re-fetch weather at the start of the new simulated day if in Default mode
                        if (currentScenarioInLoop === 'default') {
                            lastWeatherData = await fetchWeather();
                            console.log("Weather fetched at start of new day for Default scenario:", lastWeatherData);
                            // Update the active solar profile based on the NEW weather condition for the new day
                            const newProfileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                            const newProfileData = dayProfilesWh[newProfileKey];
                            if (newProfileData) {
                                currentSolarProfileDataWh = newProfileData;
                                console.log(`Starting new day with solar profile: ${newProfileKey}`);
                            } else {
                                console.error(`Could not load new solar profile for key: ${newProfileKey}. Using default.`);
                                currentSolarProfileDataWh = dayProfilesWh['may1_refined']; // Fallback
                            }
                        } else {
                            // For predefined scenarios, ensure the correct profile is loaded and update display condition
                            currentSolarProfileDataWh = await getSelectedSolarProfileData(); // Reloads based on dropdown selection
                            console.log(`Ensured correct profile for scenario '${currentScenarioInLoop}' at start of new day.`);
                            lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text; // Display selected scenario name
                        }
                    }

                }, SIMULATION_INTERVAL_MS); // Update based on simulation interval
            }
        }

        simButton.addEventListener("click", toggleSimulation);

        // Update total quota display when number of houses or quota per house changes
        numHousesInput.addEventListener("change", () => {
            numberOfHouses = parseInt(numHousesInput.value) || 20;
            dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000; // Re-read in case quota also changed
            totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
        });

        dailyQuotaInput.addEventListener("change", () => {
            dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000;
            numberOfHouses = parseInt(numHousesInput.value) || 20; // Re-read in case num houses also changed
            totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
        });

        // Update initial battery level and condition display when solar scenario dropdown changes BEFORE simulation starts
        solarProfileSelect.addEventListener("change", async () => { // Made async because getSelectedSolarProfileData is async
            const selectedScenario = solarProfileSelect.value;
            if (selectedScenario === 'good_day') {
                battery = batteryCapacity; // Set battery to full
            } else {
                battery = batteryCapacity / 2; // Set battery to half full
            }
            // Fetch weather immediately on change if default is selected to update condition display
            if (selectedScenario === 'default') {
                lastWeatherData = await fetchWeather(); // Update lastWeatherData for display
            } else {
                // For predefined scenarios, set weatherData.condition for display
                lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text;
            }

            // Update UI immediately to show the changed battery level and condition
            const initialReserveLevel = Math.max(0, battery - batteryReserveThreshold);
            batteryLevelSpan.textContent = `${Math.round(battery)} / ${batteryCapacity}`;
            reserveBatteryLevelSpan.textContent = `${Math.round(initialReserveLevel)}`;
            reserveThresholdDisplaySpan.textContent = `${Math.round(batteryReserveThreshold)}`;
            weatherConditionSpan.textContent = lastWeatherData.condition; // Use the updated condition
        });


        // --- Sidebar Toggle (Keep existing) ---
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }

        // Initial setup on page load
        async function initialSetup() {
            console.log("--- initialSetup started ---"); // Debugging initial setup
            numberOfHouses = parseInt(numHousesInput.value) || 20;
            dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000;
            totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;

            // On initial load, always fetch weather to populate lastWeatherData for Default mode/wind
            lastWeatherData = await fetchWeather();
            console.log("Initial Weather Fetch on page load:", lastWeatherData);

            // Set initial battery level based on the default selected scenario on load
            const initialSelectedScenario = solarProfileSelect.value;
            if (initialSelectedScenario === 'good_day') {
                battery = batteryCapacity; // Start at full capacity for Good Day
            } else {
                battery = batteryCapacity / 2; // Start at half capacity for other scenarios
            }


            // Select and load the appropriate solar profile based on the initial dropdown selection
            currentSolarProfileDataWh = await getSelectedSolarProfileData();
            if (!currentSolarProfileDataWh) {
                console.error(`Could not load solar profile for initial selected scenario. Using default profile.`);
                currentSolarProfileDataWh = dayProfilesWh['may1_refined']; // Fallback
            }
            console.log(`Initial load with solar profile determined by: ${initialSelectedScenario}`);


            // Calculate initial reserve level for display
            const initialReserveLevel = Math.max(0, battery - batteryReserveThreshold);

            // Display initial condition based on selected scenario or fetched weather
            const initialDisplayCondition = initialSelectedScenario === 'default' ? (lastWeatherData.condition || 'N/A') : solarProfileSelect.options[solarProfileSelect.selectedIndex].text;

            updateUI(simulatedMinutes, 0, 0, 0, battery, 'Idle', initialReserveLevel, 0, 0, initialDisplayCondition, 'N/A', 0);
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`; // Ensure this is set initially
            reserveThresholdDisplaySpan.textContent = `${Math.round(batteryReserveThreshold)}`; // Display reserve threshold initially

            console.log("--- initialSetup finished ---"); // Debugging initial setup
        }

        // Call initial setup function on page load
        initialSetup();

        // --- admin_simulation.js code ends here ---
    </script>

</body>

</html>
