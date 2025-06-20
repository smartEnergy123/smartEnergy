<?php

if (!isset($_SESSION['user_state']) && $_SESSION['user_data']['user_type'] !== 'admin') {
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
            <a href="/smartEnergy/client/view-consumption-data" class="block text-gray-700 hover:text-green-600">📈 View Power Consumption</a>
            <a href="/smartEnergy/client/make-subscription" class="block text-gray-700 hover:text-green-600">💳 Make Payment</a>
            <a href="/smartEnergy/client/view-subcription-history" class="block text-gray-700 hover:text-green-600">📅 Subscription History</a>
            <a href="/smartEnergy/contact" class="block text-gray-700 hover:text-green-600">📞 Contact Support</a>
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
                <a href="/smartEnergy/client/make-subscription">
                    <button class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">View
                        Subscription Plans</button>
                </a>
            </div>

        </main>
    </div>

    <div id="quotaModalOverlay"
        class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div id="quotaModal" class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full text-center relative">
            <h3 class="text-2xl font-bold text-red-600 mb-4">Daily Quota Exceeded!</h3>
            <p class="text-gray-700 mb-6 text-lg">
                Your daily energy allocation has been used up.
                <br>
                To continue enjoying power, you can subscribe for an extra power top-up,
                or wait until tomorrow for your new daily allocation.
            </p>
            <div class="flex justify-center space-x-4">
                <a href="/smartEnergy/client/make-subscription">
                    <button id="subscribeBtn"
                        class="bg-green-500 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-green-600 transition">
                        Subscribe Now
                    </button>
                </a>
                <button id="cancelBtn"
                    class="bg-gray-300 text-gray-800 px-6 py-3 rounded-lg text-lg font-semibold hover:bg-gray-400 transition">
                    Wait Until Tomorrow
                </button>
            </div>
        </div>
    </div>

    <div id="pleaseSubscribeModalOverlay"
        class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div id="pleaseSubscribeModal" class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full text-center relative">
            <h3 class="text-2xl font-bold text-blue-600 mb-4">Welcome to SmartEnergy!</h3>
            <p class="text-gray-700 mb-6 text-lg">
                It looks like you don't have an active subscription yet.
                <br>
                Please subscribe to a plan to start enjoying your daily energy allocation and manage your appliances.
            </p>
            <div class="flex justify-center">
                <a href="/smartEnergy/client/make-subscription">
                    <button id="goToSubscriptionBtn"
                        class="bg-blue-500 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-600 transition">
                        Go to Subscription Plans
                    </button>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            console.log("DOMContentLoaded: Script execution started.");
            // --- Configuration ---
            // Define appliances with their power consumption in Watts (W)
            const appliances = [{
                    id: 'fridge',
                    name: 'Refrigerator',
                    wattage: 1500,
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

            // Flags for modal and appliance control
            let quotaExceededModalShown = false;
            let appliancesStoppedDueToQuota = false;
            let hasActiveSubscription = false; // NEW: Flag for overall subscription status
            let pleaseSubscribeModalShown = false; // NEW: Flag for "Please Subscribe" modal
            let openAppliance = false;

            // --- DOM Elements ---
            const applianceGrid = document.getElementById("applianceGrid");
            const userCurrentConsumptionSpan = document.getElementById("userCurrentConsumption");
            const userDailyConsumptionSpan = document.getElementById("userDailyConsumption");
            const userDailyQuotaSpan = document.getElementById("userDailyQuota");
            const quotaStatusSpan = document.getElementById("quotaStatus");
            const currentCostRateDisplaySpan = document.getElementById("currentCostRateDisplay"); // To display dynamic cost

            // Get modal elements
            const quotaModalOverlay = document.getElementById("quotaModalOverlay");
            const subscribeBtn = document.getElementById("subscribeBtn");
            const cancelBtn = document.getElementById("cancelBtn");

            // NEW: Get "Please Subscribe" modal elements
            const pleaseSubscribeModalOverlay = document.getElementById("pleaseSubscribeModalOverlay");
            const goToSubscriptionBtn = document.getElementById("goToSubscriptionBtn");


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
                    // Add a class to disable pointer events if no active subscription
                    if (hasActiveSubscription === false) {
                        applianceCard.className = `bg-gray-50 rounded-lg p-4 shadow flex flex-col items-center`;
                    } else {
                        applianceCard.className = `bg-gray-50 rounded-lg p-4 shadow flex flex-col items-center
                                               ${!hasActiveSubscription ? 'opacity-50 pointer-events-none' : ''}`;
                    }
                    console.log("+++++ Sub ++++", hasActiveSubscription);
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
                const checkbox = document.getElementById(`toggle-${applianceId}`); // Corrected ID

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
                // Prevent toggling if no active subscription or if quota is exceeded
                if (!hasActiveSubscription) {
                    showPleaseSubscribeModal(); // Prompt to subscribe
                    return;
                }
                if (appliancesStoppedDueToQuota) {
                    alert("Quota exceeded! Please subscribe for a top-up or wait until tomorrow to use appliances.");
                    // Ensure the UI reflects the off state if they tried to toggle it
                    updateApplianceUI(applianceId, false);
                    return;
                }

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
             * and timestamp) to the backend for logging.
             */
            async function sendConsumptionDataToBackend() {
                // Only send consumption data if there's an active subscription
                if (!hasActiveSubscription) {
                    console.log("No active subscription, skipping consumption data send.");
                    return;
                }

                const timestamp = new Date().toISOString(); // Get current timestamp in ISO 8601 format

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
             * Stops all active appliances, updates their UI, and recalculates total consumption.
             */
            async function stopAllAppliances() {
                console.log("Quota exceeded: Attempting to stop all active appliances.");
                appliancesStoppedDueToQuota = true; // Set flag
                userCurrentTotalConsumptionW = 0; // Reset consumption immediately for UI

                for (const appliance of appliances) {
                    if (applianceStates[appliance.id]) {
                        applianceStates[appliance.id] = false; // Update internal state
                        updateApplianceUI(appliance.id, false); // Update UI to turn off toggle

                        // Send toggle off request to backend for persistence
                        try {
                            await fetch('/smartEnergy/api/appliance/toggle', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    userId: userId,
                                    applianceId: appliance.id,
                                    state: false
                                })
                            });
                        } catch (error) {
                            console.error(`Error sending toggle off for ${appliance.id}:`, error);
                        }
                    }
                }
                userCurrentConsumptionSpan.textContent = userCurrentTotalConsumptionW; // Update dashboard display
                // hasActiveSubscription = false;
                console.log("All appliances stopped. Total consumption is now:", userCurrentTotalConsumptionW, "W");
            }

            /**
             * Displays the quota exceeded modal.
             */
            function showQuotaModal() {
                if (!quotaExceededModalShown) {
                    quotaModalOverlay.classList.remove('hidden');
                    quotaExceededModalShown = true;
                    console.log("Quota Exceeded Modal Shown.");
                }
            }

            /**
             * Hides the quota exceeded modal.
             */
            function hideQuotaModal() {
                // quotaModalOverlay.classList.add('hidden');
                quotaModalOverlay.style.display = 'none';
                quotaExceededModalShown = false;
                hasActiveSubscription = false;
                console.log("Quota Exceeded Modal Hidden.");
            }

            /**
             * NEW: Displays the "Please Subscribe" modal.
             */
            function showPleaseSubscribeModal() {
                if (!pleaseSubscribeModalShown) {
                    pleaseSubscribeModalOverlay.classList.remove('hidden');
                    pleaseSubscribeModalShown = true;
                    console.log("Please Subscribe Modal Shown.");
                }
            }

            /**
             * NEW: Hides the "Please Subscribe" modal.
             */
            function hidePleaseSubscribeModal() {
                pleaseSubscribeModalOverlay.classList.add('hidden');
                pleaseSubscribeModalShown = false;
                console.log("Please Subscribe Modal Hidden.");
            }


            /**
             * Updates the client-side display of daily consumption and checks against the quota.
             * This function simulates accumulation for display purposes;
             */

            function updateDailyConsumptionSimulation() {
                // Only accumulate if there's an active subscription AND quota is NOT exceeded
                if (hasActiveSubscription && (!appliancesStoppedDueToQuota || userDailyConsumptionWh < userDailyQuotaWh)) {
                    const consumptionInThisMinuteWh = userCurrentTotalConsumptionW * (1 / 60); // Wh = W * (hours)
                    userDailyConsumptionWh += consumptionInThisMinuteWh;
                }

                userDailyConsumptionSpan.textContent = Math.round(userDailyConsumptionWh); // Round for display

                // Check and update quota status display
                if (hasActiveSubscription) { // Only check quota if there's an active subscription
                    if (userDailyQuotaWh > 0 && userDailyConsumptionWh >= userDailyQuotaWh) {
                        quotaStatusSpan.textContent = `Quota Exceeded! (${Math.round(userDailyConsumptionWh - userDailyQuotaWh)} Wh over)`;
                        quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';

                        if (!appliancesStoppedDueToQuota) {
                            stopAllAppliances(); // Stop all appliances
                        }
                        showQuotaModal(); // Show the modal


                        // --- NEW CODE STARTS HERE ---
                        const currentUserIdMeta = userId;
                        const currentUserId = currentUserIdMeta ? currentUserIdMeta : null;

                        if (currentUserId) {
                            // API call to update daily_quota_wh to 0 in client_profiles table
                            fetch('/smartEnergy/api/updateUserDailyQuota', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        user_id: currentUserId,
                                        new_quota_value: 0 // Set quota to 0 to mark as used up for the day
                                    }),
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        console.log('Daily quota updated on server to 0:', data.message);
                                        // Optionally, update the client-side userDailyQuotaWh to 0 immediately
                                        userDailyQuotaWh = 0; // This ensures the client-side also reflects the server state
                                    } else {
                                        console.error('Failed to update daily quota on server:', data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error sending daily quota update request:', error);
                                });
                        } else {
                            console.error('User ID not found for quota update.');
                        }
                        // --- NEW CODE ENDS HERE ---

                    } else {
                        const remaining = userDailyQuotaWh - userDailyConsumptionWh;
                        quotaStatusSpan.textContent = `Remaining Quota: ${Math.round(remaining)} Wh`;
                        quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-green-600';
                        // If quota was exceeded but now is not (e.g., after a top-up), hide modal
                        if (quotaExceededModalShown) {
                            hideQuotaModal();
                            // If a top-up occurred, you'd also need to re-enable appliance toggling
                            appliancesStoppedDueToQuota = false; // Allow toggling again
                            // renderAppliances(); // This might be too heavy, just ensure pointer-events are reset
                        }

                    }
                } else { // No active subscription
                    quotaStatusSpan.textContent = 'No active subscription.';
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-gray-600';
                    userDailyQuotaSpan.textContent = 'N/A';
                    userDailyConsumptionSpan.textContent = 'N/A';
                    // Ensure quota exceeded modal is hidden if no subscription
                    hideQuotaModal();
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
                    const currentRate = data.data.costRate; // e.g., 'Low', 'Standard', 'High'

                    console.log("++++Current Rate++++", currentRate);
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
                    const result = await response.json();
                    console.log("================RESULT FOR THE DASHBOARD============= ", result.data);

                    if (result.status === 'success' && result.data) {
                        const data = result.data;

                        // CORRECTED: Determine hasActiveSubscription based on dailyQuotaWh > 0,
                        // as the backend provides dailyQuotaWh, not a separate hasActiveSubscription flag.
                        hasActiveSubscription = data.dailyQuotaWh > 0; // Updated logic

                        if (!hasActiveSubscription) {
                            console.log("No active subscription detected. Showing subscribe modal.");
                            userDailyQuotaWh = 0;
                            userDailyConsumptionWh = 0;
                            userCurrentTotalConsumptionW = 0; // Ensure current consumption is 0
                            // Reset all appliance states to off if no subscription
                            appliances.forEach(appliance => {
                                applianceStates[appliance.id] = false;
                            });
                            updateTotalConsumption(); // Update UI for 0W
                            renderAppliances(); // Re-render to apply opacity/pointer-events-none
                            showPleaseSubscribeModal();
                            // Also ensure quota exceeded modal is hidden
                            hideQuotaModal();
                            appliancesStoppedDueToQuota = true; // Effectively stop appliances if no subscription
                        } else {
                            hidePleaseSubscribeModal(); // Hide subscribe modal if active subscription found
                            appliancesStoppedDueToQuota = false; // Allow toggling again
                            renderAppliances(); // Re-render to remove opacity/pointer-events-none

                            // Update daily quota and consumption
                            userDailyQuotaWh = data.dailyQuotaWh;
                            userDailyConsumptionWh = data.currentDailyConsumptionWh;
                            userDailyQuotaSpan.textContent = userDailyQuotaWh;
                            userDailyConsumptionSpan.textContent = Math.round(userDailyConsumptionWh);
                            updateDailyConsumptionSimulation(); // Call to update quota status display

                            // Update appliance states based on fetched data
                            // Ensure data.applianceStates is an array before iterating
                            if (Array.isArray(data.applianceStates)) {
                                data.applianceStates.forEach(appliance => {
                                    if (applianceStates.hasOwnProperty(appliance.id)) {
                                        // Ensure state is boolean
                                        applianceStates[appliance.id] = Boolean(appliance.state);
                                        updateApplianceUI(appliance.id, Boolean(appliance.state));
                                    }
                                });
                            }
                            updateTotalConsumption(); // Update total consumption based on fetched appliance states
                        }
                    } else {
                        console.error("Backend reported an error or missing data:", result.message);
                        userDailyQuotaSpan.textContent = 'Error';
                        userDailyConsumptionSpan.textContent = 'Error';
                        quotaStatusSpan.textContent = 'Failed to load data.';
                        quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                        // If backend reports error, assume no subscription or problem
                        showPleaseSubscribeModal();
                        appliancesStoppedDueToQuota = true;
                    }

                } catch (error) {
                    console.error("Failed to fetch dashboard data:", error);
                    userDailyQuotaSpan.textContent = 'Error';
                    userDailyConsumptionSpan.textContent = 'Error';
                    quotaStatusSpan.textContent = 'Failed to load data.';
                    quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                    // If network error, also assume no subscription or problem
                    showPleaseSubscribeModal();
                    appliancesStoppedDueToQuota = true;
                }
            }


            // --- Initial Setup ---
            console.log("DOMContentLoaded: renderAppliances called.");
            // Initial render might be without full subscription status, will be updated by fetchDashboardData
            renderAppliances();

            console.log("DOMContentLoaded: fetchDashboardData called.");
            fetchDashboardData(); // Fetch initial user data from backend

            console.log("DOMContentLoaded: updateTotalConsumption called.");
            updateTotalConsumption(); // Calculate initial consumption (should be 0 or based on fetched states)

            // Start timers
            console.log("DOMContentLoaded: Setting up intervals.");
            setInterval(updateDailyConsumptionSimulation, 1000); // Simulate daily consumption update every 1 second
            setInterval(sendConsumptionDataToBackend, 60 * 1000); // Send consumption data to backend every 60 seconds
            setInterval(fetchCurrentCostRate, 5000); // Fetch cost rate every 5 seconds

            // Event listeners for Quota Exceeded Modal buttons
            subscribeBtn.addEventListener('click', () => {
                console.log("Subscribe button clicked (from Quota Exceeded Modal)!");
                // TODO: Implement actual subscription/top-up logic and redirect
                alert("Redirecting to subscription page for top-up (not yet implemented).");
                hideQuotaModal();
                // After successful top-up, you would need to:
                // 1. Make a backend call to update user's quota/balance.
                // 2. Re-fetch dashboard data to update client-side `userDailyQuotaWh` and `userDailyConsumptionWh`.
                // 3. Set `appliancesStoppedDueToQuota = false;` to re-enable appliance toggling.
            });

            cancelBtn.addEventListener('click', () => {
                console.log("Cancel button clicked (from Quota Exceeded Modal)!");
                alert("You have chosen to wait until tomorrow for your new daily allocation. Appliances remain off.");
                hideQuotaModal();
            });

            // NEW: Event listener for "Go to Subscription Plans" button
            goToSubscriptionBtn.addEventListener('click', () => {
                console.log("Go to Subscription Plans button clicked!");
                alert("Redirecting to subscription plans page");
                hidePleaseSubscribeModal();
            });
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
