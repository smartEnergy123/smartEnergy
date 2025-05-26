<?php

if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// These values will now be fetched by JavaScript from the API
// Keeping them here as placeholders for initial render, but JavaScript will overwrite them.
$dailyQuotaWh = 0; // Will be fetched from API
$currentDailyConsumptionWh = 0; // Will be fetched from API

// User's unique ID (conceptual, would come from session/auth)
// This ID would be used to store/retrieve user-specific data in the backend
$userId = $_SESSION['user_data']['id'] ?? 'user_unknown';
$username = $_SESSION['user_data']['username'] ?? 'User';
echo 'User Id = ' . $userId;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SmartEnergy Client Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom toggle switch styling */
        .toggle-checkbox:checked+.toggle-label {
            background-color: #10B981;
            /* Green-500 */
        }

        .toggle-checkbox:checked+.toggle-label .toggle-circle {
            transform: translateX(1.5rem);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div id="sidebar"
        class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-40 md:translate-x-0">
        <div class="flex justify-between items-center p-8 border-b">
            <h2 class="text-xl font-bold text-green-600">SmartEnergy</h2>
            <button class="md:hidden text-gray-600" onclick="toggleSidebar()">✖</button>
        </div>
        <nav class="p-4 space-y-4">
            <a href="#" class="block text-gray-700 hover:text-green-600">📈 View Power Consumption</a>
            <a href="#" class="block text-gray-700 hover:text-green-600">💳 Make Payment</a>
            <a href="#" class="block text-gray-700 hover:text-green-600">📃 View Payment Plans</a>
            <a href="#" class="block text-gray-700 hover:text-green-600">📊 View Data</a>
            <a href="#" class="block text-gray-700 hover:text-green-600">📅 Subscription History</a>
            <a href="#" class="block text-gray-700 hover:text-green-600">📞 Contact Support</a>
        </nav>
    </div>

    <div class="md:ml-64 transition-all">
        <header class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button class="text-green-600 md:hidden" onclick="toggleSidebar()">
                    <img src="https://img.icons8.com/material-rounded/24/menu--v1.png" alt="user" />
                </button>
                <h1 class="text-lg font-semibold text-gray-800">Welcome, <span
                        class="text-green-600 font-bold">
                        <?php echo $username; ?>
                    </span></h1>
            </div>
            <div class="relative">
                <button onclick="toggleUserDropdown()">
                    <img src="https://img.icons8.com/ios-filled/28/user.png" alt="User" class="w-7 h-7" />
                </button>
                <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg hidden">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 Account</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">⚙️ Settings</a>
                    <a href="/smartEnergy/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">🚪
                        Logout</a>
                </div>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold text-green-700">Current Total Consumption</h3>
                        <span class="text-2xl">⚡</span>
                    </div>
                    <p class="text-gray-600"><span id="userCurrentConsumption">0</span> Wh (Per Minute)</p>
                </div>

                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold text-blue-700">Daily Quota Status</h3>
                        <span class="text-2xl">📊</span>
                    </div>
                    <p class="text-gray-600">Consumed Today: <span id="userDailyConsumption">0</span> Wh</p>
                    <p class="text-gray-600">Daily Quota: <span id="userDailyQuota">0</span> Wh</p>
                    <p id="quotaStatus" class="text-sm mt-2 font-semibold text-green-600">Fetching Quota...</p>
                </div>

                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold text-yellow-700">Current Energy Cost</h3>
                        <span class="text-2xl">💰</span>
                    </div>
                    <p class="text-gray-600">Rate: <span id="currentCostRateDisplay"
                            class="font-semibold">Fetching...</span></p>
                    <p class="text-sm text-gray-500 mt-2">Rate changes based on renewable availability.</p>
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Household Appliances</h2>
                <div id="applianceGrid"
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Account & Billing</h2>
                <p class="text-gray-600">Your current plan: <span class="font-semibold text-green-600">Standard Quota
                        (Free Daily Allocation)</span></p>
                <p class="text-gray-600 mt-2">Excess consumption beyond your daily quota is charged at the current
                    dynamic rate.</p>
                <button class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">View
                    Subscription Plans</button>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            console.log("DOMContentLoaded: Script execution started.");
            // --- Configuration ---
            // Define appliances with their power consumption in Watts (W)
            const appliances = [{
                    id: 'fridge',
                    name: 'Refrigerator',
                    wattage: 150,
                    icon: '🧊'
                }, // Running wattage (compressor cycles) - average
                {
                    id: 'microwave',
                    name: 'Microwave Oven',
                    wattage: 1000,
                    icon: '♨️'
                }, // High wattage when in use
                {
                    id: 'oven',
                    name: 'Electric Oven',
                    wattage: 2000,
                    icon: '🔥'
                }, // High wattage when in use
                {
                    id: 'stove',
                    name: 'Stove Burner',
                    wattage: 1200,
                    icon: '🍳'
                }, // Per burner (example)
                {
                    id: 'dishwasher',
                    name: 'Dishwasher',
                    wattage: 1800,
                    icon: '🧼'
                }, // Peak wattage during heating cycle
                {
                    id: 'washing-machine',
                    name: 'Washing Machine',
                    wattage: 500,
                    icon: '🧺'
                }, // Varies during cycle
                {
                    id: 'dryer',
                    name: 'Clothes Dryer',
                    wattage: 3000,
                    icon: '💨'
                }, // High wattage
                {
                    id: 'tv',
                    name: 'Television',
                    wattage: 100,
                    icon: '📺'
                }, // Varies by size/type
                {
                    id: 'console',
                    name: 'Gaming Console',
                    wattage: 150,
                    icon: '🎮'
                },
                {
                    id: 'desktop',
                    name: 'Desktop Computer',
                    wattage: 200,
                    icon: '🖥️'
                }, // Varies by components
                {
                    id: 'lights',
                    name: 'Lights (Avg)',
                    wattage: 60,
                    icon: '💡'
                }, // Average for a few lights
                {
                    id: 'toaster',
                    name: 'Toaster',
                    wattage: 1000,
                    icon: '🍞'
                },
                {
                    id: 'kettle',
                    name: 'Electric Kettle',
                    wattage: 1500,
                    icon: '☕'
                },
                {
                    id: 'vacuum',
                    name: 'Vacuum Cleaner',
                    wattage: 1000,
                    icon: '🧹'
                },
                { // Added from original brief
                    id: 'fan',
                    name: 'Fan',
                    wattage: 50,
                    icon: '🍃'
                },
                { // Added from original brief
                    id: 'ac',
                    name: 'Air Conditioner',
                    wattage: 1500, // Window/portable unit example
                    icon: '❄️'
                }
                // Added more than 12 to give you options
            ];

            // --- State Variables ---
            // Object to store the on/off state of each appliance (true for on, false for off)
            const applianceStates = {};
            appliances.forEach(appliance => {
                applianceStates[appliance.id] = false; // All start off
            });

            let userCurrentTotalConsumptionW = 0; // Total consumption in Watts (W)
            let userDailyConsumptionWh = 0; // Total consumption today in Watt-hours (Wh) - will be fetched
            let userDailyQuotaWh = 0; // Daily quota in Wh - will be fetched

            // --- DOM Elements ---
            const applianceGrid = document.getElementById("applianceGrid");
            const userCurrentConsumptionSpan = document.getElementById("userCurrentConsumption");
            const userDailyConsumptionSpan = document.getElementById("userDailyConsumption");
            const userDailyQuotaSpan = document.getElementById("userDailyQuota");
            const quotaStatusSpan = document.getElementById("quotaStatus");
            const currentCostRateDisplaySpan = document.getElementById("currentCostRateDisplay"); // To display dynamic cost

            // The userId is passed from PHP, ensuring it's available in JS
            const userId = "<?php echo $userId; ?>";

            // --- Functions ---

            /**
             * Renders the appliance grid dynamically based on the 'appliances' array.
             * Each appliance gets a card with its icon, name, wattage, and a toggle switch.
             */
            function renderAppliances() {
                console.log("renderAppliances called.");
                if (!applianceGrid) {
                    console.error("Appliance grid element not found in renderAppliances!");
                    return; // Exit if element not found
                }
                applianceGrid.innerHTML = ''; // Clear existing content
                let renderedCount = 0;
                appliances.forEach(appliance => {
                    const applianceCard = document.createElement("div");
                    applianceCard.className = "bg-gray-50 rounded-lg p-4 shadow flex flex-col items-center"; // Adjusted padding/layout

                    applianceCard.innerHTML = `
                           <span class="text-4xl mb-2">${appliance.icon}</span>
                           <h4 class="text-md font-semibold text-gray-800 text-center">${appliance.name}</h4>
                           <p class="text-sm text-gray-600 mb-4">${appliance.wattage} W</p>

                           <label for="toggle-${appliance.id}" class="flex items-center cursor-pointer">
                               <div class="relative">
                                   <input type="checkbox" id="toggle-${appliance.id}" class="sr-only toggle-checkbox">
                                   <div class="block bg-red-500 w-10 h-6 rounded-full toggle-label transition"></div>
                                   <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition toggle-circle"></div>
                               </div>
                               <div class="ml-3 text-gray-700 font-medium text-sm" id="status-${appliance.id}">Off</div>
                           </label>
                           `;

                    // Add event listener to the checkbox
                    const checkbox = applianceCard.querySelector(`#toggle-${appliance.id}`);
                    if (checkbox) {
                        checkbox.addEventListener('change', () => toggleAppliance(appliance.id));
                    } else {
                        console.error(`Checkbox not found for appliance: ${appliance.id}`);
                    }

                    applianceGrid.appendChild(applianceCard);
                    renderedCount++;
                });
                console.log(`Finished rendering appliances. Rendered ${renderedCount} cards.`);
            }

            /**
             * Updates the UI for a specific appliance based on its new state.
             * @param {string} applianceId - The ID of the appliance.
             * @param {boolean} newState - The new state (true for on, false for off).
             */
            function updateApplianceUI(applianceId, newState) {
                const label = document.querySelector(`label[for="toggle-${applianceId}"] .toggle-label`);
                const statusTextSpan = document.getElementById(`status-${applianceId}`);
                const checkbox = document.getElementById(`toggle-${applianceId}`);

                if (checkbox) { // Ensure checkbox exists before trying to update it
                    checkbox.checked = newState; // Ensure checkbox reflects the state
                }

                if (label && statusTextSpan) { // Ensure elements exist
                    if (newState) {
                        label.classList.remove('bg-red-500');
                        label.classList.add('bg-green-500');
                        statusTextSpan.textContent = 'On';
                    } else {
                        label.classList.remove('bg-green-500');
                        label.classList.add('bg-red-500');
                        statusTextSpan.textContent = 'Off';
                    }
                }
            }

            /**
             * Handles toggling an appliance's state, updates UI, recalculates total consumption,
             * and sends the state change to the backend.
             * @param {string} applianceId - The ID of the appliance to toggle.
             */
            async function toggleAppliance(applianceId) {
                const currentState = applianceStates[applianceId];
                const newState = !currentState;
                applianceStates[applianceId] = newState; // Optimistically update internal state

                updateApplianceUI(applianceId, newState); // Update UI immediately for responsiveness
                updateTotalConsumption(); // Recalculate total consumption locally

                // Send state change to the backend via API
                try {
                    const response = await fetch('/smartEnergy/api/appliance/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            userId: userId,
                            applianceId: applianceId,
                            state: newState
                        })
                    });

                    if (!response.ok) {
                        // If API call fails, revert UI and internal state
                        applianceStates[applianceId] = currentState;
                        updateApplianceUI(applianceId, currentState);
                        updateTotalConsumption(); // Recalculate again to revert
                        console.error(`Failed to toggle appliance ${applianceId}. Status: ${response.status}`);
                        const errorData = await response.json().catch(() => ({
                            message: 'No JSON response or malformed JSON'
                        }));
                        console.error("Server error message:", errorData.message);
                        // Optionally show an error message to the user (e.g., using a custom modal)
                    }
                    // If successful, no further action needed as state is already updated optimistically

                } catch (error) {
                    applianceStates[applianceId] = currentState;
                    updateApplianceUI(applianceId, currentState);
                    updateTotalConsumption(); // Recalculate again to revert
                    console.error("Error sending appliance toggle to backend:", error);
                    // Optionally show an error message to the user
                }
            }

            /**
             * Recalculates the total current consumption (in Watts) based on active appliances
             * and updates the display. This function does NOT send data to the backend directly.
             */
            function updateTotalConsumption() {
                userCurrentTotalConsumptionW = 0;
                appliances.forEach(appliance => {
                    if (applianceStates[appliance.id]) {
                        userCurrentTotalConsumptionW += appliance.wattage;
                    }
                });
                userCurrentConsumptionSpan.textContent = userCurrentTotalConsumptionW;
            }

            /**
             * Sends the current consumption data (instantaneous Watts, accumulated daily Watt-hours,
             * and timestamp) to the backend for logging. This is the function that resolves the 400 error.
             */
            async function sendConsumptionDataToBackend() {
                const timestamp = new Date(); // Get current timestamp in ISO 8601 format

                try {
                    const response = await fetch('/smartEnergy/api/consumption/current', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            userId: userId,
                            currentConsumptionW: userCurrentTotalConsumptionW, // Instantaneous W
                            dailyConsumptionWh: Math.round(userDailyConsumptionWh), // Accumulated daily Wh
                            timestamp: timestamp
                        })
                    });

                    if (!response.ok) {
                        console.error(`Failed to send consumption data. Status: ${response.status}`);
                        const errorData = await response.json().catch(() => ({
                            message: 'No JSON response or malformed JSON'
                        }));
                        console.error("Server error message:", errorData.message);
                    } else {
                        console.log("Consumption data sent successfully at", timestamp);
                    }
                } catch (error) {
                    console.error("Error sending consumption data to backend:", error);
                }
            }


            /**
             * Updates the client-side display of daily consumption and checks against the quota.
             * This function simulates accumulation for display purposes; actual accumulation
             * should be handled persistently on the backend.
             */
            function updateDailyConsumptionSimulation() {
                // Calculate consumption for the current simulated minute (1 real second)
                const consumptionInThisMinuteWh = userCurrentTotalConsumptionW * (1 / 60); // Wh = W * (hours)
                userDailyConsumptionWh += consumptionInThisMinuteWh;

                userDailyConsumptionSpan.textContent = Math.round(userDailyConsumptionWh); // Round for display

                // Check and update quota status display
                if (userDailyQuotaWh > 0 && userDailyConsumptionWh >= userDailyQuotaWh) {
                    quotaStatusSpan.textContent = `Quota Exceeded! (${Math.round(userDailyConsumptionWh - userDailyQuotaWh)} Wh over)`;
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                } else if (userDailyQuotaWh > 0) {
                    const remaining = userDailyQuotaWh - userDailyConsumptionWh;
                    quotaStatusSpan.textContent = `Remaining Quota: ${Math.round(remaining)} Wh`;
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-green-600';
                } else {
                    quotaStatusSpan.textContent = 'Quota data not available.';
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-gray-600';
                }
            }


            /**
             * Fetches the current energy cost rate from the simulation state backend API.
             * Updates the display with the fetched rate and applies color coding.
             */
            async function fetchCurrentCostRate() {
                try {
                    const response = await fetch('/smartEnergy/api/simulation/costRate');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    const currentRate = data.costRate; // e.g., 'Low', 'Standard', 'High'

                    currentCostRateDisplaySpan.textContent = currentRate;
                    // Apply color based on the rate
                    if (currentRate === 'Low') currentCostRateDisplaySpan.className = 'font-semibold text-green-600';
                    else if (currentRate === 'High') currentCostRateDisplaySpan.className = 'font-semibold text-red-600';
                    else currentCostRateDisplaySpan.className = 'font-semibold text-yellow-600'; // Standard

                } catch (error) {
                    console.error("Failed to fetch current cost rate:", error);
                    currentCostRateDisplaySpan.textContent = 'Error';
                    currentCostRateDisplaySpan.className = 'font-semibold text-gray-600';
                }
            }

            /**
             * Fetches initial user dashboard data (daily quota, current daily consumption,
             * and appliance states) from the backend API.
             */
            async function fetchDashboardData() {
                try {
                    const response = await fetch(`/smartEnergy/api/user/dashboard-data?userId=${userId}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json(); // Use 'result' to avoid clash with local 'data'

                    if (result.status === 'success' && result.data) {
                        const data = result.data; // Now, 'data' holds the actual payload

                        // Update daily quota and consumption
                        userDailyQuotaWh = data.dailyQuotaWh;
                        userDailyConsumptionWh = data.currentDailyConsumptionWh;
                        userDailyQuotaSpan.textContent = userDailyQuotaWh;
                        userDailyConsumptionSpan.textContent = Math.round(userDailyConsumptionWh);
                        updateDailyConsumptionSimulation(); // Call to update quota status display

                        // Update appliance states based on fetched data
                        data.applianceStates.forEach(appliance => {
                            if (applianceStates.hasOwnProperty(appliance.id)) {
                                applianceStates[appliance.id] = appliance.state;
                                updateApplianceUI(appliance.id, appliance.state);
                            }
                        });
                        updateTotalConsumption(); // Update total consumption based on fetched appliance states
                    } else {
                        console.error("Backend reported an error or missing data:", result.message);
                        userDailyQuotaSpan.textContent = 'Error';
                        userDailyConsumptionSpan.textContent = 'Error';
                        quotaStatusSpan.textContent = 'Failed to load data.';
                        quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                    }

                } catch (error) {
                    console.error("Failed to fetch dashboard data:", error);
                    userDailyQuotaSpan.textContent = 'Error';
                    userDailyConsumptionSpan.textContent = 'Error';
                    quotaStatusSpan.textContent = 'Failed to load data.';
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                }
            }


            // --- Initial Setup ---
            console.log("DOMContentLoaded: renderAppliances called.");
            renderAppliances(); // Draw the appliance grid on page load

            console.log("DOMContentLoaded: fetchDashboardData called.");
            fetchDashboardData(); // Fetch initial user data from backend

            console.log("DOMContentLoaded: updateTotalConsumption called.");
            updateTotalConsumption(); // Calculate initial consumption (should be 0 or based on fetched states)

            // Start timers
            console.log("DOMContentLoaded: Setting up intervals.");
            // This interval handles the client-side display of daily consumption and quota.
            setInterval(updateDailyConsumptionSimulation, 1000); // Simulate daily consumption update every 1 second (e.g., representing 1 simulated minute)

            // This interval sends the current and daily consumption data to the backend.
            // Adjust the interval as needed for how frequently you want to log data.
            // For a "per minute" log, 60 * 1000 ms (60 seconds) is appropriate.
            setInterval(sendConsumptionDataToBackend, 60 * 1000); // Send consumption data to backend every 60 seconds

            setInterval(fetchCurrentCostRate, 5000); // Fetch cost rate every 5 seconds (example)
        });

        // Keep existing sidebar and user dropdown toggles
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }

        function toggleUserDropdown() {
            document.getElementById("userDropdown").classList.toggle("hidden");
        }
    </script>
</body>

</html>
