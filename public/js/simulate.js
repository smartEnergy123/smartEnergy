//091e69af47ec41acdbb6e9138757b482
const apiKey = "091e69af47ec41acdbb6e9138757b482"; // Replace with your OpenWeatherMap API key
const city = "Lagos";

let battery = 500;
const batteryCapacity = 1000;
let intervalId = null;

const startBtn = document.getElementById("start");
const stopBtn = document.getElementById("stop");
const turbine = document.getElementsByClassName("turbine")[0];
const sun = document.getElementsByClassName("sun")[0];
const bolt = document.getElementsByClassName("bolt")[0];

async function fetchWeather() {
    try {
        const res = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
        const data = await res.json();

        const cloudiness = data.clouds.all;       // 0–100 (%)
        const windSpeed = data.wind.speed;        // m/s
        const condition = data.weather[0].main;   // e.g., "Clear", "Rain"

        const solar = Math.max(0, 400 - cloudiness * 3);     // Less sun when cloudy
        const wind = Math.floor(windSpeed * 100);            // Convert wind to watts

        return { solar, wind, condition };
    } catch (error) {
        console.error("Weather API error:", error);
        return {
            solar: Math.floor(Math.random() * 300 + 100),
            wind: Math.floor(Math.random() * 200 + 100),
            condition: "Unavailable"
        };
    }
}

function updateUI(solar, wind, consumption, battery, condition) {
    document.getElementById("solarOutput").textContent = `${solar} W`;
    document.getElementById("windOutput").textContent = `${wind} W`;
    document.getElementById("consumption").textContent = `${consumption} W`;
    document.getElementById("batteryLevel").textContent = `${battery} / ${batteryCapacity} Wh`;
    document.getElementById("weatherCondition").textContent = condition;

    sun.style.opacity = solar > 200 ? 1 : 0.4;
    turbine.style.animation = wind > 150 ? "spin 1s linear infinite" : "spin 3s linear infinite";
    bolt.style.opacity = consumption > 500 ? 1 : 0.5;
}

startBtn.addEventListener("click", () => {
    if (intervalId) return;

    intervalId = setInterval(async () => {
        const { solar, wind, condition } = await fetchWeather();
        const consumption = Math.floor(Math.random() * 300 + 200); // Simulate usage

        battery += (solar + wind - consumption);
        battery = Math.max(0, Math.min(battery, batteryCapacity));

        updateUI(solar, wind, consumption, battery, condition);
    }, 3000);
});

stopBtn.addEventListener("click", () => {
    clearInterval(intervalId);
    intervalId = null;
    turbine.style.animation = "none";
});
