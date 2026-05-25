@extends('layouts.app')

@section('title', 'Dashboard')

@section('extra_css')
<style>
    .welcome-banner {
        background: radial-gradient(circle at left, rgba(218, 165, 32, 0.25) 0%, transparent 70%), var(--bg-card);
        border-radius: 20px;
        padding: 32px 40px;
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid var(--gold-primary);
        box-shadow: var(--shadow-3);
    }

    .welcome-text h1 { font-size: 2.1rem; color: var(--text-primary); margin-bottom: 6px; font-weight: 700; }
    .welcome-text p { color: var(--gold-muted); font-size: 1.05rem; }

    .inventory-card {
        background: linear-gradient(135deg, #1a2521, #0f1a17);
        border: 2px solid var(--gold-primary);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(218,165,32,0.15);
        min-width: 260px;
    }
    .inventory-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2.8rem;
        font-weight: 700;
        color: var(--gold-bright);
        margin: 8px 0;
    }
    .inventory-label {
        font-size: 0.85rem;
        color: var(--success);
        font-weight: 500;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover { 
        transform: translateY(-6px); 
        box-shadow: var(--shadow-3);
    }
    
    .stat-accent {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-color, var(--gold-primary)), transparent);
    }

    .stat-icon-bg {
        position: absolute;
        top: 20px; right: 20px;
        width: 56px; height: 56px;
        background: rgba(var(--accent-rgb, 218,165,32), 0.12);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: var(--accent-color, var(--gold-primary));
        border: 1px solid rgba(var(--accent-rgb, 218,165,32), 0.3);
    }

    .stat-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 12px 0 4px;
    }
    .stat-label { 
        font-size: 0.8rem; 
        color: var(--text-secondary); 
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .type-breakdown {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    .type-pill {
        font-size: 0.73rem;
        padding: 2px 10px;
        border-radius: 9999px;
        font-weight: 600;
    }

    .tables-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .module-link {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px;
        background: var(--bg-surface);
        border-radius: 12px;
        text-decoration: none;
        color: var(--text-body);
        transition: all 0.2s;
        border: 1px solid var(--border-color);
        margin-bottom: 12px;
    }
    .module-link:hover {
        background: rgba(218,165,32,0.1);
        border-color: var(--gold-primary);
        transform: translateX(8px);
    }
</style>
@endsection

@section('content')
    <div class="welcome-banner">
        <div class="welcome-text">
            <h1>Welcome back, {{ auth()->user()->name }} 👋</h1>
            <p>{{ now()->format('l, d F Y') }} • <span class="font-urdu">{{ now()->translatedFormat('l، j F Y') }}</span></p>
        </div>
        
        <div class="inventory-card">
            <div class="inventory-label">TOTAL OPENING STOCK</div>
            <div class="inventory-value">{{ number_format($stats['total_opening_stock'] ?? 0, 3) }}<span style="font-size:1.1rem; opacity:0.7;">g</span></div>
            <small style="color:#4ade80;">Shop Gold + All Parties Opening</small>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--accent-color: #22C55E; --accent-rgb: 34, 197, 94">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['total_invoices'] ?? 0 }}">0</div>
            <div class="stat-label">Total Invoices</div>
            <div class="type-breakdown">
                <span class="type-pill" style="background:#0ea5e9;color:white">Customer</span>
                <span class="type-pill" style="background:#eab308;color:black">Dukandar</span>
            </div>
        </div>

        <div class="stat-card" style="--accent-color: #A78BFA; --accent-rgb: 167, 139, 250">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-box-arrow-in-down"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['total_gold_receipts'] ?? 0 }}">0</div>
            <div class="stat-label">Gold Receipts</div>
            <div style="color:var(--success);font-size:0.85rem;margin-top:8px;">
                +{{ number_format($stats['total_received_khalis'] ?? 0, 2) }}g Khalis
            </div>
        </div>

        <div class="stat-card" style="--accent-color: var(--gold-primary); --accent-rgb: 218, 165, 32">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-coin"></i></div>
            <div class="stat-value">
                <span class="counter" data-value="{{ $stats['total_gold_khalis_given'] ?? 0 }}" data-decimals="2">0</span>g
            </div>
            <div class="stat-label">Gold Given This Year</div>
        </div>

        <div class="stat-card" style="--accent-color: #F43F5E; --accent-rgb: 244, 63, 94">
            <div class="stat-accent"></div>
            <div class="stat-icon-bg"><i class="bi bi-people"></i></div>
            <div class="stat-value counter" data-value="{{ $stats['total_customers'] ?? 0 }}">0</div>
            <div class="stat-label">Total Parties</div>
            <div class="type-breakdown">
                @foreach($stats['customers_by_type'] ?? [] as $type => $count)
                    <span class="type-pill" style="background: {{ $type === 'customer' ? '#3b82f6' : ($type === 'dukandar' ? '#f59e0b' : '#10b981') }}; color:white;">
                        {{ ucfirst(substr($type,0,1)) }}: {{ $count }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="tables-grid">
        <div class="table-container" style="background:var(--bg-card);padding:24px;border-radius:16px;border:1px solid var(--border-color);">
            <h3 style="margin-bottom:16px;color:var(--gold-primary);">Recent Activity</h3>
            @if(!empty($recentActivity))
                <table style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Party</th>
                            <th style="text-align:right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivity as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['type'] }}</td>
                            <td>{{ $item['party'] }}</td>
                            <td style="text-align:right;font-family:monospace;color:{{ $item['color'] }};">{{ $item['amount'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No recent activity.</p>
            @endif
        </div>

        <div>
            <a href="{{ route('invoices.create') }}" class="module-link">
                <i class="bi bi-plus-circle-dotted" style="font-size:2rem;color:var(--gold-primary)"></i>
                <div>
                    <strong>New Invoice</strong><br>
                    <small>Live calculation with ratti & mazdori</small>
                </div>
            </a>
            @if (Route::has('gold-receipts.create'))
                <a href="{{ route('gold-receipts.create') }}" class="module-link">
                    <i class="bi bi-box-arrow-in-down" style="font-size:2rem;color:#4ade80"></i>
                    <div>
                        <strong>New Gold Receipt</strong><br>
                        <small>Record pure khalis received from party</small>
                    </div>
                </a>
            @endif
            <a href="{{ route('inventory.index') }}" class="module-link">
                <i class="bi bi-graph-up-arrow" style="font-size:2rem;color:#a5b4fc"></i>
                <div>
                    <strong>Inventory Report</strong><br>
                    <small>Full movement + opening stock</small>
                </div>
            </a>
        </div>
    </div>
@endsection

@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.counter').forEach(el => {
            const target = parseFloat(el.getAttribute('data-value') || 0);
            const decimals = parseInt(el.getAttribute('data-decimals') || 0);
            gsap.to(el, {
                innerText: target,
                duration: 1.6,
                ease: "power2.out",
                onUpdate: function() {
                    this.targets()[0].innerText = Number(this.targets()[0].innerText).toFixed(decimals);
                }
            });
        });
    });
</script>
@endsection
