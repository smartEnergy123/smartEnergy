<?php
if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

// Default outputs based on weather
$weather = $_POST['weather'] ?? 'Sunny';
$solarOutput = 0;
$windOutput = 0;

switch ($weather) {
    case 'Sunny':
        $solarOutput = 1200;
        $windOutput = 500;
        break;
    case 'Cloudy':
        $solarOutput = 600;
        $windOutput = 700;
        break;
    case 'Rainy':
        $solarOutput = 300;
        $windOutput = 900;
        break;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Simulate Weather</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-100 to-gray-100 min-h-screen flex flex-col items-center justify-center p-6">

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full space-y-6">
        <h1 class="text-2xl font-bold text-center text-blue-800">🌦 Simulate Weather Conditions</h1>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-2 font-semibold text-gray-700">Select Weather:</label>
                <select name="weather" class="w-full p-2 border rounded-lg">
                    <option <?= $weather === 'Sunny' ? 'selected' : '' ?>>Sunny</option>
                    <option <?= $weather === 'Cloudy' ? 'selected' : '' ?>>Cloudy</option>
                    <option <?= $weather === 'Rainy' ? 'selected' : '' ?>>Rainy</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg">Simulate</button>
        </form>

        <div class="mt-4 text-center space-y-2">
            <p class="text-lg font-medium">🌤 Weather: <span class="font-bold"><?= $weather ?></span></p>
            <p>🔆 Solar Output: <span class="font-bold text-yellow-600"><?= $solarOutput ?> W</span></p>
            <p>🌬 Wind Output: <span class="font-bold text-green-600"><?= $windOutput ?> W</span></p>
        </div>

        <div class="text-center mt-4">
            <a href="/smartEnergy/admin/dashboard/" class="text-blue-500 hover:underline">← Back to Dashboard</a>
        </div>
    </div>

</body>

</html>
