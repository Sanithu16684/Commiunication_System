<?php
// dashboard.php - Admin Dashboard for Advanced Analysis & Insights
$pageTitle = 'Admin Dashboard & Analytics';
$pageIcon = 'bi-grid-1x2-fill';
require_once __DIR__ . '/includes/header.php';
require_admin();

// Fetch initial server-side stats for zero delay
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$todaySales = $pdo->query("SELECT IFNULL(SUM(total_amount), 0) FROM invoices WHERE DATE(created_at) = '$today' AND status = 'completed'")->fetchColumn();
$todayOrders = $pdo->query("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = '$today' AND status = 'completed'")->fetchColumn();
$monthSales = $pdo->query("SELECT IFNULL(SUM(total_amount), 0) FROM invoices WHERE DATE(created_at) >= '$monthStart' AND status = 'completed'")->fetchColumn();
$monthOrders = $pdo->query("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) >= '$monthStart' AND status = 'completed'")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= min_stock_alert AND item_type = 'physical' AND status = 'active'")->fetchColumn();

// Stock Valuation
$valCost = $pdo->query("SELECT IFNULL(SUM(stock_qty * cost_price), 0) FROM products WHERE item_type = 'physical' AND status = 'active'")->fetchColumn();
$valRetail = $pdo->query("SELECT IFNULL(SUM(stock_qty * selling_price), 0) FROM products WHERE item_type = 'physical' AND status = 'active'")->fetchColumn();

// Estimated Today's Profit
$todayProfit = $pdo->query("SELECT IFNULL(SUM((ii.unit_price - ii.cost_price) * ii.quantity), 0) 
                            FROM invoice_items ii 
                            JOIN invoices i ON ii.invoice_id = i.id 
                            WHERE DATE(i.created_at) = '$today' AND i.status = 'completed'")->fetchColumn();
?>

<div class="page-container">
    
    <!-- Top KPI Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-info">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value"><?= $currency ?> <?= number_format($todaySales, 2) ?></div>
                <div class="stat-desc"><i class="bi bi-cart-check"></i> <?= $todayOrders ?> completed transaction(s)</div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-info">
                <div class="stat-label">Today's Estimated Profit</div>
                <div class="stat-value"><?= $currency ?> <?= number_format($todayProfit, 2) ?></div>
                <div class="stat-desc"><i class="bi bi-graph-up-arrow"></i> Net margin after cost</div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-piggy-bank-fill"></i>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-info">
                <div class="stat-label">This Month's Sales</div>
                <div class="stat-value"><?= $currency ?> <?= number_format($monthSales, 2) ?></div>
                <div class="stat-desc"><i class="bi bi-calendar3"></i> <?= $monthOrders ?> orders this month</div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>

        <div class="stat-card <?= $lowStock > 0 ? 'danger' : 'success' ?>">
            <div class="stat-info">
                <div class="stat-label">Low Stock Alerts</div>
                <div class="stat-value"><?= $lowStock ?></div>
                <div class="stat-desc"><?= $lowStock > 0 ? 'Requires immediate restock' : 'All items sufficiently stocked' ?></div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
        </div>
    </div>

    <!-- Inventory Valuation Banner -->
    <div style="background: linear-gradient(135deg, #1e1b4b, #312e81); border-radius: var(--radius-lg); padding: 20px 28px; color: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: gap; margin-bottom: 28px; box-shadow: var(--shadow-md);">
        <div>
            <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Physical Inventory Valuation</h4>
            <p style="font-size: 13px; color: #c7d2fe;">Total cost value vs expected retail worth of store stock</p>
        </div>
        <div style="display: flex; gap: 32px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #a5b4fc; font-weight: 700;">Cost Valuation</span>
                <div style="font-size: 20px; font-weight: 800; font-family: var(--font-mono);"><?= $currency ?> <?= number_format($valCost, 2) ?></div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #a5b4fc; font-weight: 700;">Retail Valuation</span>
                <div style="font-size: 20px; font-weight: 800; font-family: var(--font-mono); color: #34d399;"><?= $currency ?> <?= number_format($valRetail, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Sales Trend & Category Distribution -->
    <div style="display: grid; grid-template-columns: 2fr 1.1fr; gap: 24px; margin-bottom: 28px;">
        <!-- Sales Trend Chart -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-graph-up" style="color: var(--primary);"></i>
                    <span>Revenue & Profit Performance</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-outline btn-sm active" id="btn-range-7" onclick="switchChartRange('7days')">Last 7 Days</button>
                    <button type="button" class="btn btn-outline btn-sm" id="btn-range-30" onclick="switchChartRange('30days')">Last 30 Days</button>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 320px; position: relative;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Doughnut Breakdown -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-pie-chart-fill" style="color: var(--secondary);"></i>
                    <span>Sales by Category</span>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 320px; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Top Selling Products & Payment Methods -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; margin-bottom: 28px;">
        <!-- Top Selling Items Bar Chart -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-trophy-fill" style="color: #f59e0b;"></i>
                    <span>Top-Selling Books & Products</span>
                </div>
                <span class="badge badge-info">Volume Ranking</span>
            </div>
            <div class="card-body">
                <div style="height: 290px; position: relative;">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Method Distribution -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-credit-card" style="color: var(--accent);"></i>
                    <span>Payment Methods</span>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 290px; position: relative;">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Table -->
    <div class="content-card">
        <div class="card-header-flex">
            <div class="card-title">
                <i class="bi bi-clock-history" style="color: var(--primary);"></i>
                <span>Recent Invoices & Transactions</span>
            </div>
            <a href="invoices.php" class="btn btn-outline btn-sm">
                View All Invoices <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Payment</th>
                        <th>Net Total</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="recent-invoices-tbody">
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">Loading recent sales...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
let trendChartInstance = null;
let categoryChartInstance = null;
let topProdChartInstance = null;
let paymentChartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchAnalyticsData('7days');
});

function switchChartRange(range) {
    document.getElementById('btn-range-7').classList.toggle('active', range === '7days');
    document.getElementById('btn-range-30').classList.toggle('active', range === '30days');
    fetchAnalyticsData(range);
}

async function fetchAnalyticsData(range = '7days') {
    try {
        const res = await fetch(`api/analytics.php?range=${range}`);
        const data = await res.json();

        if (data.status === 'success') {
            renderSalesTrendChart(data.charts.trend);
            renderCategoryChart(data.charts.category_sales);
            renderTopProductsChart(data.charts.top_products);
            renderPaymentMethodChart(data.charts.payment_methods);
            renderRecentInvoices(data.recent_invoices);
        } else {
            console.error('Analytics error:', data.message);
            const tbody = document.getElementById('recent-invoices-tbody');
            if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #ef4444; padding: 20px;">${data.message || 'Error loading dashboard analytics.'}</td></tr>`;
        }
    } catch (e) {
        console.error('Analytics load error:', e);
        const tbody = document.getElementById('recent-invoices-tbody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #ef4444; padding: 20px;">Failed to connect to analytics service.</td></tr>`;
    }
}

function renderSalesTrendChart(trendData) {
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    if (trendChartInstance) trendChartInstance.destroy();

    trendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [
                {
                    label: 'Sales Revenue (Rs.)',
                    data: trendData.sales,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 4
                },
                {
                    label: 'Est. Net Profit (Rs.)',
                    data: trendData.profits,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' } } },
                tooltip: { padding: 12, cornerRadius: 8 }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: "'JetBrains Mono', monospace" } }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

function renderCategoryChart(catData) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    if (categoryChartInstance) categoryChartInstance.destroy();

    const labels = catData.map(c => c.cat_name);
    const values = catData.map(c => parseFloat(c.cat_revenue));
    const colors = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];

    categoryChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['No Sales Yet'],
            datasets: [{
                data: values.length ? values : [1],
                backgroundColor: colors.slice(0, Math.max(1, labels.length)),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" } } }
            },
            cutout: '68%'
        }
    });
}

function renderTopProductsChart(topData) {
    const ctx = document.getElementById('topProductsChart').getContext('2d');
    if (topProdChartInstance) topProdChartInstance.destroy();

    const labels = topData.map(p => p.product_name.length > 20 ? p.product_name.substr(0, 20) + '...' : p.product_name);
    const qtys = topData.map(p => parseInt(p.total_qty));

    topProdChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['None'],
            datasets: [{
                label: 'Units Sold',
                data: qtys.length ? qtys : [0],
                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
}

function renderPaymentMethodChart(payData) {
    const ctx = document.getElementById('paymentMethodChart').getContext('2d');
    if (paymentChartInstance) paymentChartInstance.destroy();

    const labels = payData.map(p => p.payment_method.toUpperCase());
    const counts = payData.map(p => parseInt(p.count));
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'];

    paymentChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels.length ? labels : ['None'],
            datasets: [{
                data: counts.length ? counts : [1],
                backgroundColor: colors.slice(0, Math.max(1, labels.length)),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
            }
        }
    });
}

function renderRecentInvoices(invoices) {
    const tbody = document.getElementById('recent-invoices-tbody');
    if (!tbody) return;

    if (!invoices || invoices.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">No invoices recorded yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = invoices.map(i => `
        <tr>
            <td><strong style="color: var(--primary); font-family: var(--font-mono);">${i.invoice_no}</strong></td>
            <td>${i.created_at}</td>
            <td>${escapeHtml(i.customer_name)}</td>
            <td>${escapeHtml(i.cashier_name || 'Staff')}</td>
            <td><span class="badge badge-neutral">${i.payment_method.toUpperCase()}</span></td>
            <td><strong style="font-family: var(--font-mono);">Rs. ${parseFloat(i.total_amount).toFixed(2)}</strong></td>
            <td>
                <span class="badge ${i.status === 'completed' ? 'badge-success' : (i.status === 'voided' ? 'badge-danger' : 'badge-warning')}">
                    ${i.status.toUpperCase()}
                </span>
            </td>
            <td style="text-align: right;">
                <a href="print_receipt.php?id=${i.id}&format=thermal" target="_blank" class="btn btn-outline btn-sm" title="Print Thermal Receipt">
                    <i class="bi bi-printer"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
