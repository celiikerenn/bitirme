@extends('layouts.app')

@section('title', 'Analytics')

@push('styles')
<style>
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
    }
    .chart-card {
        background: var(--surface);
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(37,99,235,0.06);
        padding: 24px;
    }
    .chart-card h2 {
        margin-top: 0;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        color: #1e293b;
    }
    .chart-card canvas {
        max-height: 320px;
    }
</style>
@endpush

@section('content')
<h1>Analytics</h1>
<p style="margin-bottom: 0.75rem; color:#64748b;">
    View charts and spending insights in one place. Use month selector to update category analytics.
</p>

@if(!empty($availableMonths))
    <form method="GET" action="{{ route('charts') }}" style="margin-bottom:1rem; display:flex; gap:0.5rem; align-items:center;">
        <label for="month" style="font-size:0.9rem; color:#374151;">Month:</label>
        <select id="month" name="month" onchange="this.form.submit()"
                style="padding:0.3rem 0.5rem; border-radius:8px; border:1px solid #cbd5f5; font-size:0.9rem;">
            @foreach($availableMonths as $month)
                <option value="{{ $month }}" {{ $selectedMonth === $month ? 'selected' : '' }}>
                    {{ $month }}
                </option>
            @endforeach
        </select>
    </form>
@endif

<div class="charts-grid">
    <div class="chart-card" style="grid-column:1 / -1;">
        <h2>Spending Insights</h2>
        <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:12px;">
            <div style="background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:12px;">
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em;">Monthly average</div>
                <div class="number-value" style="font-size:20px; font-weight:600;">{{ number_format($monthlyAverage ?? 0, 2, ',', '.') }} ₺</div>
            </div>
            <div style="background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:12px;">
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em;">Anomaly threshold</div>
                <div class="number-value" style="font-size:20px; font-weight:600;">{{ number_format($anomalyThreshold ?? 0, 2, ',', '.') }} ₺</div>
            </div>
            <div style="background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:12px;">
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em;">Anomaly months</div>
                <div class="number-value" style="font-size:20px; font-weight:600;">{{ count($anomalyMonths ?? []) }}</div>
            </div>
        </div>
        @if(!empty($insights))
            <ul style="margin:0 0 0.75rem 1rem; padding:0; color:var(--txt2);">
                @foreach($insights as $insight)
                    <li style="margin-bottom:0.35rem;">{{ $insight }}</li>
                @endforeach
            </ul>
        @endif
        @if(!empty($recommendations))
            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.35rem;">Recommendations</div>
            <ul style="margin:0 0 0 1rem; padding:0; color:var(--txt2);">
                @foreach($recommendations as $rec)
                    <li style="margin-bottom:0.25rem;">{{ $rec }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="chart-card">
        <h2>Category Distribution (Pie Chart)</h2>
        @if(count($pieLabels) === 0)
            <p>No data to display yet. Please add an expense.</p>
        @else
            <p style="font-size:0.9rem; color:#6b7280; margin-top:0; margin-bottom:0.5rem;">
                Each slice shows the percentage share of total spending per category.
            </p>
            <canvas id="pieChart" height="210"></canvas>
        @endif
    </div>

    <div class="chart-card">
        <h2>Category Comparison (Bar Chart)</h2>
        @if(count($barLabels) === 0)
            <p>No data to display yet. Please add an expense.</p>
        @else
            <p style="font-size:0.9rem; color:#6b7280; margin-top:0; margin-bottom:0.5rem;">
                Bars display the total amount (₺) spent in each category across all months.
            </p>
            <canvas id="barChart" height="210"></canvas>
        @endif
    </div>

    <div class="chart-card">
        <h2>Monthly Expense Trend (Line Chart)</h2>
        @if(count($lineLabels) < 2)
            <p>Trend chart requires at least <strong>2 different months</strong> of data.</p>
        @else
            <canvas id="lineChart" height="210"></canvas>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const pieLabels = @json($pieLabels);
    const pieData = @json($pieData);
    const lineLabels = @json($lineLabels);
    const lineData = @json($lineData);
    const barLabels = @json($barLabels);
    const barData = @json($barData);

    // All Expenses sayfasındaki badge renkleriyle birebir eşleme
    const categoryColors = {
        food: '#2563eb',
        yemek: '#2563eb',
        transport: '#10b981',
        ulasim: '#10b981',
        'ulaşım': '#10b981',
        bills: '#f59e0b',
        fatura: '#f59e0b',
        groceries: '#16a34a',
        market: '#16a34a',
        shopping: '#16a34a',
        health: '#ef4444',
        saglik: '#ef4444',
        'sağlık': '#ef4444',
        entertainment: '#a855f7',
        eglence: '#a855f7',
        'eğlence': '#a855f7',
        education: '#0284c7',
        egitim: '#0284c7',
        'eğitim': '#0284c7',
        clothing: '#ea580c',
        giyim: '#ea580c',
        rent: '#db2777',
        kira: '#db2777',
        other: '#64748b',
    };

    const fallbackColors = ['#2563eb', '#10b981', '#f59e0b', '#16a34a', '#ef4444', '#a855f7', '#0284c7', '#ea580c', '#64748b', '#db2777'];

    function normalizeText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/ı/g, 'i');
    }

    function getCategoryColor(label, index) {
        const key = normalizeText(label);
        for (const token in categoryColors) {
            if (key.includes(token)) {
                return categoryColors[token];
            }
        }
        return fallbackColors[index % fallbackColors.length];
    }

    function buildPalette(labels) {
        return labels.map((label, i) => getCategoryColor(label, i));
    }

    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(99,120,255,0.08)';
        Chart.defaults.font.family = 'DM Sans, sans-serif';

        if (pieLabels.length && document.getElementById('pieChart')) {
            const ctxPie = document.getElementById('pieChart').getContext('2d');
            const totalPie = pieData.reduce((sum, v) => sum + Number(v || 0), 0);
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: buildPalette(pieLabels),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#94a3b8' } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = Number(context.raw || 0);
                                    const percent = totalPie > 0 ? (value * 100 / totalPie) : 0;
                                    const percentStr = percent.toLocaleString('tr-TR', { maximumFractionDigits: 1 });
                                    return label + ': ' + percentStr + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        if (lineLabels.length && document.getElementById('lineChart')) {
            const ctxLine = document.getElementById('lineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: lineLabels,
                    datasets: [{
                        label: 'Total Amount (₺)',
                        data: lineData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.06)',
                        pointBackgroundColor: '#2563eb',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.04)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.04)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#94a3b8'
                            }
                        }
                    }
                }
            });
        }

        if (barLabels.length && document.getElementById('barChart')) {
            const ctxBar = document.getElementById('barChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Total Amount (₺)',
                        data: barData,
                        backgroundColor: buildPalette(barLabels),
                        borderWidth: 0,
                        borderRadius: 6,
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.04)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.04)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#94a3b8'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

