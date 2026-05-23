@extends('layouts.app')

@section('title', 'Customer Profile')

@section('extra_css')
<style>
    .customer-hero {
        background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-sidebar) 100%);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 32px;
        display: flex;
        align-items: center;
        gap: 32px;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
    }
    .customer-hero::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(218, 165, 32, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .hero-avatar {
        width: 100px;
        height: 100px;
        background-color: var(--gold-deep);
        color: var(--gold-primary);
        border: 2px solid var(--gold-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        box-shadow: 0 0 20px rgba(218, 165, 32, 0.2);
    }

    .hero-info { flex: 1; }
    .hero-info h1 { font-size: 2.5rem; color: var(--text-primary); margin-bottom: 8px; }
    .hero-details { display: flex; gap: 24px; color: var(--text-secondary); font-size: 0.95rem; }
    .hero-details span { display: flex; align-items: center; gap: 8px; }

    .stat-mini-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }
    .stat-mini-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease;
    }
    .stat-mini-card:hover { transform: translateY(-5px); }
    .stat-mini-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .stat-mini-value { font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }

    .table-section {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .section-title { font-family: 'Playfair Display', serif; font-size: 1.25rem; color: var(--text-primary); }

    @media (max-width: 768px) {
        .customer-hero { flex-direction: column; text-align: center; }
        .hero-details { flex-direction: column; gap: 12px; align-items: center; }
        .stat-mini-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
    <!-- Hero Card -->
    <div class="customer-hero">
        <div class="hero-avatar">
            {{ substr($customer->name, 0, 1) }}
        </div>
        <div class="hero-info">
            <h1>{{ $customer->name }}</h1>
            <div class="hero-details">
                <span><i class="bi bi-telephone"></i> {{ $customer->phone ?: 'No phone' }}</span>
                <span><i class="bi bi-geo-alt"></i> {{ $customer->city ?: 'No city' }}</span>
                <span><i class="bi bi-card-text"></i> {{ $customer->cnic ?: 'No CNIC' }}</span>
            </div>
            <div style="margin-top: 12px; color: var(--text-muted); font-size: 0.85rem;">
                <i class="bi bi-house-door"></i> {{ $customer->address ?: 'No address provided' }}
            </div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('customers.edit', $customer) }}" class="btn-gold">
                <i class="bi bi-pencil"></i> Edit Profile
            </a>
        </div>
    </div>

    <!-- Stat Mini Cards -->
    <div class="stat-mini-grid">
        <div class="stat-mini-card">
            <div class="stat-mini-label">Total Invoices</div>
            <div class="stat-mini-value">{{ $customer->invoices()->count() }}</div>
        </div>
        <div class="stat-mini-card">
            <div class="stat-mini-label">Total Gold Given</div>
            <div class="stat-mini-value">{{ number_format($customer->getTotalGoldKhalis(), 3) }}g</div>
        </div>
        <div class="stat-mini-card" style="border-bottom: 3px solid {{ $customer->getCurrentBalance() > 0 ? 'var(--error)' : 'var(--success)' }};">
            <div class="stat-mini-label">Outstanding Balance</div>
            <div class="stat-mini-value" style="color: {{ $customer->getCurrentBalance() > 0 ? 'var(--error)' : 'var(--success)' }};">
                {{ number_format($customer->getCurrentBalance(), 3) }}g
            </div>
        </div>
    </div>

    <!-- Recent Invoices Table -->
    <div class="table-section">
        <div class="section-header">
            <h2 class="section-title">Recent Invoices</h2>
            <a href="{{ route('customers.ledger', $customer) }}" class="btn-outline">
                <i class="bi bi-book"></i> View Full Ledger
            </a>
        </div>
        
        @if($customer->invoices->isEmpty())
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <p>No invoices found for this customer.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Gold Khalis</th>
                            <th>Grand Total</th>
                            <th>Wasooli</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->invoices()->latest('invoice_date')->limit(10)->get() as $invoice)
                            <tr>
                                <td class="font-mono" style="color: var(--gold-primary);">{{ $invoice->formatted_invoice_no }}</td>
                                <td style="color: var(--text-secondary);">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td class="font-mono">{{ number_format($invoice->gold_khalis, 3) }}g</td>
                                <td class="font-mono">{{ number_format($invoice->grand_total, 3) }}g</td>
                                <td class="font-mono">{{ number_format($invoice->wasooli, 3) }}g</td>
                                <td class="font-mono" style="color: {{ $invoice->remaining_balance > 0 ? 'var(--error)' : 'var(--success)' }};">
                                    {{ number_format($invoice->remaining_balance, 3) }}g
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $invoice->status }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}" class="action-btn btn-view">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
