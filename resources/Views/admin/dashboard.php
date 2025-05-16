<?php

use Dotenv\Dotenv;
// dashboard.php
if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!getenv('DB_HOST')) {
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
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Settings</a>
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
                        <label for="dayProfile" class="block text-sm font-medium text-gray-700">Select Solar Day Profile:</label>
                        <select id="dayProfile" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="sunny">Sunny Day Profile (Based on Data)</option>
                            <option value="partly_cloudy">Partly Cloudy Profile (Based on Data)</option>
                            <option value="low_irradiance">Low Irradiance Profile (Inferred)</option>
                            <option value="manual">Manual (Uses OpenWeatherMap - Solar calculation less accurate)</option>
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
                        <rect x="30" y="100" width="140" height="30" fill="#4B5563" />
                        <line x1="40" y1="100" x2="40" y2="130" stroke="white" />
                        <line x1="60" y1="100" x2="60" y2="130" stroke="white" />
                        <line x1="80" y1="100" x2="80" y2="130" stroke="white" />
                        <line x1="100" y1="100" x2="100" y2="130" stroke="white" />
                        <line x1="120" y1="100" x2="120" y2="130" stroke="white" />
                        <line x1="140" y1="100" x2="140" y2="130" stroke="white" />
                        <line x1="160" y1="100" x2="160" y2="130" stroke="white" />
                    </svg>
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

            <div class="mt-8 bg-white shadow p-6 rounded">
                <h2 class="text-xl font-bold mb-4">Live Stats</h2>
                <div id="stats" class="space-y-2">
                    <p>Simulated Time: <span id="simulatedTime">00:00</span></p>
                    <p>Battery Level: <span id="batteryLevel">0</span> Wh</p>
                    <p>Grid Status: <span id="gridStatus" class="font-semibold">Offline</span></p>
                    <p>Solar Output: <span id="solarOutput">0</span> Wh</p>
                    <p>Wind Output: <span id="windOutput">0</span> Wh</p>
                    <p>Active Users Consuming: <span id="activeUsersCount">0</span></p>
                    <p>Total Renewable Available: <span id="renewableAvailable">0</span> Wh</p>
                    <p>Total Consumption (Street): <span id="consumption">0</span> Wh</p>
                    <p>Total Daily Quota (Street): <span id="totalDailyQuota">0</span> Wh</p>
                    <p>Energy from Grid (Today): <span id="gridImportToday">0</span> Wh</p>
                    <p>CO2 Emissions (Today): <span id="co2EmissionsToday" class="font-semibold text-green-600">0</span> kg</p>
                    <p>Condition: <span id="weatherCondition">N/A</span></p>
                    <p>Current Energy Cost Rate: <span id="costRate" class="font-semibold">Standard</span></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // --- Configuration ---
        const apiKey = "<?php echo $_ENV['WEATHER_API_KEY']; ?>"; // Keep for manual mode
        const city = "Alba Iulia"; // Keep for manual mode
        const CO2_EMISSION_RATE_KG_PER_KWH = 0.4; // Approx. kg CO2 per kWh from average grid source (can vary)
        const SIMULATION_INTERVAL_MS = 100; // Real-world milliseconds per simulation step
        const SIMULATION_TIME_INCREMENT_MINUTES = 1; // Simulated minutes advanced per simulation step (was 30)
        // To simulate 24 hours (1440 minutes) in ~240 seconds (4 minutes), interval = 100ms, increment = 1 min

        // Pricing Tiers (Cost per Wh - example values)
        const PRICE_RATE = {
            LOW: 0.00005, // Very cheap when lots of renewable available (e.g., during peak sun/wind)
            STANDARD: 0.00015, // Normal price (mix of renewables and battery/grid)
            HIGH: 0.00030 // Expensive when relying heavily on grid
        };


        // --- Data Profiles (Manually Extracted from Graphs - APPROXIMATE) ---
        // Data for a 24-hour period in 1-minute intervals (1440 data points) for smoother simulation
        // Values represent PV Output in Wh (scaled from kW in graphs for simulation)
        // **IMPORTANT: REPLACE THESE WITH MORE ACCURATE VALUES EXTRACTED FROM YOUR GRAPHS at 1-minute intervals**
        // I've scaled the previous 30-min data linearly between points for this example.
        const dayProfiles = {
            sunny: generateSmoothProfile([ // Based roughly on May 5th/6th - peak ~12-14kW
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, // 00:00 - 06:00 (13 points for 30 min intervals)
                50, 150, 400, // 06:30 - 07:30
                800, 1500, 2500, 4000, // 08:00 - 09:30
                6000, 8000, 10000, 12000, // 10:00 - 11:30
                13000, 14000, 13500, 12500, // 12:00 - 13:30
                11000, 9000, 7000, 5000, // 14:00 - 15:30
                3000, 1500, 700, 200, // 16:00 - 17:30
                50, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 // 18:00 - 23:30 (12 points)
            ], 30, SIMULATION_TIME_INCREMENT_MINUTES), // Data points were every 30 mins, now interpolate to 1 min
            partly_cloudy: generateSmoothProfile([ // Based roughly on May 1st/2nd - peak ~4-5kW with fluctuations
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                30, 100, 250,
                500, 800, 1200, 1500,
                1800, 2200, 2000, 2500, // Example of a dip
                3000, 2800, 2500, 3500,
                4000, 3800, 3000, 2000,
                1000, 500, 150, 30,
                10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
            ], 30, SIMULATION_TIME_INCREMENT_MINUTES),
            low_irradiance: generateSmoothProfile([ // Inferred profile for a very cloudy/rainy day
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                10, 20, 50,
                80, 120, 150, 180,
                200, 250, 220, 200,
                180, 150, 100, 80,
                50, 30, 10, 5,
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
            ], 30, SIMULATION_TIME_INCREMENT_MINUTES),
            manual: [] // Placeholder for manual (OpenWeatherMap) mode
        };

        // Helper function to generate smoother data points by interpolating
        function generateSmoothProfile(sparseData, sparseIntervalMinutes, denseIntervalMinutes) {
            const denseData = [];
            const pointsPerSparseInterval = sparseIntervalMinutes / denseIntervalMinutes;
            for (let i = 0; i < sparseData.length - 1; i++) {
                const startValue = sparseData[i];
                const endValue = sparseData[i + 1];
                for (let j = 0; j < pointsPerSparseInterval; j++) {
                    const interpolatedValue = startValue + (endValue - startValue) * (j / pointsPerSparseInterval);
                    denseData.push(Math.max(0, Math.round(interpolatedValue))); // Ensure non-negative and round
                }
            }
            // Add the last point
            denseData.push(Math.max(0, Math.round(sparseData[sparseData.length - 1])));

            // Pad with zeros if needed to reach 1440 minutes (should be handled by input data though)
            while (denseData.length < 1440 / denseIntervalMinutes) {
                denseData.push(0);
            }

            return denseData;
        }


        // --- Simulation Variables ---
        let battery = 5000;
        const batteryCapacity = 10000; // Wh
        let intervalId = null;
        let simulatedMinutes = 0; // Track simulated time in minutes (0 to 1440)

        let totalGridImportToday = 0; // Wh
        let totalCO2EmissionsToday = 0; // kg
        let currentCostRate = PRICE_RATE.STANDARD; // Current price tier

        // User/Street configuration (Admin inputs)
        let numberOfHouses = 20; // Default
        let dailyQuotaPerHouse = 7000; // Wh, Default (7 kWh)
        let totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse; // Calculated total

        // --- DOM Elements ---
        const simButton = document.getElementById("simButton");
        const turbine = document.getElementById("turbine");
        const sun = document.getElementById("sun");
        const bolt = document.getElementById("bolt");

        const simulatedTimeSpan = document.getElementById("simulatedTime");
        const batteryLevelSpan = document.getElementById("batteryLevel");
        const gridStatusSpan = document.getElementById("gridStatus");
        const solarOutputSpan = document.getElementById("solarOutput");
        const windOutputSpan = document.getElementById("windOutput");
        const renewableAvailableSpan = document.getElementById("renewableAvailable");
        const consumptionOutputSpan = document.getElementById("consumption");
        const totalDailyQuotaSpan = document.getElementById("totalDailyQuota");
        const gridImportTodaySpan = document.getElementById("gridImportToday");
        const co2EmissionsTodaySpan = document.getElementById("co2EmissionsToday");
        const weatherConditionSpan = document.getElementById("weatherCondition");
        const costRateSpan = document.getElementById("costRate");

        const dayProfileSelect = document.getElementById("dayProfile");
        const windProfileSelect = document.getElementById("windProfile"); // New wind profile select
        const numHousesInput = document.getElementById("numHouses");
        const dailyQuotaInput = document.getElementById("dailyQuota");


        // Get the current active users
        const activeUsersCountSpan = document.getElementById("activeUsersCount"); // New element

        // ... in your setInterval loop or a separate fetch ...
        // TODO: Fetch actual active user count from backend
        // activeUsersCountSpan.textContent = fetchedActiveUserCount;


        // --- Simulation Logic ---

        // Function to get solar output from the selected profile based on simulated time
        function getSolarOutputFromProfile(minutes) {
            const selectedProfileKey = dayProfileSelect.value;
            if (selectedProfileKey === 'manual') {
                // In manual mode, the solar value comes from the fetchWeather function
                return -1; // Indicate manual mode lookup
            }
            const profile = dayProfiles[selectedProfileKey];
            if (!profile || profile.length !== 1440 / SIMULATION_TIME_INCREMENT_MINUTES) { // Check for 1440 points / increment
                console.error(`Selected solar day profile "${selectedProfileKey}" is invalid or incomplete.`);
                return 0;
            }
            // Calculate the index for the time interval
            const index = Math.floor(minutes / SIMULATION_TIME_INCREMENT_MINUTES);
            return profile[index] || 0;
        }

        // Function to get wind output based on profile or live data
        async function getWindOutput(windProfileKey) {
            switch (windProfileKey) {
                case 'variable':
                    try {
                        const weatherData = await fetchWeather(); // Fetch live data for wind speed
                        return Math.floor((weatherData.wind || 0) * 100); // Convert wind speed to a power value (example scaling)
                    } catch (error) {
                        console.error("Failed to fetch wind data:", error);
                        return Math.floor(Math.random() * 200 + 50); // Fallback random wind
                    }
                case 'constant_medium':
                    return 500; // Example constant medium wind power (Wh)
                case 'low':
                    return 100; // Example low wind power (Wh)
                default:
                    return 0;
            }
        }

        // Function to fetch weather (primarily for manual solar, variable wind, and live condition)
        async function fetchWeather() {
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
                const data = await response.json();
                const windSpeed = data.wind.speed;
                const condition = data.weather[0].main;

                // Original solar calculation based on cloudiness (less accurate now, only for manual mode)
                const solar_manual = Math.max(0, 400 - (data.clouds?.all || 0) * 3); // Use optional chaining

                return {
                    solar: solar_manual,
                    wind: windSpeed,
                    condition
                };
            } catch (error) {
                console.error("Weather API error:", error);
                // Fallback random values if API fails
                return {
                    solar: Math.floor(Math.random() * 300 + 100),
                    wind: Math.random() * 5 + 2, // Fallback wind speed
                    condition: "Unavailable"
                };
            }
        }


        function updateUI(simTime, solar, wind, consumption, batteryLevelValue, gridImport, co2Emissions, condition, costRateText, renewableAvailable) {
            const hours = Math.floor(simTime / 60);
            const minutes = simTime % 60;
            const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

            simulatedTimeSpan.textContent = formattedTime;
            batteryLevelSpan.textContent = `${Math.round(batteryLevelValue)} / ${batteryCapacity}`; // Round battery display
            gridStatusSpan.textContent = gridImport > 0 ? 'Importing' : 'Offline';
            gridStatusSpan.className = gridImport > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'; // Color based on status
            solarOutputSpan.textContent = `${Math.round(solar)}`;
            windOutputSpan.textContent = `${Math.round(wind)}`;
            renewableAvailableSpan.textContent = `${Math.round(renewableAvailable)}`;
            consumptionOutputSpan.textContent = `${Math.round(consumption)}`;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
            gridImportTodaySpan.textContent = `${Math.round(gridImport)}`;
            co2EmissionsTodaySpan.textContent = `${co2Emissions.toFixed(2)}`; // Show CO2 with 2 decimal places
            weatherConditionSpan.textContent = condition;
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
        }

        function toggleSimulation() {
            if (intervalId) {
                // Stop simulation
                clearInterval(intervalId);
                intervalId = null;
                simButton.textContent = "Start Simulation";
                turbine.style.animation = "none"; // Stop wind animation when simulation stops
                bolt.style.display = 'none'; // Hide bolt when simulation stops
            } else {
                // Start simulation
                simButton.textContent = "Stop Simulation";
                simulatedMinutes = 0; // Reset time to the start of the day
                totalGridImportToday = 0; // Reset daily grid import
                totalCO2EmissionsToday = 0; // Reset daily CO2 emissions
                battery = batteryCapacity / 2; // Reset battery to half full (example)
                numberOfHouses = parseInt(numHousesInput.value) || 20; // Get num houses from input
                dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000; // Get quota per house from input
                totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse; // Calculate total quota
                updateUI(simulatedMinutes, 0, 0, 0, battery, 0, 0, 'Starting...', '...'); // Initial UI update

                // Initial setup for UI elements that depend on inputs
                totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;


                intervalId = setInterval(async () => {
                    const selectedSolarProfileKey = dayProfileSelect.value;
                    const selectedWindProfileKey = windProfileSelect.value;

                    let currentSolarOutput = 0;
                    let currentWindOutput = 0;
                    let liveWeatherCondition = 'Simulating'; // Default condition text

                    // --- Get Solar Output ---
                    if (selectedSolarProfileKey === 'manual') {
                        const weatherData = await fetchWeather();
                        currentSolarOutput = Math.max(0, weatherData.solar * 10); // Scale manual solar (example scale)
                        liveWeatherCondition = weatherData.condition; // Use live condition in manual mode
                        currentWindOutput = Math.floor((weatherData.wind || 0) * 100); // Use live wind in manual mode
                    } else {
                        currentSolarOutput = getSolarOutputFromProfile(simulatedMinutes);
                        // Fetch weather only for live wind/condition display in profile mode
                        if (selectedWindProfileKey === 'variable') {
                            const weatherData = await fetchWeather();
                            currentWindOutput = Math.floor((weatherData.wind || 0) * 100);
                            liveWeatherCondition = weatherData.condition;
                        } else {
                            currentWindOutput = await getWindOutput(selectedWindProfileKey); // Get wind from selected profile
                            liveWeatherCondition = selectedSolarProfileKey.replace('_', ' ') + " Day"; // Base condition on solar profile
                        }

                    }

                    // --- Get Wind Output (If not using manual solar which fetches wind) ---
                    if (selectedSolarProfileKey !== 'manual') {
                        currentWindOutput = await getWindOutput(selectedWindProfileKey);
                    }


                    // --- Simulate Total Consumption for the Street ---
                    // This is a simplified random consumption for the admin view.
                    // In a real system, this would be the sum of consumption from all user dashboards.
                    const currentConsumption = Math.floor(Math.random() * (numberOfHouses * 30) + (numberOfHouses * 10)); // Scale consumption with number of houses (Wh)


                    // --- Energy Balance and Battery Management ---
                    const totalGeneration = currentSolarOutput + currentWindOutput;
                    const energyNeeded = currentConsumption;
                    let energyFromBattery = 0;
                    let energyToBattery = 0;
                    let energyFromGrid = 0;
                    let renewableEnergyUsedDirectly = Math.min(energyNeeded, totalGeneration); // Energy used directly from solar/wind

                    let energyRemainingAfterDirectUse = energyNeeded - renewableEnergyUsedDirectly; // Energy still needed or surplus

                    if (energyRemainingAfterDirectUse > 0) {
                        // Consumption > Generation -> Need energy from battery or grid
                        const energyFromBatteryPossible = Math.min(energyRemainingAfterDirectUse, battery);
                        energyFromBattery = energyFromBatteryPossible;
                        battery -= energyFromBattery;
                        energyRemainingAfterDirectUse -= energyFromBattery; // Energy still needed after battery

                        if (energyRemainingAfterDirectUse > 0) {
                            // Still need energy after battery -> Draw from grid
                            energyFromGrid = energyRemainingAfterDirectAfterDirectUse;
                            totalGridImportToday += energyFromGrid; // Accumulate grid import
                        }

                    } else if (energyRemainingAfterDirectUse < 0) {
                        // Generation > Consumption -> Surplus energy
                        const surplusEnergy = Math.abs(energyRemainingAfterDirectUse);
                        const energyToBatteryPossible = Math.min(surplusEnergy, batteryCapacity - battery);
                        energyToBattery = energyToBatteryPossible;
                        battery += energyToBattery;
                        // Any surplus energy not stored could be fed back to the grid (not simulated here)
                    }

                    const currentRenewableAvailable = renewableEnergyUsedDirectly + energyFromBattery; // Energy supplied by renewables or battery discharge

                    // --- CO2 Emission Calculation ---
                    // Emissions are based on energy drawn from the grid (convert Wh to kWh for calculation)
                    const co2EmissionsInterval = (energyFromGrid / 1000) * CO2_EMISSION_RATE_KG_PER_KWH;
                    totalCO2EmissionsToday += co2EmissionsInterval;


                    // --- Dynamic Pricing Calculation ---
                    // Base rate on the proportion of consumption met by renewables + battery
                    // (Simplified: could also be based just on total generation amount)
                    const renewableContributionRatio = currentConsumption > 0 ? currentRenewableAvailable / currentConsumption : 1; // Assume 100% if no consumption

                    if (renewableContributionRatio >= 0.8 && totalGeneration > currentConsumption * 0.5) { // High renewable contribution and significant generation
                        currentCostRate = PRICE_RATE.LOW;
                    } else if (renewableContributionRatio >= 0.4 || battery > batteryCapacity * 0.2) { // Moderate renewable contribution or decent battery level
                        currentCostRate = PRICE_RATE.STANDARD;
                    } else { // Low renewable contribution and low battery -> Relying on grid
                        currentCostRate = PRICE_RATE.HIGH;
                    }

                    let costRateText = Object.keys(PRICE_RATE).find(key => PRICE_RATE[key] === currentCostRate);


                    // --- Update UI ---
                    updateUI(simulatedMinutes, currentSolarOutput, currentWindOutput, currentConsumption, battery, totalGridImportToday, totalCO2EmissionsToday, liveWeatherCondition, costRateText, currentRenewableAvailable);


                    // --- Advance Simulated Time ---
                    simulatedMinutes += SIMULATION_TIME_INCREMENT_MINUTES;
                    if (simulatedMinutes >= 1440) { // 1440 minutes in a day
                        simulatedMinutes = 0; // Reset to start of the next day
                        totalGridImportToday = 0; // Reset daily grid import
                        totalCO2EmissionsToday = 0; // Reset daily CO2 emissions
                        // You might want to reset daily quota usage for each user here (backend/user side logic)
                        console.log("Simulated day finished. Resetting daily stats.");
                    }

                }, SIMULATION_INTERVAL_MS); // Update based on simulation interval
            }
        }

        simButton.addEventListener("click", toggleSimulation);

        // Update total quota display when number of houses or quota per house changes
        numHousesInput.addEventListener("change", () => {
            numberOfHouses = parseInt(numHousesInput.value) || 20;
            totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
        });

        dailyQuotaInput.addEventListener("change", () => {
            dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000;
            numberOfHouses = parseInt(numHousesInput.value) || 20; // Re-read in case num houses also changed
            totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;
            totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`;
        });


        // --- Sidebar Toggle (Keep existing) ---
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }

        // Initial UI update on page load (before simulation starts)
        // Ensure initial total daily quota is calculated and displayed
        numberOfHouses = parseInt(numHousesInput.value) || 20;
        dailyQuotaPerHouse = parseInt(dailyQuotaInput.value) || 7000;
        totalDailyQuotaStreet = numberOfHouses * dailyQuotaPerHouse;
        updateUI(simulatedMinutes, 0, 0, 0, battery, 0, 0, 'Stopped', 'N/A', 0);
        totalDailyQuotaSpan.textContent = `${totalDailyQuotaStreet}`; // Ensure this is set initially
    </script>
</body>

</html>
