<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

if(isset($_GET['logout'])){
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
        background: rgba(15, 23, 42, 0.6);
        border-radius: 12px;
        overflow: hidden;
    }
    .table-dark-custom th {
        background-color: rgba(30, 41, 59, 0.8);
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }
    .table-dark-custom td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
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
</style>
</head>
<body class="gravity-theme">

<div class="container mt-5 mb-5">
    <h2>Welcome <?php echo htmlspecialchars($_SESSION['user']); ?></h2>
    <a href="?logout=true" class="btn btn-danger mb-3 shadow-sm">Logout</a>

    <div class="card p-4 shadow" style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
        <h4 id="formTitle" class="mb-3" style="color: #00f0ff;">Add New Record</h4>
        
        <!-- Form for Adding / Editing Data -->
        <form id="trackerForm">
            <!-- Hidden input for ID (used during update) -->
            <input type="hidden" id="recordId" name="id">

            <label class="form-label text-light">Date</label>
            <input class="form-control mb-3" type="date" id="dateInput" name="date" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">

            <label class="form-label text-light">Water Usage (Liters)</label>
            <input class="form-control mb-3" type="number" step="0.1" id="waterInput" name="water" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">

            <label class="form-label text-light">Energy Usage (kWh)</label>
            <input class="form-control mb-3" type="number" step="0.1" id="energyInput" name="energy" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">

            <div class="d-flex align-items-center">
                <button type="submit" id="submitBtn" class="btn btn-success shadow-sm">Save Data</button>
                <button type="button" id="cancelBtn" class="btn btn-secondary ms-2" style="display:none;" onclick="resetForm()">Cancel Edit</button>
                
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
                <h3 class="gravity-title" style="font-size: 2.5rem;">Gravity Dashboard</h3>
            </header>

            <main class="glass-panel mb-5">
                <div class="chart-container">
                    <canvas id="gravityChart"></canvas>
                </div>
            </main>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card p-4 shadow mt-3 glass-panel">
        <h4 class="mb-3 text-light">Manage Existing Records</h4>
        <div class="table-responsive">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Water (L)</th>
                        <th>Energy (kWh)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody">
                    <tr><td colspan="4" class="text-center">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const apiEndpoint = 'api/usage.php';
    let gravityChart;
    
    // UI Elements
    const trackerForm = document.getElementById('trackerForm');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const alertContainer = document.getElementById('alertContainer');
    const dataTableBody = document.getElementById('dataTableBody');
    
    const formTitle = document.getElementById('formTitle');
    const recordIdInput = document.getElementById('recordId');
    const dateInput = document.getElementById('dateInput');
    const waterInput = document.getElementById('waterInput');
    const energyInput = document.getElementById('energyInput');
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    // Chart.js Setup Context
    const ctx = document.getElementById('gravityChart')?.getContext('2d');
    const waterGradient = ctx.createLinearGradient(0, 0, 0, 400);
    waterGradient.addColorStop(0, 'rgba(0, 240, 255, 0.8)');
    waterGradient.addColorStop(1, 'rgba(0, 240, 255, 0.05)');

    const energyGradient = ctx.createLinearGradient(0, 0, 0, 400);
    energyGradient.addColorStop(0, 'rgba(255, 0, 128, 0.8)');
    energyGradient.addColorStop(1, 'rgba(255, 0, 128, 0.05)');

    // Initialize Chart
    function initChart() {
        gravityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Water Consumption (L)',
                        data: [],
                        borderColor: '#00f0ff',
                        backgroundColor: waterGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#00f0ff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Energy Consumption (kWh)',
                        data: [],
                        borderColor: '#ff0080',
                        backgroundColor: energyGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ff0080',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#e2e8f0', font: { family: "'Outfit', sans-serif", size: 14, weight: '600' } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        titleFont: { family: "'Outfit', sans-serif", size: 14 }
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, ticks: { color: '#94a3b8', font: { family: "'Outfit', sans-serif" } } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, ticks: { color: '#94a3b8', font: { family: "'Outfit', sans-serif" } } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    }

    // Show Alert
    function showAlert(message, type) {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        
        // Auto remove alert after 5 seconds
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    // Toggle Loading
    function toggleLoading(isLoading) {
        loadingIndicator.style.display = isLoading ? 'inline-block' : 'none';
        submitBtn.disabled = isLoading;
    }

    // Fetch All Data
    async function fetchData() {
        try {
            const response = await fetch(apiEndpoint);
            if (!response.ok) throw new Error('Network response was not ok');
            const result = await response.json();
            
            if (result.status === 'success') {
                updateUI(result.data);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showAlert('Failed to load data from server.', 'danger');
        }
    }

    // Update Chart and Table
    function updateUI(data) {
        if (data.length === 0) {
            dataTableBody.innerHTML = '<tr><td colspan="4" class="text-center">No records found.</td></tr>';
            gravityChart.data.labels = ['No Data'];
            gravityChart.data.datasets[0].data = [0];
            gravityChart.data.datasets[1].data = [0];
            gravityChart.update();
            return;
        }

        const dates = [];
        const water = [];
        const energy = [];
        let tableRows = '';

        data.forEach(row => {
            dates.push(row.date);
            water.push(row.water_usage);
            energy.push(row.energy_usage);

            // Important: Use strings properly encoded to avoid XSS if data was user generated strings, numbers are fine
            tableRows += `<tr>
                <td>${row.date}</td>
                <td>${row.water_usage}</td>
                <td>${row.energy_usage}</td>
                <td>
                    <button class="btn btn-warning action-btn text-dark shadow-sm me-1" onclick="editRecord(${row.id}, '${row.date}', ${row.water_usage}, ${row.energy_usage})">Edit</button>
                    <button class="btn btn-danger action-btn shadow-sm" onclick="deleteRecord(${row.id})">Delete</button>
                </td>
            </tr>`;
        });

        // Update Table
        dataTableBody.innerHTML = tableRows;

        // Update Chart
        gravityChart.data.labels = dates;
        gravityChart.data.datasets[0].data = water;
        gravityChart.data.datasets[1].data = energy;
        gravityChart.update();
    }

    // Form Submission (Add/Update)
    trackerForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent page reload
        
        const id = recordIdInput.value;
        const date = dateInput.value;
        const water = waterInput.value;
        const energy = energyInput.value;

        // Basic Validation
        if (!date || !water || !energy) {
            showAlert('Please fill all fields.', 'warning');
            return;
        }

        const payload = { date, water, energy };
        let method = 'POST';
        
        if (id) {
            payload.id = id;
            method = 'PUT'; // Use PUT for updating
        }

        toggleLoading(true);

        try {
            const response = await fetch(apiEndpoint, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            toggleLoading(false);

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                resetForm();
                fetchData(); // Refresh data!
            } else {
                showAlert(result.message || 'Error saving data.', 'danger');
            }

        } catch (error) {
            console.error('Submit Error:', error);
            toggleLoading(false);
            showAlert('An error occurred while saving. Please check network connection.', 'danger');
        }
    });

    // Edit Functionality Setup
    window.editRecord = function(id, date, water, energy) {
        // Populate Form
        recordIdInput.value = id;
        dateInput.value = date;
        waterInput.value = water;
        energyInput.value = energy;
        
        // Update UI state
        formTitle.innerText = "Edit Record";
        formTitle.style.color = "#ffb703"; // highlight edit mode
        submitBtn.innerText = "Update Data";
        submitBtn.classList.replace('btn-success', 'btn-warning');
        submitBtn.classList.add('text-dark');
        cancelBtn.style.display = 'inline-block';

        // Scroll to form smoothly
        trackerForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // Reset Form to Add Mode
    window.resetForm = function() {
        trackerForm.reset();
        recordIdInput.value = '';
        formTitle.innerText = "Add New Record";
        formTitle.style.color = "#00f0ff";
        submitBtn.innerText = "Save Data";
        submitBtn.classList.replace('btn-warning', 'btn-success');
        submitBtn.classList.remove('text-dark');
        cancelBtn.style.display = 'none';
    };

    // Delete Sequence
    window.deleteRecord = async function(id) {
        if (!confirm("Are you sure you want to delete this record? This cannot be undone.")) return;

        try {
            const response = await fetch(apiEndpoint, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                fetchData(); // Refresh data
                
                // If the user was editing the deleted record, reset the form
                if (recordIdInput.value == id) {
                    resetForm();
                }
            } else {
                showAlert(result.message || 'Error deleting data.', 'danger');
            }
        } catch (error) {
            console.error('Delete Error:', error);
            showAlert('An error occurred while deleting.', 'danger');
        }
    };

    // On Mount
    document.addEventListener('DOMContentLoaded', () => {
        initChart();
        fetchData();
        
        // Ensure bootstrap js is there for dismissible alerts if not present
        if(typeof bootstrap === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js";
            document.body.appendChild(script);
        }
    });

</script>
</body>
</html>
