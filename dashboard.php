<?php
include_once __DIR__ . '/controllers/it_tickets_dashboard_controller.php';
include_once __DIR__ . '/api/photo_helper.php';
include_once __DIR__ . '/includes/layout.php';

renderPageStart('ITicketHub - Dashboard');
renderTopNav('dashboard', $userRole);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<!-- MAIN LAYOUT: Left Content + Right Panel -->
<div class="main-layout">

    <!-- LEFT: Main Content Area -->
    <main class="content-area">
        <div class="page-header">
            <h1>Analytics Dashboard</h1>
        </div>

        <!-- Summary Cards Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon open"><i class="fas fa-folder-open"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $openCount; ?></span>
                    <span class="stat-label">Open</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon assigned"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $assignedCount; ?></span>
                    <span class="stat-label">Assigned</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon urgent"><i class="fas fa-fire"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $urgentCount; ?></span>
                    <span class="stat-label">Urgent</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon closed"><i class="fas fa-lock"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $closedCount; ?></span>
                    <span class="stat-label">Closed</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-value">
                        <?php echo $completedCount; ?>
                    </span>
                    <span class="stat-label">Completed</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="charts-row">
                <div class="chart-card" id="monthlyTicketsChart">
                    <h3>Total Tickets per Month This Year</h3>
                    <canvas id="monthlyTicketsCanvas" width="400" height="200"></canvas>
                </div>

                <div class="chart-card" id="departmentRequestsChart">
                    <h3>Total Requests per Department This Month</h3>
                    <canvas id="departmentRequestsCanvas" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="date-filter-container">
                <label for="ticketsPerDayDateFrom">From:</label>
                <input type="date" id="ticketsPerDayDateFrom" name="date_from" value="<?php echo date('Y-01-01'); ?>">

                <label for="ticketsPerDayDateTo">To:</label>
                <input type="date" id="ticketsPerDayDateTo" name="date_to" value="<?php echo date('Y-m-d'); ?>">

                <button id="filterTicketsPerDay" class="filter-button">Apply Filter</button>
            </div>

            <div class="charts-row full-width">
                <div class="chart-card" id="ticketsPerDayChart">
                    <canvas id="ticketsPerDayCanvas" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <style>
            .charts-section {
                margin-top: 20px;
            }

            .charts-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 20px;
            }

            .charts-row.full-width {
                grid-template-columns: 1fr;
            }

            .chart-card {
                background: white;
                border-radius: 16px;
                padding: 20px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            }

            .chart-card h3 {
                margin-bottom: 15px;
                color: #1e293b;
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .date-filter-container {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                gap: 15px;
                background: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }

            .date-filter-container label {
                color: #64748b;
                font-size: 13px;
                font-weight: 500;
            }

            .date-filter-container input[type="date"] {
                padding: 10px 15px;
                border: 2px solid #e5e7eb;
                border-radius: 10px;
                font-size: 14px;
                transition: all 0.2s ease;
            }

            .date-filter-container input[type="date"]:focus {
                outline: none;
                border-color: #ec4899;
                box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
            }

            .filter-button {
                padding: 10px 20px;
                background: linear-gradient(135deg, #ec4899, #db2777);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                transition: all 0.3s ease;
            }

            .filter-button:hover {
                background: linear-gradient(135deg, #db2777, #be185d);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
            }

            .download-chart-btn {
                display: inline-block;
                margin-top: 10px;
                padding: 8px 16px;
                background: linear-gradient(135deg, #ec4899, #db2777);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .download-chart-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(236, 72, 153, 0.3);
            }

            .download-chart-btn i {
                margin-right: 5px;
            }

            @media (max-width: 900px) {
                .charts-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </main> <!-- End content-area -->

    <?php renderRightPanel($userRole, 'dashboard'); ?>

</div> <!-- End main-layout -->

<?php renderFooter(); ?>

<script>
    var userRole = '<?php echo $userRole; ?>';
    var user = '<?php echo $username; ?>';
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Existing filter functionality
        const filterButton = document.getElementById('filterTicketsPerDay');
        const dateFromInput = document.getElementById('ticketsPerDayDateFrom');
        const dateToInput = document.getElementById('ticketsPerDayDateTo');

        // Set initial date values from PHP
        dateFromInput.value = ticketsPerDayDateFrom;
        dateToInput.value = ticketsPerDayDateTo;

        filterButton.addEventListener('click', function () {
            const dateFrom = dateFromInput.value;
            const dateTo = dateToInput.value;

            // Reload the page with new date parameters
            window.location.href = `dashboard.php?date_from=${dateFrom}&date_to=${dateTo}`;
        });

        // Data for the charts
        const monthlyTicketsData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
                'September', 'October', 'November', 'December'
            ],
            datasets: [{
                label: 'Created Tickets',
                data: createdCounts,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',  // Blue
                    'rgba(75, 192, 192, 0.6)',  // Green
                    'rgba(255, 205, 86, 0.6)',  // Yellow
                    'rgba(153, 102, 255, 0.6)', // Purple
                    'rgba(255, 159, 64, 0.6)',  // Orange
                    'rgba(0, 200, 200, 0.6)',   // Teal
                    'rgba(54, 162, 235, 0.6)',  // Blue
                    'rgba(75, 192, 192, 0.6)',  // Green
                    'rgba(255, 205, 86, 0.6)',  // Yellow
                    'rgba(153, 102, 255, 0.6)', // Purple
                    'rgba(255, 159, 64, 0.6)',  // Orange
                    'rgba(0, 200, 200, 0.6)'    // Teal
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(0, 200, 200, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(0, 200, 200, 1)'
                ],
                borderWidth: 1
            }]
        };

        const departmentRequestsData = {
            labels: departments,
            datasets: [{
                label: 'Requests',
                data: requestCounts,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 205, 86, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(0, 200, 200, 0.6)',
                    'rgba(201, 203, 207, 0.6)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(0, 200, 200, 1)',
                    'rgba(201, 203, 207, 1)'
                ],
                borderWidth: 1
            }]
        };

        const ticketsPerDayData = {
            labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            datasets: [{
                label: 'Tickets Closed',
                data: ticketsPerDayCounts,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 205, 86, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(0, 200, 200, 0.6)',
                    'rgba(201, 203, 207, 0.6)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(0, 200, 200, 1)',
                    'rgba(201, 203, 207, 1)'
                ],
                borderWidth: 1
            }]
        };

        // Common configuration for data labels
        const dataLabelsConfig = {
            display: true,
            color: 'black',
            anchor: 'end',
            align: 'end',
            offset: -2,
            font: {
                weight: 'bold',
                size: 16
            },
            formatter: Math.round
        };

        // Create the monthly tickets chart (Bar Chart)
        const ctxMonthly = document.getElementById('monthlyTicketsCanvas').getContext('2d');
        const monthlyTicketsChart = new Chart(ctxMonthly, {
            type: 'bar',
            data: monthlyTicketsData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Tickets'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Months'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Created Tickets per Month This Year'
                    },
                    datalabels: dataLabelsConfig
                }
            },
            plugins: [ChartDataLabels]
        });

        // Create the department requests chart (Bar Chart)
        const ctxDepartment = document.getElementById('departmentRequestsCanvas').getContext('2d');
        const departmentRequestsChart = new Chart(ctxDepartment, {
            type: 'bar',
            data: departmentRequestsData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Requests'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Departments'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Total Requests per Department This Month'
                    },
                    datalabels: dataLabelsConfig
                }
            },
            plugins: [ChartDataLabels]
        });

        // Create tickets per day chart
        const ctxTicketsPerDay = document.getElementById('ticketsPerDayCanvas').getContext('2d');
        const ticketsPerDayChart = new Chart(ctxTicketsPerDay, {
            type: 'bar',
            data: ticketsPerDayData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Tickets Closed'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: `Tickets Closed per Day (${ticketsPerDayDateFrom} to ${ticketsPerDayDateTo})`
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: `Tickets Closed per Day (${ticketsPerDayDateFrom} to ${ticketsPerDayDateTo})`,
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    },
                    datalabels: dataLabelsConfig
                }
            },
            plugins: [ChartDataLabels]
        });

        // Modify chart download function to ensure white background
        function addChartDownloadButton(chartId, chartCanvasId) {
            const chartContainer = document.getElementById(chartId);
            const downloadButton = document.createElement('button');
            downloadButton.innerHTML = '<i class="fas fa-download"></i> Save';
            downloadButton.classList.add('download-chart-btn');

            downloadButton.addEventListener('click', function () {
                const canvas = document.getElementById(chartCanvasId);

                // Create a new canvas with white background
                const downloadCanvas = document.createElement('canvas');
                downloadCanvas.width = canvas.width;
                downloadCanvas.height = canvas.height;
                const downloadCtx = downloadCanvas.getContext('2d');

                // Fill with white background
                downloadCtx.fillStyle = 'white';
                downloadCtx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);

                // Draw the original chart onto the new canvas
                downloadCtx.drawImage(canvas, 0, 0);

                // Convert to data URL and download
                const imageURL = downloadCanvas.toDataURL('image/jpg');
                const link = document.createElement('a');
                link.download = `${chartId}_chart.jpg`;
                link.href = imageURL;
                link.click();
            });

            chartContainer.appendChild(downloadButton);
        }

        // Add download buttons to existing charts
        addChartDownloadButton('monthlyTicketsChart', 'monthlyTicketsCanvas');
        addChartDownloadButton('departmentRequestsChart', 'departmentRequestsCanvas');
        addChartDownloadButton('ticketsPerDayChart', 'ticketsPerDayCanvas');
    });
</script>

<?php
renderPageEnd();
$conn = null;
?>