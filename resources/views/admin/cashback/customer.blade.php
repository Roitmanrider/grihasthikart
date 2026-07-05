@extends('layouts.admin')
@section('title','Customer Cashback')
@section('admin-content')
<h1 class="h3 mb-4">{{ $customer->name }} Cashback</h1>
<div class="alert alert-light border">Current balance: <strong>Rs. {{ number_format($balance,2) }}</strong></div>
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white fw-semibold">Ledger</div><div class="card-body">@forelse($ledgers as $ledger)<div class="border-bottom pb-2 mb-2">{{ $ledger->ledger_type }} Rs. {{ number_format((float)$ledger->amount,2) }} / Balance Rs. {{ number_format((float)$ledger->balance_after,2) }}</div>@empty<div class="text-muted">No ledger entries.</div>@endforelse</div></div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Monthly Summaries</div><div class="card-body">@forelse($summaries as $summary)<div>{{ $summary->month }}/{{ $summary->year }} - {{ $summary->eligibility_status }} - Rs. {{ number_format((float)$summary->cashback_amount,2) }}</div>@empty<div class="text-muted">No summaries.</div>@endforelse</div></div>
@endsection
