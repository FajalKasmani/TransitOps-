<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Trip.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Trip;
use Api\Classes\Database;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'driver'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Trip Dispatch Logs</h3>
            <p class="text-muted small mb-0">Track vehicle routing, cargo loading, and scheduling status.</p>
        </div>
        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
            <a href="add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-send-plus-fill"></i> Dispatch New Trip
            </a>
        <?php endif; ?>
    </div>

    <!-- Search Input Filter -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search trips by route, vehicle, driver, status...">
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table dashboard-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Trip ID</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Route Details</th>
                        <th>Cargo Weight (kg)</th>
                        <th>Distance (km)</th>
                        <th>Status</th>
                        <th>Timestamps</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="tripsTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Loading operational trips registry...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted small" id="recordSummary">Showing 0 to 0 of 0 records</div>
        <nav aria-label="Trips pagination">
            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                <!-- Pagination buttons generated dynamically -->
            </ul>
        </nav>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let currentPage = 1;
    let searchTimeout = null;
    const limit = 10;

    const searchInput = document.getElementById("searchInput");
    const tableBody = document.getElementById("tripsTableBody");
    const recordSummary = document.getElementById("recordSummary");
    const paginationControls = document.getElementById("paginationControls");

    function fetchTrips(page = 1, search = "") {
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading...</td></tr>`;
        
        const url = `api_list.php?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`;
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-x-circle me-2"></i>Error: ${data.error}</td></tr>`;
                    return;
                }
                
                renderTable(data.trips, data.role);
                renderPagination(data.page, data.totalPages, data.total);
            })
            .catch(err => {
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-x-circle me-2"></i>Failed to fetch trip logs.</td></tr>`;
            });
    }

    function renderTable(trips, userRole) {
        if (trips.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">No trips recorded matching search.</td></tr>`;
            return;
        }

        let html = "";
        trips.forEach(t => {
            let badgeClass = "bg-secondary";
            if (t.status === 'draft') badgeClass = "bg-secondary";
            else if (t.status === 'dispatched') badgeClass = "bg-primary";
            else if (t.status === 'completed') badgeClass = "bg-success";
            else if (t.status === 'cancelled') badgeClass = "bg-danger";

            let cargoVal = parseFloat(t.cargo_weight).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            let plannedVal = parseFloat(t.planned_distance).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            let actualStr = t.actual_distance !== null ? `<span class="text-success small fw-semibold d-block">Actual: ${parseFloat(t.actual_distance).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} km</span>` : '';

            let actionIcon = userRole === 'driver' ? 'bi-play-circle-fill' : 'bi-pencil';

            let startStr = t.start_time ? `<div>Started: ${t.start_time}</div>` : '';
            let endStr = t.end_time ? `<div>Ended: ${t.end_time}</div>` : '';
            let timeStr = (!startStr && !endStr) ? '-' : `${startStr}${endStr}`;

            html += `
                <tr>
                    <td><strong>#${t.id}</strong></td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(t.vehicle_name)}</div>
                        <span class="text-muted small">${escapeHtml(t.registration_number)}</span>
                    </td>
                    <td>${escapeHtml(t.driver_name)}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(t.source)} <i class="bi bi-arrow-right text-muted px-1"></i> ${escapeHtml(t.destination)}</div>
                    </td>
                    <td>${cargoVal} kg</td>
                    <td>
                        <div>Planned: ${plannedVal} km</div>
                        ${actualStr}
                    </td>
                    <td>
                        <span class="badge ${badgeClass} text-uppercase">${t.status}</span>
                    </td>
                    <td class="small text-muted">${timeStr}</td>
                    <td class="text-end">
                        <a href="edit.php?id=${t.id}" class="btn btn-sm btn-outline-primary">
                            <i class="bi ${actionIcon}"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }

    function renderPagination(page, totalPages, totalRecords) {
        currentPage = page;
        
        const startRecord = totalRecords === 0 ? 0 : (page - 1) * limit + 1;
        const endRecord = Math.min(page * limit, totalRecords);
        recordSummary.textContent = `Showing ${startRecord} to ${endRecord} of ${totalRecords} records`;

        if (totalPages <= 1) {
            paginationControls.innerHTML = "";
            return;
        }

        let html = "";
        
        html += `
            <li class="page-item ${page === 1 ? 'disabled' : ''}">
                <button class="page-link" data-page="${page - 1}" type="button">&laquo; Prev</button>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${page === i ? 'active' : ''}">
                    <button class="page-link" data-page="${i}" type="button">${i}</button>
                </li>
            `;
        }

        html += `
            <li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <button class="page-link" data-page="${page + 1}" type="button">Next &raquo;</button>
            </li>
        `;

        paginationControls.innerHTML = html;

        paginationControls.querySelectorAll(".page-link").forEach(btn => {
            btn.addEventListener("click", function() {
                const targetPage = parseInt(this.getAttribute("data-page"));
                if (targetPage && targetPage !== currentPage) {
                    fetchTrips(targetPage, searchInput.value);
                }
            });
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    searchInput.addEventListener("input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchTrips(1, this.value);
        }, 300);
    });

    fetchTrips(1);
});
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>