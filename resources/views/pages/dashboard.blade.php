@extends('layouts.app')

@section('title', 'Dashboard')

@section('extra_css')
<style>
    .welcome-banner {
        background: radial-gradient(circle at left, rgba(218, 165, 32, 0.2) 0%, transparent 70%), var(--bg-card);
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid var(--border-color);
    }

    .welcome-text h1 { font-size: 2rem; color: var(--text-primary); margin-bottom: 8px; }
    .welcome-text p { color: var(--text-secondary); font-size: 1rem; }

    .gold-stock-card { text-align: right; }
    .gold-stock-label { font-size: 0.8rem; color: var(--gold-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .gold-stock-value { font-family: 'JetBrains Mono', monospace; font-size: 2.5rem; color: var(--gold-primary); font-weight: 700; }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    
    .stat-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--accent-color), transparent);
    }

    .stat-icon-bg {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 48px;
        height: 48px;
        background-color: rgba(var(--accent-rgb), 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-color);
        font-size: 1.5rem;
    }

    .stat-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    .stat-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; }
    
    .stat-trend {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        margin-top: 12px;
    }
    .trend-up { color: var(--success); }
    .trend-down { color: var(--error); }

    .tables-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 20px;
    }

    .table-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
    }
    
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px; color: var(--gold-primary); font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    td { padding: 14px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    tr:nth-child(even) { background-color: rgba(255, 255, 255, 0.02); }
    tr:hover { background-color: rgba(255, 255, 255, 0.05); }

    .customer-row { display: flex; align-items: center; gap: 12px; }
    .customer-progress { flex: 1; height: 6px; background: var(--bg-surface); border-radius: 3px; overflow: hidden; margin: 0 12px; }
    .progress-bar { height: 100%; background: var(--gold-primary); border-radius: 3px; }

    @media (max-width: 1200px) {
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
        .tables-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .stat-grid { grid-template-columns: 1fr; }
        .welcome-banner { flex-direction: column; text-align: center; gap: 24px; }
        .gold-stock-card { text-align: center; }
    }
</style>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-text">
            <h1>Good Morning, {{ auth()->user()->name }}</h1>
            <p>{{ now()->format('l, jS F Y') }} | <span class="font-urdu">{{ now()->translatedFormat('l، j F Y') }}</span></p>
        </div>
        <div class="gold-stock-card">
            <div class="gold-stock-label">Total Gold Stock</div>
            <div class="gold-stock-value">{{ number_format(\App\Models\Inventory::first()->closing_balance ?? 0, 3) }}<span style="font-size: 1rem; margin-left: 4px;">g</span></div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
        <!-- Card 1: Total Customers -->
        <div class="stat-card" style="--accent-color: #3B82F6; --accent-rgb: 59, 130, 246;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-people"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['total_customers'] }}">0</div>
            <div class="stat-label">Total Customers</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> 12% from last month</div>
        </div>

        <!-- Card 2: Total Invoices -->
        <div class="stat-card" style="--accent-color: #8B5CF6; --accent-rgb: 139, 92, 246;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-receipt"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['total_invoices'] }}">0</div>
            <div class="stat-label">Total Invoices</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> 8% from last month</div>
        </div>

        <!-- Card 3: Total Gold Khalis -->
        <div class="stat-card" style="--accent-color: var(--gold-primary); --accent-rgb: 218, 165, 32;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-box"></i></div>
            <div class="stat-value"><span class="counter" data-value="{{ $stats['total_gold_khalis'] }}" data-decimals="3">0</span><span style="font-size: 1rem;">g</span></div>
            <div class="stat-label">Total Gold Khalis</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> 5% from last month</div>
        </div>

        <!-- Card 4: Total RP Amount -->
        <div class="stat-card" style="--accent-color: #10B981; --accent-rgb: 16, 185, 129;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value">Rs. <span class="counter" data-value="{{ $stats['total_rp_amount'] }}">0</span></div>
            <div class="stat-label">Total RP Amount</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> 15% from last month</div>
        </div>

        <!-- Card 5: Total Grand Total -->
        <div class="stat-card" style="--accent-color: #14B8A6; --accent-rgb: 20, 184, 166;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-calculator"></i></div>
            <div class="stat-value"><span class="counter" data-value="{{ $stats['total_grand_total'] }}" data-decimals="3">0</span>g</div>
            <div class="stat-label">Total Grand Total</div>
            <div class="stat-trend trend-down"><i class="bi bi-arrow-down"></i> 3% from last month</div>
        </div>

        <!-- Card 6: Total Wasooli -->
        <div class="stat-card" style="--accent-color: #06B6D4; --accent-rgb: 6, 182, 212;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-coin"></i></div>
            <div class="stat-value"><span class="counter" data-value="{{ $stats['total_wasooli'] }}" data-decimals="3">0</span>g</div>
            <div class="stat-label">Total Wasooli</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> 20% from last month</div>
        </div>

        <!-- Card 7: Remaining Balance -->
        <div class="stat-card" style="--accent-color: #F59E0B; --accent-rgb: 245, 158, 11;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-balance-scale"></i></div>
            <div class="stat-value {{ $stats['total_remaining_balance'] > 0 ? 'trend-down' : 'trend-up' }}"><span class="counter" data-value="{{ $stats['total_remaining_balance'] }}" data-decimals="3">0</span>g</div>
            <div class="stat-label">Remaining Balance</div>
            <div class="stat-trend {{ $stats['total_remaining_balance'] > 0 ? 'trend-down' : 'trend-up' }}">
                <i class="bi bi-info-circle"></i> {{ $stats['total_remaining_balance'] > 0 ? 'Outstanding' : 'Cleared' }}
            </div>
        </div>

        <!-- Card 8: Today's Invoices -->
        <div class="stat-card" style="--accent-color: #F43F5E; --accent-rgb: 244, 63, 94;">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['today_invoices'] }}">0</div>
            <div class="stat-label">Today's Invoices</div>
            <div class="stat-trend trend-up"><i class="bi bi-clock"></i> Updated just now</div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="tables-grid">
        <!-- Recent Invoices -->
        <div class="table-container">
            <div class="chart-header">
                <div class="chart-title">Recent Invoices</div>
                <a href="{{ route('invoices.index') }}" class="btn-outline" style="padding: 6px 12px; font-size: 0.75rem;">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentInvoices as $invoice)
                        <tr>
                            <td class="font-mono" style="color: var(--gold-primary);">{{ $invoice->formatted_invoice_no }}</td>
                            <td>{{ $invoice->customer->name ?? 'Unknown' }}</td>
                            <td style="color: var(--text-secondary);">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td class="font-mono">{{ number_format($invoice->grand_total, 3) }}g</td>
                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" class="header-icon-btn" style="color: var(--gold-primary);">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top Customers -->
        <div class="table-container">
            <div class="chart-header">
                <div class="chart-title">Top Customers</div>
            </div>
            @php
                $maxBalance = $topCustomers->max(fn($c) => $c->getCurrentBalance()) ?: 1;
            @endphp
            @foreach($topCustomers as $customer)
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 500;">{{ $customer->name }}</span>
                        <span class="font-mono" style="color: var(--error);">{{ number_format($customer->getCurrentBalance(), 3) }}g</span>
                    </div>
                    <div class="customer-progress">
                        <div class="progress-bar" style="width: {{ min(100, ($customer->getCurrentBalance() / $maxBalance) * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Counter Animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-value'));
            const decimals = parseInt(counter.getAttribute('data-decimals') || 0);
            
            gsap.to(counter, {
                innerText: target,
                duration: 1.5,
                ease: 'power2.out',
                onUpdate: function() {
                    counter.innerText = parseFloat(this.targets()[0].innerText).toLocaleString(undefined, {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    });
                }
            });
        });
    });
</script>
@endsection
