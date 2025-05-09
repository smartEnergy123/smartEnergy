<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - SmartEnergy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .slide-in {
            transform: translateX(-100%);
            opacity: 0;
            animation: slideIn 0.6s ease-out forwards;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .msg {
            border: 2px solid gray;
            box-shadow: 2px 2px 2px gray;
            position: absolute;
            left: 25%;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-green-100 via-white to-green-200 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-green-700">SmartEnergy</h1>
            <nav class="space-x-4">
                <a href="/" class="text-gray-700 hover:text-green-600">Home</a>
                <a href="/smartEnergy/login" class="text-gray-700 hover:text-green-600">Login</a>
            </nav>
        </div>
    </header>

    <!-- Registration Form -->
    <main class="flex justify-center items-center mt-20 px-4">
        <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-md slide-in">
            <h2 class="text-2xl font-bold text-center mb-6 text-green-700">Create an Account</h2>

            <form action="/smartEnergy/register" method="POST" class="space-y-4">
                <div class="msg">
                    <?php

                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="success"> ' . $_SESSION['success_message'] . '</div>';
                        unset($_SESSION['success_message']);
                    ?>
                        <script>
                            document.addEventListener("DOMContentLoaded", () => {
                                var success = document.querySelectorAll('.success');

                                if (success.length > 0) {
                                    success.forEach(function(msg) {
                                        msg.style.color = 'green';
                                    })
                                }

                                setTimeout(() => {
                                    window.location.href = '/smartEnergy/login'; //redirect to the previous page
                                }, 9000);
                            });
                        </script>
                    <?php
                    }

                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="error"> ' . $_SESSION['error_message'] . '</div>';
                        unset($_SESSION['error_message']);
                    ?>
                        <script>
                            document.addEventListener("DOMContentLoaded", () => {
                                var error = document.querySelectorAll('.error');

                                if (error.length > 0) {
                                    error.forEach(function(msg) {
                                        msg.style.color = 'red';
                                    })
                                }

                                setTimeout(() => {
                                    window.location.href = '/smartEnergy/login'; //redirect to the previous page
                                }, 9000);
                            });
                        </script>
                    <?php
                    }
                    ?>
                </div>
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off"
                        class="mt-1 w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:outline-none focus:border-green-500" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required required autocomplete="off"
                        class="mt-1 w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:outline-none focus:border-green-500" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required required autocomplete="off"
                        class="mt-1 w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:outline-none focus:border-green-500" />
                </div>

                <button type="submit" name="registerBtn"
                    class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-all">
                    Register
                </button>

                <p class="text-center text-sm text-gray-600 mt-3">
                    Already have an account? <a href="/smartEnergy/login" class="text-green-600 hover:underline">Login</a>
                </p>
            </form>
        </div>
    </main>
</body>

</html>
