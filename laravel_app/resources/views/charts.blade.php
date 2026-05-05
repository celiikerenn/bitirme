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
        box-shadow: 0 1px 3px rgba(0,0,0,0.2), 0 8px 28px rgba(34,197,94,0.06);
        padding: 24px;
    }
    .chart-card h2 {
        margin-top: 0;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        color: var(--txt);
    }
    .chart-card canvas {
        max-height: 320px;
    }
    .chart-empty {
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<h1>Analytics</h1>
<p style="margin-bottom: 0.75rem; color:var(--muted);">
    View charts and spending insights in one place. Use month selector to update category analytics.
</p>

@if(!empty($availableMonths))
    <form method="GET" action="{{ route('charts') }}" style="margin-bottom:1rem; display:flex; gap:0.5rem; align-items:center;">
        <label for="month" style="font-size:0.9rem; color:var(--txt2);">Month:</label>
        <select id="month" name="month" onchange="this.form.submit()"
                style="padding:0.3rem 0.5rem; border-radius:8px; border:1px solid var(--border2); font-size:0.9rem; background:var(--surface2); color:var(--txt);">
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
            <div class="empty-state chart-empty" role="status">
                <div class="empty-state__icon" aria-hidden="true">🥧</div>
                <p class="empty-state__title">No categories to chart</p>
                <p class="empty-state__text">Add an expense for this period to see how spending splits by category.</p>
            </div>
        @else
            <p style="font-size:0.9rem; color:var(--muted); margin-top:0; margin-bottom:0.5rem;">
                Each slice shows the percentage share of total spending per category.
            </p>
            <canvas id="pieChart" height="210"></canvas>
        @endif
    </div>

    <div class="chart-card">
        <h2>Category Comparison (Bar Chart)</h2>
        @if(count($barLabels) === 0)
            <div class="empty-state chart-empty" role="status">
                <div class="empty-state__icon" aria-hidden="true">📊</div>
                <p class="empty-state__title">Nothing to compare yet</p>
                <p class="empty-state__text">Once you log expenses by category, bar totals will appear here.</p>
            </div>
        @else
            <p style="font-size:0.9rem; color:var(--muted); margin-top:0; margin-bottom:0.5rem;">
                Bars display the total amount (₺) spent in each category across all months.
            </p>
            <canvas id="barChart" height="210"></canvas>
        @endif
    </div>

    <div class="chart-card">
        <h2>Monthly Expense Trend (Line Chart)</h2>
        @if(count($lineLabels) < 2)
            <div class="empty-state chart-empty" role="status">
                <div class="empty-state__icon" aria-hidden="true">📈</div>
                <p class="empty-state__title">Trend needs more history</p>
                <p class="empty-state__text">The line chart needs expenses in at least <strong>two different months</strong> to show a trend.</p>
            </div>
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

    /* Yüksek kontrast: yeşil–teal–zeytun ekseninde belirgin ayrım (OKLAB benzeri dağılım) */
    const categoryColors = {
        food: '#22c55e',
        transport: '#14b8a6',
        utilities: '#a3e635',
        grocery: '#059669',
        groceries: '#047857',
        health: '#84cc16',
        entertainment: '#0d9488',
        education: '#2dd4bf',
        clothing: '#65a30d',
        rent: '#15803d',
        other: '#5eead4',
    };

    const fallbackColors = [
        '#22c55e', '#14b8a6', '#a3e635', '#059669', '#84cc16',
        '#0d9488', '#65a30d', '#047857', '#2dd4bf', '#166534',
    ];

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

    const sliceOutline = '#0a0f0d';

    function buildPalette(labels) {
        return labels.map((label, i) => getCategoryColor(label, i));
    }

    function buildBorderArray(len) {
        return Array.from({ length: len }, () => sliceOutline);
    }

    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.color = '#a8d5c3';
        Chart.defaults.borderColor = 'rgba(74, 222, 128, 0.12)';
        Chart.defaults.font.family = 'DM Sans, sans-serif';

        if (pieLabels.length && document.getElementById('pieChart')) {
            const ctxPie = document.getElementById('pieChart').getContext('2d');
            const totalPie = pieData.reduce((sum, v) => sum + Number(v || 0), 0);
            const pieBg = buildPalette(pieLabels);
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: pieBg,
                        borderWidth: 3,
                        borderColor: buildBorderArray(pieLabels.length),
                        hoverBorderWidth: 3,
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#a8d5c3' } },
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
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.12)',
                        pointBackgroundColor: '#4ade80',
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
                                color: 'rgba(74, 222, 128, 0.08)'
                            },
                            ticks: {
                                color: '#a8d5c3'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(74, 222, 128, 0.08)'
                            },
                            ticks: {
                                color: '#a8d5c3'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#a8d5c3'
                            }
                        }
                    }
                }
            });
        }

        if (barLabels.length && document.getElementById('barChart')) {
            const ctxBar = document.getElementById('barChart').getContext('2d');
            const barBg = buildPalette(barLabels);
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Total Amount (₺)',
                        data: barData,
                        backgroundColor: barBg,
                        borderColor: buildBorderArray(barLabels.length),
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(74, 222, 128, 0.08)'
                            },
                            ticks: {
                                color: '#a8d5c3'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(74, 222, 128, 0.08)'
                            },
                            ticks: {
                                color: '#a8d5c3'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#a8d5c3'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

