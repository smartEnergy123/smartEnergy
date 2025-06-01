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
const batteryReserveThreshold = 8000; // Wh (this constant isn't currently used in runSimulationStep logic, but kept for completeness)
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
    return 'may1_refined'; // Default fallback
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
// --- admin_simulation.js code ends here ---


// --- UI Element References and Simulation State Variables ---
const liveSolarOutputSpan = document.getElementById("liveSolarOutput");
const liveWindOutputSpan = document.getElementById("liveWindOutput");
const currentTotalConsumptionSpan = document.getElementById("currentTotalConsumption");
const batteryLevelSpan = document.getElementById("batteryLevel");
const batteryIcon = document.getElementById("batteryIcon");
const batteryStatusSpan = document.getElementById("batteryStatus");
const gridStatusSpan = document.getElementById("gridStatus");
const gridImportExportSpan = document.getElementById("gridImportExport");
const simulatedTimeSpan = document.getElementById("simulatedTime");
const currentWeatherSpan = document.getElementById("currentWeather");

const totalSolarGeneratedSpan = document.getElementById("totalSolarGenerated");
const totalWindGeneratedSpan = document.getElementById("totalWindGenerated");
const totalConsumptionSpan = document.getElementById("totalConsumption");
const totalGridImportSpan = document.getElementById("totalGridImport");
const totalGridExportSpan = document.getElementById("totalGridExport");
const co2EmissionsSpan = document.getElementById("co2Emissions");
const currentEnergyCostSpan = document.getElementById("currentEnergyCost");

const summarySolarGeneratedSpan = document.getElementById("summarySolarGenerated");
const summaryWindGeneratedSpan = document.getElementById("summaryWindGenerated");
const summaryTotalConsumptionSpan = document.getElementById("summaryTotalConsumption");
const summaryGridImportSpan = document.getElementById("summaryGridImport");
const summaryGridExportSpan = document.getElementById("summaryGridExport");
const summaryCo2EmissionsSpan = document.getElementById("summaryCo2Emissions");

const startStopSimulationBtn = document.getElementById("startStopSimulationBtn");
const numHousesInput = document.getElementById("numHouses");
const dailyQuotaInput = document.getElementById("dailyQuota");
const costRateInput = document.getElementById("costRate");
const solarProfileSelect = document.getElementById("solarProfileSelect");
const updateConfigBtn = document.getElementById("updateConfigBtn");
const dailyQuotaRemaining = document.getElementById("dailyQuotaRemaining");
const currentDailyConsumption = document.getElementById("currentDailyConsumption");
const currentCostRate = document.getElementById("currentCostRate");


let simulationRunning = false;
let simulationIntervalId = null;
let autoSyncIntervalId = null;
let summaryUpdateIntervalId = null;

let currentSimulationTime = 0; // In minutes from 00:00
let lastWeatherData = {
    condition: 'Clear',
    wind: 10,
    clouds: 0,
    temperature: 20
}; // Default weather
let selectedSolarProfile = dayProfilesWh.may5_table; // Default profile

let simulationConfig = {
    numHouses: parseInt(numHousesInput.value),
    dailyQuota: parseInt(dailyQuotaInput.value),
    costRate: parseFloat(costRateInput.value)
};

let currentBatteryLevel = batteryCapacity / 2; // Start at 50%
let dailyCumulativeConsumption = 0; // Wh for daily quota calculation

// Totals for current simulation session (resets on page reload)
let sessionTotalSolarGenerated = 0;
let sessionTotalWindGenerated = 0;
let sessionTotalConsumption = 0;
let sessionTotalGridImport = 0;
let sessionTotalGridExport = 0;
let sessionCo2Emissions = 0;
let sessionEnergyCost = 0;

// CHART OBJECTS
let renewableEnergyChart;
let consumptionChart;
let batteryLevelChart;
let gridCo2Chart;


// --- CHART FUNCTIONS (inferred from chart_functions.js and chart_update_loop.js) ---
function initCharts() {
    const ctxRenewable = document.getElementById('renewableEnergyChart').getContext('2d');
    renewableEnergyChart = new Chart(ctxRenewable, {
        type: 'line',
        data: {
            labels: [], // Time labels
            datasets: [{
                label: 'Solar Power (W)',
                data: [],
                borderColor: 'rgb(255, 205, 86)',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(255, 205, 86, 0.2)'
            }, {
                label: 'Wind Power (W)',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time of Day'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });

    const ctxConsumption = document.getElementById('consumptionChart').getContext('2d');
    consumptionChart = new Chart(ctxConsumption, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Instantaneous Consumption (W)',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(255, 99, 132, 0.2)'
            }, {
                label: 'Daily Cumulative Consumption (Wh)',
                data: [],
                borderColor: 'rgb(54, 162, 235)',
                borderDash: [5, 5],
                tension: 0.3,
                yAxisID: 'y1' // Use a second Y-axis
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                },
                y1: { // Second Y-axis for cumulative consumption
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false // Only draw the grid for the first Y-axis
                    },
                    title: {
                        display: true,
                        text: 'Energy (Wh)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time of Day'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });

    const ctxBattery = document.getElementById('batteryLevelChart').getContext('2d');
    batteryLevelChart = new Chart(ctxBattery, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Battery Level (Wh)',
                data: [],
                borderColor: 'rgb(153, 102, 255)',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(153, 102, 255, 0.2)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: batteryCapacity,
                    title: {
                        display: true,
                        text: 'Energy (Wh)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time of Day'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });

    const ctxGridCo2 = document.getElementById('gridCo2Chart').getContext('2d');
    gridCo2Chart = new Chart(ctxGridCo2, {
        type: 'bar',
        data: {
            labels: ['Grid Import (Wh)', 'Grid Export (Wh)', 'CO2 Emissions (kg)'],
            datasets: [{
                label: 'Daily Totals',
                data: [0, 0, 0], // Initial values
                backgroundColor: [
                    'rgba(255, 159, 64, 0.7)', // Orange for import
                    'rgba(75, 192, 192, 0.7)', // Teal for export
                    'rgba(201, 203, 207, 0.7)' // Grey for CO2
                ],
                borderColor: [
                    'rgb(255, 159, 64)',
                    'rgb(75, 192, 192)',
                    'rgb(201, 203, 207)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false // Only one dataset, label is clear from bars
                }
            }
        }
    });
}

function updateCharts(timeLabel, solar, wind, instantaneousConsumption, battery, totalGridImport, totalGridExport, totalCo2) {
    const maxDataPoints = 60; // Show last 60 minutes for line charts (1 hour)

    // Update Renewable Energy Chart
    renewableEnergyChart.data.labels.push(timeLabel);
    renewableEnergyChart.data.datasets[0].data.push(solar);
    renewableEnergyChart.data.datasets[1].data.push(wind);
    if (renewableEnergyChart.data.labels.length > maxDataPoints) {
        renewableEnergyChart.data.labels.shift();
        renewableEnergyChart.data.datasets[0].data.shift();
        renewableEnergyChart.data.datasets[1].data.shift();
    }
    renewableEnergyChart.update();

    // Update Consumption Chart
    consumptionChart.data.labels.push(timeLabel);
    consumptionChart.data.datasets[0].data.push(instantaneousConsumption);
    consumptionChart.data.datasets[1].data.push(dailyCumulativeConsumption); // Use dailyCumulativeConsumption for second dataset
    if (consumptionChart.data.labels.length > maxDataPoints) {
        consumptionChart.data.labels.shift();
        consumptionChart.data.datasets[0].data.shift();
        consumptionChart.data.datasets[1].data.shift();
    }
    consumptionChart.update();


    // Update Battery Level Chart
    batteryLevelChart.data.labels.push(timeLabel);
    batteryLevelChart.data.datasets[0].data.push(battery);
    if (batteryLevelChart.data.labels.length > maxDataPoints) {
        batteryLevelChart.data.labels.shift();
        batteryLevelChart.data.datasets[0].data.shift();
    }
    batteryLevelChart.update();

    // Update Grid/CO2 Bar Chart (always reflects session totals)
    gridCo2Chart.data.datasets[0].data = [totalGridImport, totalGridExport, totalCo2];
    gridCo2Chart.update();
}

// --- WEBSOCKET LOGIC (placeholder for websocket.js) ---
function setupWebSocket() {
    // This is a placeholder for WebSocket logic.
    // You would typically connect to a WebSocket server here
    // to send or receive real-time updates.
    // Example:
    // const socket = new WebSocket('ws://localhost:8080/smartEnergy/websocket-endpoint');
    // socket.onopen = () => { console.log('WebSocket connected'); };
    // socket.onmessage = (event) => {
    //     const data = JSON.parse(event.data);
    //     console.log('Received WebSocket data:', data);
    //     // Process real-time updates, e.g., update live UI elements or charts
    // };
    // socket.onclose = () => { console.log('WebSocket disconnected'); };
    // socket.onerror = (error) => { console.error('WebSocket error:', error); };
}


// Function to update UI elements based on simulation state
function updateLiveUI(solarOutput, windOutput, currentConsumption, gridImport, batteryLevel, batteryStatus, gridStatusText = 'Connected', gridImportExportValue = 0) {
    liveSolarOutputSpan.textContent = `${solarOutput} W`;
    liveWindOutputSpan.textContent = `${windOutput} W`;
    currentTotalConsumptionSpan.textContent = `${currentConsumption} W`;
    batteryLevelSpan.textContent = `${Math.round(batteryLevel)} Wh`;
    batteryStatusSpan.textContent = batteryStatus;
    gridStatusSpan.textContent = gridStatusText;
    gridImportExportSpan.textContent = `${Math.round(gridImportExportValue)} W`;

    // Update battery icon color
    if (batteryStatus === 'Charging') {
        batteryIcon.className = 'fas fa-battery-full text-green-500 text-4xl';
    } else if (batteryStatus === 'Discharging') {
        batteryIcon.className = 'fas fa-battery-three-quarters text-yellow-500 text-4xl';
    } else {
        batteryIcon.className = 'fas fa-battery-empty text-red-500 text-4xl';
    }
}

// Function to fetch and display daily summary from the database
async function fetchAndDisplayDailySummary() {
    try {
        const response = await fetch('/smartEnergy/appliance-controller/get-daily-simulation-summary', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const summary = await response.json();

        if (summary && summary.success && summary.data) {
            const data = summary.data;
            summarySolarGeneratedSpan.textContent = `${Math.round(data.total_solar_generated)} Wh`;
            summaryWindGeneratedSpan.textContent = `${Math.round(data.total_wind_generated)} Wh`;
            summaryTotalConsumptionSpan.textContent = `${Math.round(data.total_consumption)} Wh`;
            summaryGridImportSpan.textContent = `${Math.round(data.total_grid_import)} Wh`;
            summaryGridExportSpan.textContent = `${Math.round(data.total_grid_export)} Wh`;
            summaryCo2EmissionsSpan.textContent = `${(data.total_co2_emissions).toFixed(2)} kg`;
        } else {
            console.warn("No daily summary data available or success false:", summary);
            // Optionally reset display to 0 or 'N/A'
            summarySolarGeneratedSpan.textContent = `0 Wh`;
            summaryWindGeneratedSpan.textContent = `0 Wh`;
            summaryTotalConsumptionSpan.textContent = `0 Wh`;
            summaryGridImportSpan.textContent = `0 Wh`;
            summaryGridExportSpan.textContent = `0 Wh`;
            summaryCo2EmissionsSpan.textContent = `0 kg`;
        }
    } catch (error) {
        console.error("Error fetching daily summary:", error);
    }
}


// Function to update simulation data in the database
async function syncSimulationState() {
    try {
        const stateData = {
            total_solar_generated: sessionTotalSolarGenerated,
            total_wind_generated: sessionTotalWindGenerated,
            total_consumption: sessionTotalConsumption,
            total_grid_import: sessionTotalGridImport,
            total_grid_export: sessionTotalGridExport,
            co2_emissions: sessionCo2Emissions,
            current_cost: sessionEnergyCost,
            battery_level: currentBatteryLevel,
            simulated_time_minutes: currentSimulationTime,
            live_consumption: parseFloat(currentTotalConsumptionSpan.textContent),
            current_cost_rate: simulationConfig.costRate,
            daily_quota_remaining: parseFloat(dailyQuotaRemaining.textContent),
            current_daily_consumption: parseFloat(currentDailyConsumption.textContent)
        };

        const response = await fetch('/smartEnergy/appliance-controller/update-simulation-state', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(stateData),
        });
        const result = await response.json();

        if (result.success) {
            // console.log("Simulation state synced:", result.message);
        } else {
            console.error("Failed to sync simulation state:", result.message);
        }
    } catch (error) {
        console.error("Error syncing simulation state:", error);
    }
}

// Function to fetch simulation configuration from the database
async function getSimulationConfig() {
    try {
        const response = await fetch('/smartEnergy/appliance-controller/get-simulation-config', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const config = await response.json();

        if (config.success && config.data) {
            numHousesInput.value = config.data.num_houses;
            dailyQuotaInput.value = config.data.daily_quota;
            costRateInput.value = config.data.cost_rate;
            simulationConfig = {
                numHouses: config.data.num_houses,
                dailyQuota: config.data.daily_quota,
                costRate: config.data.cost_rate
            };
            updateUIConfigDisplay();
            updateHouseIcons(simulationConfig.numHouses); // Update house icons on config load
            console.log("Simulation config loaded:", simulationConfig);
        } else {
            console.warn("Failed to load simulation config or no data:", config);
            // Use default values if loading fails
            simulationConfig = {
                numHouses: parseInt(numHousesInput.value),
                dailyQuota: parseInt(dailyQuotaInput.value),
                costRate: parseFloat(costRateInput.value)
            };
        }
    } catch (error) {
        console.error("Error fetching simulation config:", error);
        // Use default values on error
        simulationConfig = {
            numHouses: parseInt(numHousesInput.value),
            dailyQuota: parseInt(dailyQuotaInput.value),
            costRate: parseFloat(costRateInput.value)
        };
    }
}

// Function to update simulation configuration in the database
async function setSimulationConfig(newConfig) {
    try {
        const response = await fetch('/smartEnergy/appliance-controller/set-simulation-config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(newConfig),
        });
        const result = await response.json();

        if (result.success) {
            console.log("Simulation config updated:", result.message);
            simulationConfig = newConfig; // Update local config
            updateUIConfigDisplay();
            updateHouseIcons(simulationConfig.numHouses); // Update house icons immediately
        } else {
            console.error("Failed to update simulation config:", result.message);
        }
    } catch (error) {
        console.error("Error setting simulation config:", error);
    }
}

// Helper to update displayed config values in UI
function updateUIConfigDisplay() {
    numHousesInput.value = simulationConfig.numHouses;
    dailyQuotaInput.value = simulationConfig.dailyQuota;
    costRateInput.value = simulationConfig.costRate;
    currentCostRate.textContent = `$${simulationConfig.costRate.toFixed(5)}/Wh`; // Update widget as well
}


// Function to update the number of house icons
function updateHouseIcons(count) {
    const container = document.getElementById('houses-container');
    container.innerHTML = ''; // Clear existing icons
    for (let i = 0; i < count; i++) {
        const svg = `
                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" class="text-gray-700">
                    <path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3zm0 2.62L18.39 12H16v6h-3v-6h-2v6H8v-6H5.61L12 5.62z"/>
                </svg>
            `;
        container.innerHTML += svg;
    }
}


// MAIN SIMULATION LOGIC (per interval)
async function runSimulationStep() {
    if (!simulationRunning) return;

    currentSimulationTime = (currentSimulationTime + SIMULATION_TIME_INCREMENT_MINUTES) % 1440; // 1440 minutes in a day
    let hours = Math.floor(currentSimulationTime / 60);
    let minutes = currentSimulationTime % 60;
    simulatedTimeSpan.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

    // Fetch live weather every hour (simulated time) or at start
    if (currentSimulationTime % 60 === 0 || currentSimulationTime === 0) {
        lastWeatherData = await fetchWeather();
        currentWeatherSpan.textContent = `Weather: ${lastWeatherData.condition}, Wind: ${lastWeatherData.wind.toFixed(1)} km/h, Temp: ${lastWeatherData.temperature.toFixed(1)}°C`;
    }

    // Determine active profile based on weather or selected option
    let activeSolarProfile = selectedSolarProfile;
    if (solarProfileSelect.value !== 'default') {
        activeSolarProfile = dayProfilesWh[solarProfileSelect.value];
    }


    // Get consumption and solar/wind generation for the current minute
    const profileIndex = Math.floor(currentSimulationTime / SIMULATION_TIME_INCREMENT_MINUTES); // Index for the 1-minute profile
    const solarGenerationWh = (activeSolarProfile[profileIndex] || 0) * (lastWeatherData.condition.toLowerCase() === 'clear' ? 1 : (lastWeatherData.clouds ? (1 - (lastWeatherData.clouds / 100) * 0.7) : 1)); // Adjust based on cloudiness


    // Simplified wind generation based on wind speed (example scaling)
    const WIND_MAX_POWER_W = 2000; // Max power a wind turbine can generate in Watts
    const WIND_MAX_SPEED_KMH = 50; // Wind speed at which max power is generated
    let instantaneousWindPowerW = (lastWeatherData.wind / WIND_MAX_SPEED_KMH) * WIND_MAX_POWER_W;
    instantaneousWindPowerW = Math.min(Math.max(0, instantaneousWindPowerW), WIND_MAX_POWER_W); // Cap between 0 and max
    const windGenerationWh = instantaneousWindPowerW * (SIMULATION_TIME_INCREMENT_MINUTES / 60);

    // House consumption (scaled by number of houses)
    // Average consumption per minute for a typical house (e.g., 2kWh/day = 2000Wh/day, 1440 minutes/day -> ~1.39 Wh/minute average)
    // Let's use a more dynamic consumption, perhaps randomizing around a base value
    const BASE_HOUSE_CONSUMPTION_W_PER_MINUTE = 1500; // Base consumption per house in Watts for one minute
    const consumptionPerHouseWh = (BASE_HOUSE_CONSUMPTION_W_PER_MINUTE + (Math.random() - 0.5) * 500) * (SIMULATION_TIME_INCREMENT_MINUTES / 60); // Add some random variation
    const totalConsumptionWh = consumptionPerHouseWh * simulationConfig.numHouses; // Total consumption for all houses for this minute

    // Update cumulative consumption for daily quota
    dailyCumulativeConsumption += totalConsumptionWh;

    // Calculate net energy (generation - consumption)
    let netEnergyWh = (solarGenerationWh + windGenerationWh) - totalConsumptionWh;

    let gridImportWh = 0;
    let gridExportWh = 0;
    let batteryChangeWh = 0; // Positive for charging, negative for discharging

    // Battery and Grid Logic
    if (netEnergyWh > 0) {
        // Excess energy: try to charge battery first
        const chargeAmount = Math.min(netEnergyWh, batteryCapacity - currentBatteryLevel);
        currentBatteryLevel += chargeAmount;
        batteryChangeWh = chargeAmount;
        netEnergyWh -= chargeAmount; // Remaining excess after charging battery

        if (netEnergyWh > 0) {
            // Still excess energy: export to grid
            gridExportWh = netEnergyWh;
        }
    } else if (netEnergyWh < 0) {
        // Deficit energy: try to discharge battery first
        const dischargeAmount = Math.min(Math.abs(netEnergyWh), currentBatteryLevel - batteryMinDischarge);
        currentBatteryLevel -= dischargeAmount;
        batteryChangeWh = -dischargeAmount;
        netEnergyWh += dischargeAmount; // Remaining deficit after discharging battery

        if (netEnergyWh < 0) {
            // Still deficit energy: import from grid
            gridImportWh = Math.abs(netEnergyWh);
        }
    }

    // Update session totals
    sessionTotalSolarGenerated += solarGenerationWh;
    sessionTotalWindGenerated += windGenerationWh;
    sessionTotalConsumption += totalConsumptionWh; // This accumulates the actual consumption for the whole day
    sessionTotalGridImport += gridImportWh;
    sessionTotalGridExport += gridExportWh;
    sessionCo2Emissions += (gridImportWh / 1000) * CO2_EMISSION_RATE_KG_PER_KWH; // Wh to kWh

    // Calculate current cost (only for imported energy)
    sessionEnergyCost += (gridImportWh * simulationConfig.costRate);

    // Update live UI
    updateLiveUI(
        Math.round(solarGenerationWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Convert Wh per minute to W
        Math.round(windGenerationWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Convert Wh per minute to W
        Math.round(totalConsumptionWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Convert Wh per minute to W
        gridImportWh, // This is already in Wh for the interval, let's keep it consistent
        currentBatteryLevel,
        (batteryChangeWh > 0) ? 'Charging' : (batteryChangeWh < 0 ? 'Discharging' : 'Idle'),
        (gridImportWh > 0 || gridExportWh > 0) ? 'Active' : 'Connected', // More dynamic grid status
        (gridImportWh > 0 ? -gridImportWh : gridExportWh) / (SIMULATION_TIME_INCREMENT_MINUTES / 60) // Convert to W, negative for import
    );

    // Update summary stats in the current session
    totalSolarGeneratedSpan.textContent = `${Math.round(sessionTotalSolarGenerated)} Wh`;
    totalWindGeneratedSpan.textContent = `${Math.round(sessionTotalWindGenerated)} Wh`;
    totalConsumptionSpan.textContent = `${Math.round(sessionTotalConsumption)} Wh`;
    totalGridImportSpan.textContent = `${Math.round(sessionTotalGridImport)} Wh`;
    totalGridExportSpan.textContent = `${Math.round(sessionTotalGridExport)} Wh`;
    co2EmissionsSpan.textContent = `${sessionCo2Emissions.toFixed(2)} kg`;
    currentEnergyCostSpan.textContent = `$${sessionEnergyCost.toFixed(5)}`;

    // Update daily quota and consumption widgets
    const dailyQuotaRemainingValue = simulationConfig.dailyQuota - dailyCumulativeConsumption;
    dailyQuotaRemaining.textContent = `${Math.round(dailyQuotaRemainingValue)} Wh`;
    currentDailyConsumption.textContent = `${Math.round(dailyCumulativeConsumption)} Wh`;
    currentCostRate.textContent = `$${simulationConfig.costRate.toFixed(5)}/Wh`; // Ensure this widget updates

    // Update Charts
    updateCharts(
        simulatedTimeSpan.textContent, // Time label
        Math.round(solarGenerationWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Solar Power in W
        Math.round(windGenerationWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Wind Power in W
        Math.round(totalConsumptionWh / (SIMULATION_TIME_INCREMENT_MINUTES / 60)), // Consumption in W
        currentBatteryLevel, // Battery level in Wh
        sessionTotalGridImport, // Total Grid Import for bar chart
        sessionTotalGridExport, // Total Grid Export for bar chart
        sessionCo2Emissions // Total CO2 Emissions for bar chart
    );

    // If it's the end of the simulated day (00:00 after a full 24h cycle)
    if (currentSimulationTime === 0 && simulatedTimeSpan.textContent !== '00:00') {
        console.log("End of simulated day. Resetting daily cumulative consumption for next day.");
        dailyCumulativeConsumption = 0;
    }
}

// --- Initial Setup and Event Listeners ---
async function initialSetup() {
    console.log("--- initialSetup started ---");

    // 1. Get initial configuration from DB
    await getSimulationConfig();

    // 2. Update house icons based on loaded config
    updateHouseIcons(simulationConfig.numHouses);

    // 3. Initialize Charts
    initCharts();

    // 4. Fetch initial weather data
    lastWeatherData = await fetchWeather();
    currentWeatherSpan.textContent = `Weather: ${lastWeatherData.condition}, Wind: ${lastWeatherData.wind.toFixed(1)} km/h, Temp: ${lastWeatherData.temperature.toFixed(1)}°C`;

    // If solar profile is set to a specific day, override weather display for consistency
    if (solarProfileSelect.value !== 'default') {
        lastWeatherData.condition = solarProfileSelect.options[solarProfileSelect.selectedIndex].text; // Show selected profile name
    }

    // 5. Populate initial LIVE UI visuals with zeros/defaults (simulation not running yet)
    updateLiveUI(0, 0, 0, 0, currentBatteryLevel, 'Idle'); // Initial state for live visuals
    liveSolarOutputSpan.textContent = "0 W";
    liveWindOutputSpan.textContent = "0 W";
    currentTotalConsumptionSpan.textContent = "0 W";


    // 6. Start automatic updates for the DAILY SUMMARY section from the database
    // This ensures the summary section always shows the latest persisted state for today,
    // whether the simulation is running or not.
    summaryUpdateIntervalId = setInterval(fetchAndDisplayDailySummary, SUMMARY_DISPLAY_UPDATE_INTERVAL_MS);
    fetchAndDisplayDailySummary(); // Call once immediately on load

    // 7. Setup WebSocket (if any)
    setupWebSocket();

    console.log("--- initialSetup finished ---");
}

// --- Sidebar Toggle ---
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("-translate-x-full");
}

// Event Listeners
document.addEventListener("DOMContentLoaded", initialSetup);

startStopSimulationBtn.addEventListener("click", () => {
    if (simulationRunning) {
        clearInterval(simulationIntervalId);
        clearInterval(autoSyncIntervalId);
        simulationRunning = false;
        startStopSimulationBtn.textContent = "Start Simulation";
        startStopSimulationBtn.classList.remove("bg-red-600", "hover:bg-red-700");
        startStopSimulationBtn.classList.add("bg-blue-600", "hover:bg-blue-700");
        console.log("Simulation Stopped.");
    } else {
        simulationRunning = true;
        simulationIntervalId = setInterval(runSimulationStep, SIMULATION_INTERVAL_MS);
        autoSyncIntervalId = setInterval(syncSimulationState, AUTO_SYNC_INTERVAL_MS);
        startStopSimulationBtn.textContent = "Stop Simulation";
        startStopSimulationBtn.classList.remove("bg-blue-600", "hover:bg-blue-700");
        startStopSimulationBtn.classList.add("bg-red-600", "hover:bg-red-700");
        console.log("Simulation Started.");
        runSimulationStep(); // Run one step immediately on start
    }
});

updateConfigBtn.addEventListener("click", async () => {
    const newConfig = {
        numHouses: parseInt(numHousesInput.value),
        dailyQuota: parseInt(dailyQuotaInput.value),
        costRate: parseFloat(costRateInput.value)
    };
    await setSimulationConfig(newConfig);
});

// Update house icons immediately when numHouses changes without updating DB
numHousesInput.addEventListener("input", () => {
    updateHouseIcons(parseInt(numHousesInput.value));
});

// Sidebar toggle button
document.getElementById("menu-button").addEventListener("click", toggleSidebar);
document.getElementById("sidebar-toggle-btn").addEventListener("click", toggleSidebar);

// Solar profile selection changes
solarProfileSelect.addEventListener("change", async () => {
    if (solarProfileSelect.value === 'default') {
        lastWeatherData = await fetchWeather(); // Fetch live weather again
        currentWeatherSpan.textContent = `Weather: ${lastWeatherData.condition}, Wind: ${lastWeatherData.wind.toFixed(1)} km/h, Temp: ${lastWeatherData.temperature.toFixed(1)}°C`;
    } else {
        currentWeatherSpan.textContent = `Solar Profile: ${solarProfileSelect.options[solarProfileSelect.selectedIndex].text}`;
    }
    // Reset current simulation time to start of day if profile changes, to reflect new profile from beginning
    currentSimulationTime = 0;
    dailyCumulativeConsumption = 0; // Reset daily consumption
    // Optionally, reset session totals here if you want each profile run to be distinct from previous runs
    // For now, session totals will continue to accumulate.
});

