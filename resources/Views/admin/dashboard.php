<?php
// dashboard.php
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
            display: block;
            opacity: 0.5;
            font-size: 24px;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 w-64 bg-blue-800 text-white p-4 z-50">
            <h2 class="text-2xl font-bold mb-6">Admin Panel</h2>
            <nav class="space-y-4">
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Dashboard</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Manage Users</a>
                <a href="/smartEnergy/admin/viewPowerStats" class="block hover:bg-blue-700 p-2 rounded">View Power Stats</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Simulate Weather</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Reports</a>
                <a href="#" class="block hover:bg-blue-700 p-2 rounded">Settings</a>
            </nav>
        </aside>

        <!-- Toggle Button -->
        <button onclick="toggleSidebar()" class="fixed top-4 left-4 md:hidden z-50 bg-blue-800 text-white p-2 rounded shadow">☰ Menu</button>

        <!-- Main Content -->
        <main class="flex-1 p-6 ml-0 md:ml-64 transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Smart Energy Admin Dashboard</h1>
                <button id="simButton" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Start Simulation</button>
            </div>

            <!-- SVGs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Solar Panel -->
                <div class="bg-white shadow-lg rounded p-4 text-center">
                    <h2 class="text-xl font-semibold mb-4">Solar Panel</h2>
                    <p id="bolt" class="bolt relative top-20 ml-20">⚡</p>
                    <svg viewBox="0 0 200 150" class="mx-auto h-40 relative bottom-2">
                        <circle id="sun" cx="160" cy="30" r="20" fill="yellow" class="shine" />
                        <rect x="30" y="100" width="140" height="30" fill="#4B5563" />
                        <line x1="40" y1="100" x2="40" y2="130" stroke="white" />
                        <line x1="60" y1="100" x2="60" y2="130" stroke="white" />
                        <line x1="80" y1="100" x2="80" y2="130" stroke="white" />
                    </svg>
                </div>

                <!-- Wind Turbine -->
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

            <!-- Live Stats -->
            <div class="mt-8 bg-white shadow p-6 rounded">
                <h2 class="text-xl font-bold mb-4">Live Stats</h2>
                <div id="stats" class="space-y-2">
                    <p>Battery Level: <span id="batteryLevel">0</span> Wh</p>
                    <p>Solar Output: <span id="solarOutput">0</span> Wh</p>
                    <p>Wind Output: <span id="windOutput">0</span> Wh</p>
                    <p>Consumption: <span id="consumption">0</span> Wh</p>
                    <p>Condition: <span id="weatherCondition">N/A</span></p>
                </div>
            </div>
        </main>
    </div>

    <!-- Simulation Script -->
    <script>
        const apiKey = "091e69af47ec41acdbb6e9138757b482";
        const city = "Lagos";

        let battery = 500;
        const batteryCapacity = 1000;
        let intervalId = null;

        const simButton = document.getElementById("simButton");
        const turbine = document.getElementById("turbine");
        const sun = document.getElementById("sun");
        const bolt = document.getElementById("bolt");

        const solarOutput = document.getElementById("solarOutput");
        const windOutput = document.getElementById("windOutput");
        const consumptionOutput = document.getElementById("consumption");
        const batteryLevel = document.getElementById("batteryLevel");
        const weatherCondition = document.getElementById("weatherCondition");

        async function fetchWeather() {
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
                const data = await response.json();
                const cloudiness = data.clouds.all;
                const windSpeed = data.wind.speed;
                const condition = data.weather[0].main;

                const solar = Math.max(0, 400 - cloudiness * 3);
                const wind = Math.floor(windSpeed * 100);

                return {
                    solar,
                    wind,
                    condition
                };
            } catch (error) {
                console.error("Weather API error:", error);
                return {
                    solar: Math.floor(Math.random() * 300 + 100),
                    wind: Math.floor(Math.random() * 200 + 100),
                    condition: "Unavailable"
                };
            }
        }

        function updateUI(solar, wind, consumption, batteryLevelValue, condition) {
            solarOutput.textContent = `${solar}`;
            windOutput.textContent = `${wind}`;
            consumptionOutput.textContent = `${consumption}`;
            batteryLevel.textContent = `${batteryLevelValue} / ${batteryCapacity}`;
            weatherCondition.textContent = condition;

            sun.style.opacity = solar > 200 ? 1 : 0.4;
            turbine.style.animation = wind > 150 ? "spin 1s linear infinite" : "spin 3s linear infinite";
            bolt.style.opacity = consumption > 500 ? 1 : 0.5;
        }

        function toggleSimulation() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
                simButton.textContent = "Start Simulation";
                turbine.style.animation = "none";
            } else {
                intervalId = setInterval(async () => {
                    const {
                        solar,
                        wind,
                        condition
                    } = await fetchWeather();
                    const consumption = Math.floor(Math.random() * 300 + 200);

                    battery += (solar + wind - consumption);
                    battery = Math.max(0, Math.min(battery, batteryCapacity));

                    updateUI(solar, wind, consumption, battery, condition);
                }, 3000);

                simButton.textContent = "Stop Simulation";
            }
        }

        simButton.addEventListener("click", toggleSimulation);

        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }
    </script>
</body>

</html>
