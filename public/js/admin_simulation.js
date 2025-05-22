
console.log("--- admin_simulation.js started ---"); // Add this as the FIRST line


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
    3.00, 3.00, 3.00, 2.75, // Using values at 12:00, 12:30, 12:50~13:00 etc.
    1.95, 2.20, 2.73, 2.50, // Using values at 14:00, 14:30, 15:00 etc.
    1.95, 1.50, 1.50, 1.75, // Using values at 16:00, 16:30, 17:00, 18:00 etc.
    1.58, 1.20, 2.00, 0.70, // Using values at 18:30, 19:00, 19:30, 19:45~20:00 etc.
    0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00 // Assuming 0 after 20:00
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


// --- Simulation Variables ---
let battery = batteryCapacity / 2; // Default start battery at half full
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


// --- Simulation Logic ---


// Function to get wind output based on profile or live data
async function getWindOutput(windProfileKey, liveWindSpeed) { // Pass live wind speed
    switch (windProfileKey) {
        case 'variable':
            return Math.floor((liveWindSpeed || 0) * 100); // Convert live wind speed to power (example scaling)
        case 'constant_medium':
            return 500; // Example constant medium wind power (Wh)
        case 'low':
            return 100; // Example low wind power (Wh)
        default:
            return 0;
    }
}

// Function to fetch weather (now always called to get live condition, wind, and clouds)
async function fetchWeather() {
    try {
        const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
        const data = await response.json();
        // Check for expected properties to avoid errors
        const windSpeed = data.wind?.speed || 0;
        const condition = data.weather?.[0]?.main || 'Unknown';
        const cloudiness = data.clouds?.all || 0; // Get cloudiness percentage

        console.log("Fetched Weather:", data); // Log weather data for debugging

        return {
            wind: windSpeed,
            condition: condition,
            clouds: cloudiness
        };
    } catch (error) {
        console.error("Weather API error:", error);
        // Fallback random values if API fails
        return {
            wind: Math.random() * 5 + 2,
            condition: "Unavailable",
            clouds: Math.random() * 100
        };
    }
}

// Function to get solar output from the currently selected profile based on simulated time
function getSolarOutputForTime(minutes) {
    if (!currentSolarProfileDataWh || currentSolarProfileDataWh.length === 0) {
        console.error("Solar profile data is not loaded.");
        return 0;
    }
    // Calculate the index for the time interval (ensure it wraps around for a 24-hour cycle)
    const index = Math.floor(minutes / SIMULATION_TIME_INCREMENT_MINUTES) % currentSolarProfileDataWh.length;
    return currentSolarProfileDataWh[index] || 0; // Return 0 if index is out of bounds or value is missing
}


function updateUI(simTime, solar, wind, consumption, batteryLevelValue, batteryStatusText, reserveBatteryLevelValue, gridImport, co2Emissions, condition, costRateText, renewableAvailable) {
    const hours = Math.floor(simTime / 60);
    const minutes = simTime % 60;
    const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

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


        intervalId = setInterval(async () => {
            const currentScenarioInLoop = solarProfileSelect.value; // Get current scenario inside the loop

            // --- Update Solar Profile based on Scenario (Fetch weather only if Default) ---
            if (currentScenarioInLoop === 'default') {
                // Fetch weather data periodically (e.g., every simulated hour) for Default scenario
                if (simulatedMinutes % 60 === 0) { // Fetch weather at the start of each simulated hour
                    const fetchedData = await fetchWeather();
                    if (fetchedData) { // Only update if fetch was successful
                        lastWeatherData = fetchedData;
                        console.log(`Weather updated at simulated ${Math.floor(simulatedMinutes / 60).toString().padStart(2, '0')}:00`, lastWeatherData);

                        // Update the active solar profile based on the NEW weather condition
                        const newProfileKey = mapWeatherConditionToProfileKey(lastWeatherData.condition, lastWeatherData.clouds);
                        // Need to check if the profile *data* is different, not just the key string
                        const newProfileData = dayProfilesWh[newProfileKey];
                        if (newProfileData && newProfileData !== currentSolarProfileDataWh) { // Only update if the profile data changes
                            currentSolarProfileDataWh = newProfileData;
                            console.log(`Switched solar profile to: ${newProfileKey}`);
                        } else if (!newProfileData) {
                            console.error(`Could not load new solar profile for key: ${newProfileKey}`);
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
            let energyFromGridWh = 0;
            let renewableEnergyUsedDirectlyWh = Math.min(energyNeededWh, totalGenerationWh); // Energy used directly from solar/wind

            let energyBalanceWh = totalGenerationWh - energyNeededWh; // Positive if surplus, negative if deficit

            batteryStatus = 'Idle'; // Default to Idle


            if (energyBalanceWh > 0) {
                // Surplus energy -> Charge battery or feed to grid

                // Prioritize charging the 'daily use' portion (up to reserve threshold)
                const spaceInDailyUseWh = batteryCapacity - batteryReserveThreshold; // Space from threshold to full capacity
                const chargeToDailyUse = Math.min(energyBalanceWh, Math.max(0, spaceInDailyUseWh - (battery - batteryReserveThreshold))); // Charge up to capacity, prioritize below capacity but above threshold

                // Simplified charging: just fill up to capacity, prioritizing the daily use range
                const chargeAmount = Math.min(energyBalanceWh, batteryCapacity - battery);
                battery += chargeAmount;
                energyBalanceWh -= chargeAmount; // Reduce surplus

                if (chargeAmount > 0) {
                    batteryStatus = (battery > batteryReserveThreshold) ? 'Charging (Reserve)' : 'Charging (Daily)';
                }


                // Any remaining energyBalanceWh > 0 is surplus fed back to grid (not simulated here)

            } else if (energyBalanceWh < 0) {
                // Deficit energy -> Discharge battery or draw from grid
                const energyNeededFromOtherSourcesWh = Math.abs(energyBalanceWh);
                let energyToSupplyWh = energyNeededFromOtherSourcesWh; // Energy that still needs to be supplied
                let dischargeAmount = 0;

                // Determine maximum possible discharge from the battery for this interval
                let maxDischargePossible = battery - batteryMinDischarge; // Cannot go below 0 Wh

                // Special rule for "Good Day": Do not discharge below the reserve threshold (8000 Wh)
                if (currentScenarioInLoop === 'good_day') {
                    maxDischargePossible = battery - batteryReserveThreshold; // Cannot go below 8000 Wh
                    // Ensure maxDischargePossible is not negative if battery is already below threshold (shouldn't happen on good day start)
                    maxDischargePossible = Math.max(0, maxDischargePossible);
                }

                // The actual amount discharged is the minimum of energy needed and max possible discharge
                dischargeAmount = Math.min(energyToSupplyWh, maxDischargePossible);

                battery -= dischargeAmount;
                energyFromBatteryWh += dischargeAmount;
                energyToSupplyWh -= dischargeAmount; // Update remaining energy needed


                if (dischargeAmount > 0) {
                    // Update battery status based on whether we are discharging from the daily or reserve portion
                    if (battery >= batteryReserveThreshold) { // Check AGAINST the threshold AFTER discharging
                        batteryStatus = 'Discharging (Daily)';
                    } else { // Battery is now below the threshold (only happens in non-GoodDay scenarios)
                        batteryStatus = 'Discharging (Reserve)';
                    }
                }


                // Any remaining energyToSupplyWh > 0 must come from the grid
                if (energyToSupplyWh > 0) {
                    energyFromGridWh = energyToSupplyWh;
                    totalGridImportToday += energyFromGridWh; // Accumulate grid import
                }
            } else {
                // energyBalanceWh is 0 - generation equals consumption
                batteryStatus = 'Idle';
            }

            // Ensure battery level stays within bounds (between min discharge and capacity)
            battery = Math.max(batteryMinDischarge, Math.min(batteryCapacity, battery));

            // Calculate reserve battery level for display
            const currentReserveBatteryLevelWh = Math.max(0, battery - batteryReserveThreshold);


            const currentRenewableAvailableWh = renewableEnergyUsedDirectlyWh + energyFromBatteryWh; // Energy supplied by renewables or battery discharge


            // --- CO2 Emission Calculation ---
            // Emissions are based on energy drawn from the grid (convert Wh to kWh for calculation)
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
                    // If simulating Good Day repeatedly, the battery should stay full (or at threshold if it dropped)
                    battery = Math.max(batteryReserveThreshold, battery); // Ensure it doesn't go below threshold at start of new Good Day
                    console.log("Good Day simulated day finished. Resetting daily stats. Battery remains at or above reserve threshold.");
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
}

// Call initial setup function on page load
initialSetup();
