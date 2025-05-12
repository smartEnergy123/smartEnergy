<?php
if (!$_SESSION['user_state']) {
    header('Location: /smartEnergy/login');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SmartEnergy Client Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
        }

        function toggleUserDropdown() {
            document.getElementById("userDropdown").classList.toggle("hidden");
        }
    </script>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar -->
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

    <!-- Main Content -->
    <div class="md:ml-64 transition-all">
        <!-- Header -->
        <header class="bg-white shadow-md py-8 px-6 flex justify-between items-center">
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
                    <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">🚪 Logout</a>
                </div>
            </div>
        </header>

        <!-- Dashboard Section -->
        <main class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between">
                        <h3 class="text-lg font-bold text-green-700 mb-2">Current Usage</h3>
                        <p class="px-4">⚡</p>
                    </div>
                    <p class="text-gray-600">2.4 kWh / 5.0 kWh</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between">
                        <h3 class="text-lg font-bold text-green-700 mb-2">Plan</h3>
                        <p class="text-gray-600 px-4">📄</p>
                    </div>
                    <p class="text-gray-600">Premium - Expires in 20 days</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow hover:shadow-lg transition">
                    <div class="flex justify-between">
                        <h3 class="text-lg font-bold text-green-700 mb-2">Total Energy Saved</h3>
                        <p class="text-black-900 px-4">∑</p>
                    </div>
                    <p class="text-gray-600">91 kWh this month</p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
