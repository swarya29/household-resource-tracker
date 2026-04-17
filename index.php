<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Smart Resource Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gravity.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styling for the table inside the Gravity theme */
        .table-dark-custom {
            color: #e2e8f0;
            background: transparent;
            margin-bottom: 0;
        }

        .table-dark-custom th {
            background-color: rgba(0, 240, 255, 0.03);
            border-bottom: 1px solid rgba(0, 240, 255, 0.3);
            color: #00f0ff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            padding: 1rem;
        }

        .table-dark-custom td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
            padding: 1rem;
            transition: background 0.3s ease;
        }

        .table-dark-custom tbody tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .action-btn {
            padding: 5px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        #loadingIndicator {
            display: none;
            margin-left: 10px;
        }

        /* Fix for dropdown menus to prevent white text on white background */
        select option {
            background-color: #0f172a;
            /* matches gravity theme dark */
            color: #fff;
        }

        /* Fix calendar icon color on datetime inputs to be visibile */
        input[type="datetime-local"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
    </style>
</head>

<body class="gravity-theme">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-5"
        style="background: rgba(15, 23, 42, 0.9); border-bottom: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#"
                style="font-weight: 800; color: #00f0ff; letter-spacing: 1px;">
                <span style="font-size: 1.5rem; margin-right: 10px;">🌌</span> EcoTrack
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"
                style="border-color: rgba(255,255,255,0.1);">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3 mb-2 mb-lg-0">
                        <button class="btn btn-outline-info btn-sm px-3" data-bs-toggle="modal"
                            data-bs-target="#limitsModal" style="border-radius: 20px; font-weight: 600;">🔔 Alerts &
                            Limits</button>
                    </li>
                    <li class="nav-item me-3 mb-2 mb-lg-0">
                        <span class="navbar-text text-light">
                            Welcome, <strong
                                style="color: #ffb703;"><?php echo htmlspecialchars($_SESSION['user']); ?></strong>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm px-4" href="?logout=true"
                            style="border-radius: 20px; font-weight: 600; border-width: 2px;">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- Quick Stats Row -->
        <div class="row mb-4" id="statsRow">
            <div class="col-md-3">
                <div class="card p-3 shadow text-center"
                    style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(0, 240, 255, 0.3); color: #fff;">
                    <h6 class="text-muted mb-1">Electricity</h6>
                    <h3 id="statElectricity" style="color: #00f0ff; font-weight: 800;">0.00 <small
                            style="font-size: 0.9rem;">kWh</small></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow text-center"
                    style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(0, 240, 255, 0.3); color: #fff;">
                    <h6 class="text-muted mb-1">Water</h6>
                    <h3 id="statWater" style="color: #00f0ff; font-weight: 800;">0.00 <small
                            style="font-size: 0.9rem;">hL</small></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow text-center"
                    style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(0, 240, 255, 0.3); color: #fff;">
                    <h6 class="text-muted mb-1">Gas</h6>
                    <h3 id="statGas" style="color: #00f0ff; font-weight: 800;">0.00 <small
                            style="font-size: 0.9rem;">kg/m³</small></h3>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 shadow"
                    data-bs-toggle="modal" data-bs-target="#deviceModal"
                    style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 183, 3, 0.3); color: #fff; border-radius: 12px;">
                    <span style="font-size: 1.5rem;">⚙️</span>
                    <span style="font-weight: 600; color: #ffb703;">Manage Devices</span>
                </button>
            </div>
        </div>

        <div class="card p-4 shadow mb-5"
            style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
            <h4 id="formTitle" class="mb-3" style="color: #00f0ff;">Log Resource Usage</h4>

            <!-- Form for Logging Usage -->
            <form id="trackerForm">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label text-light">Select Device/Unit</label>
                        <select class="form-select" id="deviceSelect" name="device_id" required
                            style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <option value="">-- Loading Devices --</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-light">Start Time</label>
                        <input class="form-control" type="datetime-local" id="startTimeInput" name="start_time" required
                            style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-light">End Time</label>
                        <input class="form-control" type="datetime-local" id="endTimeInput" name="end_time" required
                            style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <button type="submit" id="submitBtn" class="btn btn-success shadow-sm">Log Usage</button>
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="spinner-border text-info spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </form>

            <!-- Dynamic Alerts Container -->
            <div id="alertContainer" class="mt-3"></div>
        </div>

        <!-- MAIN TRACKER PAGE -->
        <div class="position-relative mt-5">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>

            <div class="gravity-container w-100 mx-auto" style="padding: 0;">
                <header class="gravity-header mb-4">
                    <h3 class="gravity-title" style="font-size: 2.5rem;">Consumption Analytics</h3>
                </header>

                <main class="glass-panel mb-4">
                    <div class="chart-container">
                        <canvas id="gravityChart"></canvas>
                    </div>
                </main>

                <!-- Recent Activity Table -->
                <div class="glass-panel mb-5">
                    <h4 class="mb-3 text-light">Recent Resource Activity</h4>
                    <div class="table-responsive">
                        <table class="table table-dark-custom">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Type</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Cons.</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recentLogsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">No recent activity.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="row mt-3">
            <div class="col-md-7">
                <div class="card p-4 shadow glass-panel h-100">
                    <h4 class="mb-3 text-light">Hourly Consumption Breakdown</h4>
                    <div class="table-responsive">
                        <table class="table table-dark-custom">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Resource</th>
                                    <th>Consumption</th>
                                </tr>
                            </thead>
                            <tbody id="hourlyTableBody">
                                <tr>
                                    <td colspan="3" class="text-center">No logs recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card p-4 shadow glass-panel h-100">
                    <h4 class="mb-3 text-light">Device-Wise Summary</h4>
                    <div class="table-responsive">
                        <table class="table table-dark-custom">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Type</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="deviceTableBody">
                                <tr>
                                    <td colspan="3" class="text-center">No data.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Management Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"
                style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(0, 240, 255, 0.2); color: #fff;">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="deviceModalTitle" style="color: #00f0ff;">Manage Devices</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deviceForm" class="mb-4">
                        <input type="hidden" name="id" id="editDeviceId">
                        <div class="mb-3">
                            <label class="form-label">Device Name</label>
                            <input type="text" class="form-control" name="name" id="deviceNameInput" required
                                style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resource Type</label>
                            <select class="form-select" name="resource_type" id="resourceTypeSelect" required
                                style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                                <option value="electricity">Electricity</option>
                                <option value="water">Water</option>
                                <option value="gas">Gas</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Rate</label>
                                <input type="number" step="0.001" class="form-control" name="consumption_rate"
                                    id="deviceRateInput" required
                                    style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" id="unitInput"
                                    placeholder="watts, L/h" required
                                    style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            </div>
                        </div>
                        <button type="submit" id="deviceSubmitBtn" class="btn btn-info w-100">Add Device</button>
                        <button type="button" id="cancelEditDeviceBtn" class="btn btn-secondary w-100 mt-2 d-none"
                            onclick="resetDeviceForm()">Cancel Edit</button>
                    </form>
                    <hr style="opacity: 0.1;">
                    <h6>Registered Devices</h6>
                    <ul id="activeDeviceList" class="list-group list-group-flush"
                        style="max-height: 200px; overflow-y: auto;"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts & Limits Modal -->
    <div class="modal fade" id="limitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"
                style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(0, 240, 255, 0.2); color: #fff;">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" style="color: #00f0ff;">Set Daily Limit Alerts</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="limitForm" class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">Alert Email</label>
                            <input type="email" class="form-control" name="email" id="alertEmailInput"
                                placeholder="your@email.com" required
                                style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Resource Type</label>
                                <select class="form-select" name="resource_type" required
                                    style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                                    <option value="electricity">Electricity (kWh)</option>
                                    <option value="water">Water (hL)</option>
                                    <option value="gas">Gas (kg)</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Daily Limit</label>
                                <input type="number" step="0.1" class="form-control" name="limit_value" required
                                    style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 text-dark" style="font-weight:600;">Save
                            Limit Configuration</button>
                    </form>
                    <hr style="opacity: 0.1;">
                    <h6>Active Daily Limits</h6>
                    <ul id="activeLimitList" class="list-group list-group-flush"
                        style="max-height: 150px; overflow-y: auto;"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Usage Modal -->
    <div class="modal fade" id="editUsageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"
                style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(0, 240, 255, 0.2); color: #fff;">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" style="color: #00f0ff;">Edit Usage Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUsageForm">
                        <input type="hidden" name="id" id="editLogId">
                        <div class="mb-3">
                            <label class="form-label">Device</label>
                            <select class="form-select" id="editLogDeviceSelect" name="device_id" required
                                style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1);"></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <input class="form-control" type="datetime-local" id="editLogStartInput" name="start_time"
                                required
                                style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <input class="form-control" type="datetime-local" id="editLogEndInput" name="end_time"
                                required
                                style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Log</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const devicesEndpoint = 'api/endpoints/devices.php';
        const logsEndpoint = 'api/endpoints/logs.php';
        const usageReportEndpoint = 'api/usage.php'; // Primary report endpoint
        let gravityChart;

        // UI Elements
        const trackerForm = document.getElementById('trackerForm');
        const deviceForm = document.getElementById('deviceForm');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const alertContainer = document.getElementById('alertContainer');

        const deviceSelect = document.getElementById('deviceSelect');
        const startTimeInput = document.getElementById('startTimeInput');
        const endTimeInput = document.getElementById('endTimeInput');
        const activeDeviceList = document.getElementById('activeDeviceList');

        const hourlyTableBody = document.getElementById('hourlyTableBody');
        const deviceTableBody = document.getElementById('deviceTableBody');

        const statElectricity = document.getElementById('statElectricity');
        const statWater = document.getElementById('statWater');
        const statGas = document.getElementById('statGas');

        // Chart Init
        function initChart() {
            const canvas = document.getElementById('gravityChart');
            const ctx = canvas?.getContext('2d');
            if (!ctx) return;

            // Create Gradients
            const elecGrad = ctx.createLinearGradient(0, 0, 0, 400);
            elecGrad.addColorStop(0, 'rgba(0, 240, 255, 0.4)');
            elecGrad.addColorStop(1, 'rgba(0, 240, 255, 0)');

            const waterGrad = ctx.createLinearGradient(0, 0, 0, 400);
            waterGrad.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            waterGrad.addColorStop(1, 'rgba(59, 130, 246, 0)');

            const gasGrad = ctx.createLinearGradient(0, 0, 0, 400);
            gasGrad.addColorStop(0, 'rgba(255, 0, 128, 0.4)');
            gasGrad.addColorStop(1, 'rgba(255, 0, 128, 0)');

            gravityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Electricity (kWh)',
                            data: [],
                            borderColor: '#00f0ff',
                            backgroundColor: elecGrad,
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3
                        },
                        {
                            label: 'Water (Liters)',
                            data: [],
                            borderColor: '#3b82f6',
                            backgroundColor: waterGrad,
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3
                        },
                        {
                            label: 'Gas (kg)',
                            data: [],
                            borderColor: '#ff0080',
                            backgroundColor: gasGrad,
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { labels: { color: '#e2e8f0', font: { family: "'Outfit', sans-serif" } } }
                    },
                    scales: {
                        x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                        y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
                    }
                }
            });
        }

        function showAlert(message, type) {
            if (!alertContainer) return;
            alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            setTimeout(() => { if (alertContainer) alertContainer.innerHTML = ''; }, 5000);
        }

        async function fetchDevices() {
            try {
                const res = await fetch(devicesEndpoint);
                const result = await res.json();
                if (result.status === 'success') {
                    renderDevices(result.data);
                }
            } catch (e) { console.error(e); }
        }

        function renderDevices(data) {
            if (!deviceSelect) return;
            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>' +
                data.map(a => `<option value="${a.id}">${a.name} [${a.resource_type.toUpperCase()}]</option>`).join('');

            if (activeDeviceList) {
                activeDeviceList.innerHTML = data.map(a => `
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; color: #fff; border-color: rgba(255,255,255,0.05);">
                        <div><strong>${a.name}</strong> <br><small class="text-muted">${a.resource_type} (${a.consumption_rate} ${a.unit})</small></div>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteDevice(${a.id})">Remove</button>
                    </li>
                `).join('');
            }
        }

        async function deleteDevice(id) {
            if (!confirm("Are you sure? All related usage logs will remain but the device will be gone.")) return;
            try {
                const res = await fetch(devicesEndpoint, {
                    method: 'DELETE',
                    body: JSON.stringify({ id: id }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await res.json();
                if (result.status === 'success') {
                    fetchDevices();
                }
            } catch (e) { console.error(e); }
        }

        async function fetchReport() {
            try {
                // Try primary endpoint first, fall back to legacy
                const res = await fetch(usageReportEndpoint + '?type=summary');
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const result = await res.json();
                console.log('[EcoTracker] Report API response:', result);

                if (result.status === 'success') {
                    updateUI(result.data);
                } else {
                    console.warn('[EcoTracker] API returned error:', result.message);
                    showAlert('Could not load data: ' + result.message, 'warning');
                    updateUI({});
                }
            } catch (e) {
                console.error('[EcoTracker] fetchReport failed:', e);
                showAlert('Dashboard error: could not reach the API. Check console for details.', 'danger');
                updateUI({});
            }
        }

        function updateUI(data) {
            console.log('[EcoTracker] updateUI called with:', data);

            // ── Stats ──────────────────────────────────────────────────────
            const stats = { electricity: 0, water: 0, gas: 0 };
            (data.resource_summary || []).forEach(r => {
                const type = (r.resource_type || '').toLowerCase();
                if (Object.prototype.hasOwnProperty.call(stats, type)) {
                    stats[type] = parseFloat(r.total) || 0;
                }
            });
            console.log('[EcoTracker] stats:', stats);

            if (statElectricity) statElectricity.innerHTML = `${stats.electricity.toFixed(2)} <small style="font-size:0.9rem;">kWh</small>`;
            if (statWater) statWater.innerHTML = `${stats.water.toFixed(2)} <small style="font-size:0.9rem;">hL</small>`;
            if (statGas) statGas.innerHTML = `${stats.gas.toFixed(2)} <small style="font-size:0.9rem;">kg</small>`;

            // ── Device-Wise Summary ────────────────────────────────────────
            if (deviceTableBody) {
                const rows = (data.device_wise || []);
                if (rows.length === 0) {
                    deviceTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No usage data available.</td></tr>';
                } else {
                    deviceTableBody.innerHTML = rows.map(d => {
                        const unitLabel = d.resource_type === 'electricity' ? 'kWh' : (d.resource_type === 'water' ? 'L' : 'kg');
                        return `<tr>
                            <td>${d.device_name ?? d.name ?? '—'}</td>
                            <td class="text-capitalize">${d.resource_type}</td>
                            <td>${parseFloat(d.total).toFixed(3)} ${unitLabel}</td>
                        </tr>`;
                    }).join('');
                }
            }

            // ── Hourly Breakdown ───────────────────────────────────────────
            if (hourlyTableBody) {
                const rows = (data.hourly_breakdown || []);
                if (rows.length === 0) {
                    hourlyTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hourly records yet.</td></tr>';
                } else {
                    hourlyTableBody.innerHTML = rows.map(h => {
                        const timePart = (h.hour || '').split(' ')[1]?.substring(0, 5) ?? h.hour;
                        return `<tr>
                            <td>${timePart}</td>
                            <td class="text-capitalize">${h.resource_type}</td>
                            <td>${parseFloat(h.total).toFixed(4)}</td>
                        </tr>`;
                    }).join('');
                }
            }

            // ── Recent Logs ───────────────────────────────────────────────
            const recentLogsTableBody = document.getElementById('recentLogsTableBody');
            if (recentLogsTableBody) {
                const rows = (data.recent_logs || []);
                if (rows.length === 0) {
                    recentLogsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No recent activity recorded.</td></tr>';
                } else {
                    recentLogsTableBody.innerHTML = rows.map(l => {
                        const safeL = JSON.stringify(l).replace(/"/g, '&quot;');
                        return `<tr>
                            <td>${l.device_name}</td>
                            <td class="text-capitalize">${l.resource_type}</td>
                            <td><small>${(l.start_time || '').substring(5, 16)}</small></td>
                            <td><small>${(l.end_time || '').substring(5, 16)}</small></td>
                            <td>${parseFloat(l.consumption).toFixed(3)}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick="openEditLogModal(${safeL})">✎</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteLog(${l.id})">×</button>
                            </td>
                        </tr>`;
                    }).join('');
                }
            }

            // ── Chart ─────────────────────────────────────────────────────
            if (!gravityChart) return;

            const hourly = data.hourly_breakdown || [];
            if (hourly.length === 0) {
                gravityChart.data.labels = [];
                gravityChart.data.datasets.forEach(ds => ds.data = []);
                gravityChart.update();
                return;
            }

            // Sort chronologically (API returns ASC now)
            const sorted = [...hourly];
            const labels = [...new Set(sorted.map(h => h.hour ? h.hour.substring(5, 16).replace(' ', ' ') : ''))];
            gravityChart.data.labels = labels;

            // Bucket by resource type
            const resData = { electricity: {}, water: {}, gas: {} };
            labels.forEach(l => { resData.electricity[l] = 0; resData.water[l] = 0; resData.gas[l] = 0; });
            sorted.forEach(h => {
                const timeStr = h.hour ? h.hour.substring(5, 16).replace(' ', ' ') : '';
                const type = (h.resource_type || '').toLowerCase();
                if (Object.prototype.hasOwnProperty.call(resData, type)) {
                    resData[type][timeStr] = (resData[type][timeStr] || 0) + parseFloat(h.total);
                }
            });

            gravityChart.data.datasets[0].data = labels.map(l => resData.electricity[l] || 0);
            gravityChart.data.datasets[1].data = labels.map(l => resData.water[l] || 0);
            gravityChart.data.datasets[2].data = labels.map(l => resData.gas[l] || 0);
            gravityChart.update();
            console.log('[EcoTracker] Chart updated. Labels:', labels);
        }

        if (deviceForm) {
            deviceForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(deviceForm);
                const payload = Object.fromEntries(formData.entries());
                const id = payload.id;

                try {
                    const res = await fetch(devicesEndpoint, {
                        method: id ? 'PUT' : 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        showAlert(result.message, 'success');
                        resetDeviceForm();
                        fetchDevices();
                    } else {
                        alert(result.message);
                    }
                } catch (e) { console.error(e); }
            });

            // Dynamic unit suggestion
            document.getElementById('resourceTypeSelect').addEventListener('change', (e) => {
                const unitInput = document.getElementById('unitInput');
                if (document.getElementById('editDeviceId').value) return; // Don't overwrite unit when editing
                switch (e.target.value) {
                    case 'electricity': unitInput.value = 'watts'; break;
                    case 'water': unitInput.value = 'hL/h'; break;
                    case 'gas': unitInput.value = 'kg/h'; break;
                    default: unitInput.value = '';
                }
            });
        }

        function openEditDeviceModal(d) {
            document.getElementById('editDeviceId').value = d.id;
            document.getElementById('deviceNameInput').value = d.name;
            document.getElementById('resourceTypeSelect').value = d.resource_type;
            document.getElementById('deviceRateInput').value = d.consumption_rate;
            document.getElementById('unitInput').value = d.unit;
            document.getElementById('deviceModalTitle').innerText = "Edit Device";
            document.getElementById('deviceSubmitBtn').innerText = "Update Device";
            document.getElementById('cancelEditDeviceBtn').classList.remove('d-none');
        }

        function resetDeviceForm() {
            deviceForm.reset();
            document.getElementById('editDeviceId').value = "";
            document.getElementById('deviceModalTitle').innerText = "Manage Devices";
            document.getElementById('deviceSubmitBtn').innerText = "Add Device";
            document.getElementById('cancelEditDeviceBtn').classList.add('d-none');
        }

        if (trackerForm) {
            trackerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(trackerForm);
                const payload = Object.fromEntries(formData.entries());

                if (loadingIndicator) loadingIndicator.style.display = 'inline-block';
                try {
                    const res = await fetch(logsEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        showAlert(result.message, 'success');
                        trackerForm.reset();
                        fetchReport();
                    } else {
                        showAlert(result.message, 'danger');
                    }
                } catch (e) { console.error(e); }
                if (loadingIndicator) loadingIndicator.style.display = 'none';
            });
        }

        // Usage Log Logic (Edit/Delete) — routes through primary endpoint
        async function deleteLog(id) {
            if (!confirm('Delete this usage log?')) return;
            try {
                const res = await fetch(usageReportEndpoint, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await res.json();
                console.log('[EcoTracker] deleteLog result:', result);
                if (result.status === 'success') {
                    showAlert(result.message, 'success');
                    fetchReport();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (e) { console.error('[EcoTracker] deleteLog error:', e); }
        }

        function openEditLogModal(l) {
            const modal = new bootstrap.Modal(document.getElementById('editUsageModal'));
            document.getElementById('editLogId').value = l.id;

            // Populate devices in edit modal
            fetch(devicesEndpoint).then(r => r.json()).then(res => {
                const select = document.getElementById('editLogDeviceSelect');
                select.innerHTML = res.data.map(d => `<option value="${d.id}" ${d.id == l.device_id ? 'selected' : ''}>${d.name}</option>`).join('');
            });

            // Convert to datetime-local format (YYYY-MM-DDTHH:MM)
            document.getElementById('editLogStartInput').value = l.start_time.replace(' ', 'T').substring(0, 16);
            document.getElementById('editLogEndInput').value = l.end_time.replace(' ', 'T').substring(0, 16);
            modal.show();
        }

        document.getElementById('editUsageForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            console.log('[EcoTracker] editUsageForm payload:', payload);

            try {
                const res = await fetch(usageReportEndpoint, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                console.log('[EcoTracker] editUsage result:', result);
                if (result.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('editUsageModal')).hide();
                    fetchReport();
                    showAlert('Usage updated!', 'success');
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (e) { console.error('[EcoTracker] editUsage error:', e); }
        });

        function renderDevices(data) {
            if (!deviceSelect) return;
            const currentDevices = data.map(a => `<option value="${a.id}">${a.name} [${a.resource_type.toUpperCase()}]</option>`).join('');
            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>' + currentDevices;

            if (activeDeviceList) {
                activeDeviceList.innerHTML = data.map(a => `
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; color: #fff; border-color: rgba(255,255,255,0.05);">
                        <div><strong>${a.name}</strong> <br><small class="text-muted">${a.resource_type} (${a.consumption_rate} ${a.unit})</small></div>
                        <div>
                            <button class="btn btn-sm btn-outline-info" onclick="openEditDeviceModal(${JSON.stringify(a).replace(/"/g, '&quot;')})">✎</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDevice(${a.id})">×</button>
                        </div>
                    </li>
                `).join('');
            }
        }

        async function fetchLimits() {
            try {
                const res = await fetch('api/endpoints/limits.php');
                const result = await res.json();
                if (result.status === 'success') {
                    const emailInput = document.getElementById('alertEmailInput');
                    if (emailInput && result.data.email) emailInput.value = result.data.email;

                    const limitBody = document.getElementById('activeLimitList');
                    if (limitBody) {
                        const limits = result.data.limits || [];
                        if (limits.length === 0) {
                            limitBody.innerHTML = '<li class="list-group-item text-center text-muted" style="background: transparent; border:none;">No limits set.</li>';
                        } else {
                            limitBody.innerHTML = limits.map(l => `
                                <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; color: #fff; border-color: rgba(255,255,255,0.05);">
                                    <div><strong class="text-info">${l.resource_type.toUpperCase()}</strong></div>
                                    <div>Limit: <strong>${l.limit_value}</strong> ${l.unit}</div>
                                </li>
                            `).join('');
                        }
                    }
                }
            } catch (e) { console.error(e); }
        }

        const limitForm = document.getElementById('limitForm');
        if (limitForm) {
            limitForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(limitForm);
                const payload = Object.fromEntries(formData.entries());
                try {
                    const res = await fetch('api/endpoints/limits.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        showAlert(result.message, 'success');
                        fetchLimits();
                    } else {
                        showAlert(result.message, 'danger');
                    }
                } catch (e) { console.error(e); }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initChart();
            fetchDevices();
            fetchReport();
            fetchLimits();

            if (typeof bootstrap === 'undefined') {
                const script = document.createElement('script');
                script.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js";
                document.body.appendChild(script);
            }
        });
    </script>
</body>

</html>