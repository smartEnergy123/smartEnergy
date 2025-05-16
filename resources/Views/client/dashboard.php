<?php
if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

// In a real application, you would fetch the user's daily quota and current consumption
// from the database here. For this frontend simulation, we'll use placeholder values
// and update them via JavaScript.
$dailyQuotaWh = 7000; // Example daily quota in Wh (matches admin default)
$currentDailyConsumptionWh = 0; // Start with 0 consumption for the day

// User's unique ID (conceptual, would come from session/auth)
// This ID would be used to store/retrieve user-specific data in the backend
$userId = $_SESSION['user_data']['id'] ?? 'user_unknown';

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
    <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-40 md:translate-x-0">
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
                <h1 class="text-lg font-semibold text-gray-800">Welcome, <span class="text-green-600 font-bold">
                        <?php echo $_SESSION['user_data']['username'] ?? 'User'; ?>
                    </span></h1>
            </div>
            <div class="relative">
                <button onclick="toggleUserDropdown()">
                    <img src="https://img.icons8.com/ios-filled/28/user.png" alt="User" class="w-7 h-7" />
                </button>
                <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg hidden">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 Account</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">⚙️ Settings</a>
                    <a href="/smartEnergy/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">🚪 Logout</a>
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
                    <p class="text-gray-600">Daily Quota: <span id="userDailyQuota"><?php echo $dailyQuotaWh; ?></span> Wh</p>
                    <p id="quotaStatus" class="text-sm mt-2 font-semibold text-green-600">Within Quota</p>
                </div>

                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold text-yellow-700">Current Energy Cost</h3>
                        <span class="text-2xl">💰</span>
                    </div>
                    <p class="text-gray-600">Rate: <span id="currentCostRateDisplay" class="font-semibold">Fetching...</span></p>
                    <p class="text-sm text-gray-500 mt-2">Rate changes based on renewable availability.</p>
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Household Appliances</h2>
                <div id="applianceGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Account & Billing</h2>
                <p class="text-gray-600">Your current plan: <span class="font-semibold text-green-600">Standard Quota (Free Daily Allocation)</span></p>
                <p class="text-gray-600 mt-2">Excess consumption beyond your daily quota is charged at the current dynamic rate.</p>
                <button class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">View Subscription Plans</button>
            </div>

        </main>
    </div>

    <script>
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
        let userDailyConsumptionWh = <?php echo $currentDailyConsumptionWh; ?>; // Total consumption today in Watt-hours (Wh)
        const userDailyQuotaWh = <?php echo $dailyQuotaWh; ?>; // Daily quota in Wh

        // --- DOM Elements ---
        const applianceGrid = document.getElementById("applianceGrid");
        const userCurrentConsumptionSpan = document.getElementById("userCurrentConsumption");
        const userDailyConsumptionSpan = document.getElementById("userDailyConsumption");
        const userDailyQuotaSpan = document.getElementById("userDailyQuota");
        const quotaStatusSpan = document.getElementById("quotaStatus");
        const currentCostRateDisplaySpan = document.getElementById("currentCostRateDisplay"); // To display dynamic cost


        // --- Functions ---

        // Function to render the appliance grid
        function renderAppliances() {
            applianceGrid.innerHTML = ''; // Clear existing content
            appliances.forEach(appliance => {
                const applianceCard = document.createElement("div");
                applianceCard.className = "bg-gray-50 rounded-lg p-4 shadow flex flex-col items-center"; // Adjusted padding/layout

                applianceCard.innerHTML = `
                    <span class="text-4xl mb-2">${appliance.icon}</span> <h4 class="text-md font-semibold text-gray-800 text-center">${appliance.name}</h4>
                    <p class="text-sm text-gray-600 mb-4">${appliance.wattage} W</p>

                    <label for="toggle-${appliance.id}" class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" id="toggle-${appliance.id}" class="sr-only toggle-checkbox">
                            <div class="block bg-red-500 w-10 h-6 rounded-full toggle-label transition"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition toggle-circle"></div>
                        </div>
                        <div class="ml-3 text-gray-700 font-medium text-sm" id="status-${appliance.id}">Off</div> </label>
                `;

                // Add event listener to the checkbox
                const checkbox = applianceCard.querySelector(`#toggle-${appliance.id}`);
                checkbox.addEventListener('change', () => toggleAppliance(appliance.id));

                applianceGrid.appendChild(applianceCard);
            });
        }

        // Function to handle toggling an appliance
        function toggleAppliance(applianceId) {
            const currentState = applianceStates[applianceId];
            const newState = !currentState;
            applianceStates[applianceId] = newState; // Update internal state

            // Update UI: label color and status text
            const label = document.querySelector(`label[for="toggle-${applianceId}"] .toggle-label`);
            const statusTextSpan = document.getElementById(`status-${applianceId}`);

            if (newState) {
                label.classList.remove('bg-red-500');
                label.classList.add('bg-green-500');
                statusTextSpan.textContent = 'On';
            } else {
                label.classList.remove('bg-green-500');
                label.classList.add('bg-red-500');
                statusTextSpan.textContent = 'Off';
            }

            // Recalculate total consumption
            updateTotalConsumption();

            // TODO: In a real app, send this state change to the backend
            // fetch('/api/appliance/toggle', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ userId: '<?php echo $userId; ?>', applianceId: applianceId, state: newState })
            // });
        }

        // Function to recalculate and display total current consumption (Watts)
        function updateTotalConsumption() {
            userCurrentTotalConsumptionW = 0;
            appliances.forEach(appliance => {
                if (applianceStates[appliance.id]) {
                    userCurrentTotalConsumptionW += appliance.wattage;
                }
            });

            userCurrentConsumptionSpan.textContent = userCurrentTotalConsumptionW;

            // TODO: In a real app, this user's W consumption needs to be sent to the backend
            // to be added to the *total street consumption* for the Admin Dashboard.
            // Example: Send userId and userCurrentTotalConsumptionW to backend periodically or on change.
        }

        // Function to update daily consumption (Wh) - called periodically
        // In a real app, this might happen on the backend based on appliance run time.
        // For this frontend simulation, let's estimate consumption over time.
        function updateDailyConsumptionSimulation() {
            // Assuming the simulation step represents 1 minute of real time passing for the user
            // (This needs to be synchronized with the Admin simulation's time increment for accurate billing)
            // If Admin simulates 1 min passing every 100ms, and user updates every 1 second (1000ms)... it's complex.
            // For simplicity here, let's assume this function is called every 1 minute of simulated time
            // and adds the current W consumption * duration (1 minute = 1/60 hour)
            const consumptionInThisMinuteWh = userCurrentTotalConsumptionW * (1 / 60); // Wh = W * hours

            userDailyConsumptionWh += consumptionInThisMinuteWh;

            userDailyConsumptionSpan.textContent = Math.round(userDailyConsumptionWh); // Round for display

            // Check and update quota status
            if (userDailyConsumptionWh >= userDailyQuotaWh) {
                quotaStatusSpan.textContent = 'Quota Exceeded!';
                quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-red-600';
                // TODO: Implement logic for charging beyond quota (using the dynamic cost rate)
                // The cost calculation happens here based on the current consumption and the current cost rate.
                // This requires getting the current cost rate from the Admin simulation state (backend).
            } else {
                const remaining = userDailyQuotaWh - userDailyConsumptionWh;
                quotaStatusSpan.textContent = `Remaining Quota: ${Math.round(remaining)} Wh`;
                quotaStatusSpan.className = 'text-sm mt-2 font-semibold text-green-600';
            }

            // TODO: In a real app, save userDailyConsumptionWh to the database periodically.
        }


        // Function to fetch the current cost rate from the simulation state (Conceptual Backend Call)
        async function fetchCurrentCostRate() {
            // In a real application, this would fetch the state from your PHP backend
            // which gets it from the running admin simulation.
            // For this frontend demo, we'll just simulate receiving a rate.
            try {
                // Example: Fetching from a dummy endpoint or shared state
                // const response = await fetch('/api/simulation/costRate');
                // const data = await response.json();
                // const currentRate = data.costRate; // e.g., 'Low', 'Standard', 'High'

                // --- Simulation of fetching cost rate for demo ---
                // This part won't actually connect to the Admin side without a backend.
                // You'll need a way for Admin and Client UIs to share state (e.g., database, websockets).
                // For now, let's just show a placeholder or a random change for demonstration.
                const rates = ['Low', 'Standard', 'High'];
                const randomRate = rates[Math.floor(Math.random() * rates.length)]; // This is NOT linked to Admin simulation

                currentCostRateDisplaySpan.textContent = randomRate;
                // Apply color based on the rate
                if (randomRate === 'Low') currentCostRateDisplaySpan.className = 'font-semibold text-green-600';
                else if (randomRate === 'High') currentCostRateDisplaySpan.className = 'font-semibold text-red-600';
                else currentCostRateDisplaySpan.className = 'font-semibold text-yellow-600'; // Standard
                // --- End Simulation of fetching ---


            } catch (error) {
                console.error("Failed to fetch current cost rate:", error);
                currentCostRateDisplaySpan.textContent = 'Error';
                currentCostRateDisplaySpan.className = 'font-semibold text-gray-600';
            }
        }


        // --- Event Listeners ---
        // Sidebar toggle already exists

        // --- Initial Setup ---
        renderAppliances(); // Draw the appliance grid on page load
        updateTotalConsumption(); // Calculate initial consumption (should be 0)

        // Start a timer to update daily consumption and fetch cost rate periodically
        // This interval should ideally be synchronized with the Admin simulation step if possible
        setInterval(updateDailyConsumptionSimulation, 1000); // Update daily consumption every 1 simulated minute (assuming 1s real time = 1 min simulated time)
        setInterval(fetchCurrentCostRate, 5000); // Fetch cost rate every 5 real seconds (example)
    </script>
    <script>
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
