<?php
// Ensure this page is only accessible by authenticated admins
if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
    header('Location: /smartEnergy/login'); // Redirect to login if not admin
    exit;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEnergy Admin - Manage Users</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom styles for accordion */
        .accordion-header {
            cursor: pointer;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            /* gray-200 */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .accordion-content {
            display: none;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            /* gray-200 */
            background-color: #f9fafb;
            /* gray-50 */
        }

        .accordion-content.active {
            display: block;
        }

        .rotate-icon {
            transform: rotate(90deg);
        }
    </style>
</head>

<body class="bg-gray-100 flex h-screen">

    <div id="sidebar" class="w-64 bg-gray-800 text-white p-4 space-y-4 fixed h-full transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0">
        <div class="text-2xl font-bold text-center">SmartEnergy Admin</div>
        <nav>
            <a href="/smartEnergy/admin/dashboard/" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Dashboard</a>
            <a href="/smartEnergy/admin/manage-users" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 bg-gray-700">Manage Users</a>
            <a href="/smartEnergy/logout" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Logout</a>
        </nav>
    </div>

    <div class="flex-1 flex flex-col md:ml-64">
        <header class="w-full bg-white shadow-md p-4 flex items-center justify-between">
            <button id="sidebarToggle" class="md:hidden p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800">Manage Client Users</h1>
            <div>
                <button id="userDropdownToggle" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['user_data']['username'] ?? 'Admin'); ?></span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </button>
                <div id="userDropdown" class="absolute right-4 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-10">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                    <a href="/smartEnergy/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Client Users</h2>

                <div class="mb-6">
                    <input type="text" id="userSearch" placeholder="Search by username or ID..."
                        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div id="userList" class="space-y-4">
                    <p id="loadingMessage" class="text-center text-gray-500">Loading users...</p>
                    <p id="noUsersFound" class="text-center text-gray-500 hidden">No users found.</p>
                </div>
            </div>
        </main>
    </div>

    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
            <h3 class="text-xl font-bold mb-6">Edit User Details</h3>
            <form id="editUserForm" class="space-y-4">
                <input type="hidden" id="editUserId">

                <div>
                    <label for="editUsername" class="block text-sm font-medium text-gray-700">Username:</label>
                    <input type="text" id="editUsername" name="username" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="editEmail" class="block text-sm font-medium text-gray-700">Email:</label>
                    <input type="email" id="editEmail" name="email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">Client Profile</h4>
                    <div>
                        <label for="editFirstName" class="block text-sm font-medium text-gray-700">First Name:</label>
                        <input type="text" id="editFirstName" name="first_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editLastName" class="block text-sm font-medium text-gray-700">Last Name:</label>
                        <input type="text" id="editLastName" name="last_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editAddress" class="block text-sm font-medium text-gray-700">Address:</label>
                        <input type="text" id="editAddress" name="address" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editDailyQuota" class="block text-sm font-medium text-gray-700">Daily Quota (Wh):</label>
                        <input type="number" id="editDailyQuota" name="daily_quota_wh" min="0" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editCurrentBalance" class="block text-sm font-medium text-gray-700">Current Balance:</label>
                        <input type="number" id="editCurrentBalance" name="current_balance" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" id="cancelEditBtn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const userSearch = document.getElementById('userSearch');
        const userList = document.getElementById('userList');
        const loadingMessage = document.getElementById('loadingMessage');
        const noUsersFound = document.getElementById('noUsersFound');

        const editUserModal = document.getElementById('editUserModal');
        const editUserForm = document.getElementById('editUserForm');
        const cancelEditBtn = document.getElementById('cancelEditBtn');

        // Form fields for editing
        const editUserId = document.getElementById('editUserId');
        const editUsername = document.getElementById('editUsername');
        const editEmail = document.getElementById('editEmail');
        const editFirstName = document.getElementById('editFirstName');
        const editLastName = document.getElementById('editLastName');
        const editAddress = document.getElementById('editAddress');
        const editDailyQuota = document.getElementById('editDailyQuota');
        const editCurrentBalance = document.getElementById('editCurrentBalance');


        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Function to fetch users from the API
        async function fetchUsers(searchTerm = '') {
            loadingMessage.classList.remove('hidden');
            noUsersFound.classList.add('hidden');
            userList.innerHTML = ''; // Clear previous list

            try {
                const response = await fetch(`/smartEnergy/api/admin/users?search=${encodeURIComponent(searchTerm)}`);
                const result = await response.json();

                loadingMessage.classList.add('hidden');

                if (result.status === 'success' && result.data && result.data.length > 0) {
                    result.data.forEach(user => {
                        const userItem = document.createElement('div');
                        userItem.className = 'bg-white rounded-md shadow-sm border border-gray-200';
                        userItem.innerHTML = `
                            <div class="accordion-header px-4 py-3 font-semibold text-gray-700 hover:bg-gray-50 flex justify-between items-center" data-user-id="${user.id}">
                                <span>${user.username} (ID: ${user.id})</span>
                                <i class="fas fa-chevron-right text-gray-400 text-sm transform transition-transform duration-300"></i>
                            </div>
                            <div class="accordion-content p-4 text-sm text-gray-600 space-y-2">
                                <p><strong>Email:</strong> ${user.email}</p>
                                <p><strong>User Type:</strong> ${user.user_type}</p>
                                <p><strong>Registration Date:</strong> ${user.registration_date}</p>
                                <div class="client-profile-data">
                                    <p class="text-center text-gray-500">Loading client profile...</p>
                                </div>
                                <div class="flex space-x-2 mt-4">
                                    <button class="edit-user-btn px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600" data-user-id="${user.id}">Edit</button>
                                    <button class="delete-user-btn px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600" data-user-id="${user.id}">Delete</button>
                                </div>
                            </div>
                        `;
                        userList.appendChild(userItem);
                    });
                    attachAccordionListeners();
                    attachActionListeners();
                } else {
                    noUsersFound.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching users:', error);
                loadingMessage.classList.add('hidden');
                noUsersFound.textContent = 'Error loading users.';
                noUsersFound.classList.remove('hidden');
            }
        }

        // Function to attach accordion functionality
        function attachAccordionListeners() {
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', async function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    const userId = this.dataset.userId;
                    const clientProfileDataContainer = content.querySelector('.client-profile-data');

                    // Toggle visibility
                    content.classList.toggle('active');
                    icon.classList.toggle('rotate-icon');

                    // If accordion is opened and profile data not loaded yet
                    if (content.classList.contains('active') && clientProfileDataContainer.textContent.includes('Loading client profile...')) {
                        await fetchAndDisplayUserDetails(userId, clientProfileDataContainer);
                    }
                });
            });
        }

        // Function to fetch and display full user details (including client profile, consumption, appliances)
        async function fetchAndDisplayUserDetails(userId, container) {
            container.innerHTML = '<p class="text-center text-gray-500">Loading full user details...</p>';
            try {
                const response = await fetch(`/smartEnergy/api/admin/users/${userId}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const userData = result.data;
                    let html = '';

                    html += '<h5 class="font-bold mt-2">Client Profile:</h5>';
                    if (userData.profile) {
                        for (const key in userData.profile) {
                            html += `<p><strong>${formatKey(key)}:</strong> ${userData.profile[key]}</p>`;
                        }
                    } else {
                        html += '<p>No client profile found.</p>';
                    }

                    html += '<h5 class="font-bold mt-2">Appliances:</h5>';
                    if (userData.appliances && userData.appliances.length > 0) {
                        userData.appliances.forEach(app => {
                            html += `<p>- ${app.appliance_id} (ID: ${app.id}, State: ${app.is_on ? 'On' : 'Off'})</p>`;
                        });
                    } else {
                        html += '<p>No appliances found.</p>';
                    }

                    html += '<h5 class="font-bold mt-2">Latest Consumption Log:</h5>';
                    if (userData.latest_consumption) {
                        html += `<p><strong>Current Consumption (W):</strong> ${userData.latest_consumption.current_consumption_w}</p>`;
                        html += `<p><strong>Daily Consumption (Wh):</strong> ${userData.latest_consumption.daily_consumption_wh}</p>`;
                        html += `<p><strong>Timestamp:</strong> ${userData.latest_consumption.timestamp}</p>`;
                    } else {
                        html += '<p>No consumption data found.</p>';
                    }

                    container.innerHTML = html;
                } else {
                    container.innerHTML = `<p class="text-red-500">Error: ${result.message || 'Could not load user details.'}</p>`;
                }
            } catch (error) {
                console.error('Error fetching user details:', error);
                container.innerHTML = '<p class="text-red-500">Error loading user details.</p>';
            }
        }

        // Helper to format keys for display
        function formatKey(key) {
            return key.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        }

        // Function to attach edit/delete button listeners
        function attachActionListeners() {
            document.querySelectorAll('.edit-user-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent accordion from toggling
                    const userId = this.dataset.userId;
                    openEditModal(userId);
                });
            });

            document.querySelectorAll('.delete-user-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent accordion from toggling
                    const userId = this.dataset.userId;
                    confirmDelete(userId);
                });
            });
        }

        // Open Edit Modal and populate with data
        async function openEditModal(userId) {
            try {
                const response = await fetch(`/smartEnergy/api/admin/users/${userId}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const userData = result.data;
                    const profile = userData.profile || {}; // Handle cases where profile might be null

                    editUserId.value = userId;
                    editUsername.value = userData.username || '';
                    editEmail.value = userData.email || '';
                    editFirstName.value = profile.first_name || '';
                    editLastName.value = profile.last_name || '';
                    editAddress.value = profile.address || '';
                    editDailyQuota.value = profile.daily_quota_wh || 0;
                    editCurrentBalance.value = profile.current_balance || 0.00;

                    editUserModal.classList.remove('hidden');
                } else {
                    alert('Error loading user data for editing: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error fetching user data for edit:', error);
                alert('An error occurred while fetching user data for editing.');
            }
        }

        // Close Edit Modal
        cancelEditBtn.addEventListener('click', () => {
            editUserModal.classList.add('hidden');
        });

        // Handle Edit User Form Submission
        editUserForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const userId = editUserId.value;
            const formData = new FormData(this);
            const data = {
                userId: userId,
                username: formData.get('username'),
                email: formData.get('email'),
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                address: formData.get('address'),
                daily_quota_wh: parseInt(formData.get('daily_quota_wh')),
                current_balance: parseFloat(formData.get('current_balance'))
            };

            if (isNaN(data.daily_quota_wh) || isNaN(data.current_balance)) {
                alert("Please enter valid numbers for Daily Quota and Current Balance.");
                return;
            }

            try {
                const response = await fetch('/smartEnergy/api/admin/users/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    alert('User updated successfully!');
                    editUserModal.classList.add('hidden');
                    fetchUsers(userSearch.value); // Re-fetch and display users
                } else {
                    alert('Error updating user: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating user:', error);
                alert('An error occurred while updating user.');
            }
        });

        // Confirm and Delete User
        async function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete this user and ALL their associated data (appliances, consumption logs, profile)? This action cannot be undone.')) {
                try {
                    const response = await fetch('/smartEnergy/api/admin/users/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            userId: userId
                        })
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        alert('User deleted successfully!');
                        fetchUsers(userSearch.value); // Re-fetch and display users
                    } else {
                        alert('Error deleting user: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting user:', error);
                    alert('An error occurred while deleting user.');
                }
            }
        }


        // Initial load of users
        document.addEventListener('DOMContentLoaded', () => {
            fetchUsers();

            // Search functionality with debounce
            let searchTimeout;
            userSearch.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchUsers(userSearch.value);
                }, 300); // 300ms debounce
            });
        });
    </script>
</body>

</html>
