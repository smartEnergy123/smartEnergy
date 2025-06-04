# ⚡ SmartEnergy - Smart Power Allocation & Monitoring System

SmartEnergy is a web-based energy management system designed for small residential estates powered by solar and/or wind energy. It allows **administrators** to simulate energy generation and consumption conditions, and enables **tenants (clients)** to monitor their energy use and control loads like fans, TVs, and refrigerators in real time.

---

## 🚀 Purpose & Main Aim

The project aims to:
- Promote fair, optimized usage of renewable energy within shared environments.
- Prevent energy waste by allocating power daily based on availability.
- Empower users to monitor and manage their consumption independently.
- Simulate real-world conditions like cloudy/sunny/rainy weather to determine power generation impact.

---

## 🧑‍💼 Admin Panel Features

- Simulate **solar/wind energy** based on weather conditions
- View **battery storage** status and system stats
- Monitor **per-household consumption**
- View user activities (e.g., load toggling)
- Manage system behaviors in real time

---

## 👤 Client Dashboard Features

- Log in securely
- View **daily energy allocation**
- Monitor real-time **energy usage**
- Toggle specific loads:
  - Fan
  - Refrigerator
  - TV
  - Air Conditioner
- See estimated **remaining energy** and usage stats

---

## 🧰 Tech Stack

### Frontend:
- **HTML5**, **Tailwind CSS** – Responsive and animated UI
- **JavaScript (Vanilla)** – Real-time updates and interactivity
- **SVG Animations** – For solar panel, sun, and wind turbine effects

### Backend:
- **PHP (MVC structured)** – Core application logic and session handling
- **MySQL** – User data, energy logs, device status
- **FileZilla / FTP** – Deployment and updates

---

## 🌐 Deployment

Hosted using **AwardSpace** (free web hosting).
Supports PHP + MySQL and has been tested with:

- FTP file deployment using FileZilla
- Clean public access via subdomain
- Live simulation of energy allocation and load toggling

---

## 🔐 Admin Access (Example)

> 🧪 Replace with real credentials if needed for test purposes.

- **URL**: `https://yourdomain.awardspace.info/admin`
- **Username**: `admin`
- **Password**: `admin123`

---

## 📁 Folder Structure (Simplified)

/smartEnergy/
│
├── index.php # Landing/Login page
├── /admin/ # Admin dashboard and simulation logic
├── /client/ # Client dashboard
├── /assets/ # Images, logos
├── /css/ # Tailwind CSS styles
├── /js/ # Load toggling and real-time updates
├── /services/ # Backend logic and simulation
├── /includes/ # Header, footer, session config
└── /database/ # DB connection and queries



---

## 🧠 Future Improvements (Post-MVP)

- Add **payment system** for subscription or extra energy purchases
- Integrate with **real sensors** (ESP32/Arduino for hardware)
- Notification system for low battery or excessive usage
- Advanced user roles (landlord, maintenance, etc.)
- PWA (Progressive Web App) version for mobile control

---

## 📜 License

This project is for educational and hackathon use.
Feel free to modify and expand upon it with attribution.

---

> Made with ☀️, 💨, and code by Kingsley.

