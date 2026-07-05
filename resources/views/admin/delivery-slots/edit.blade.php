@extends('layouts.admin')
@section('title','Edit Delivery Slot')
@section('admin-content')
<div class="d-flex justify-content-between mb-4"><h1 class="h3">Edit Delivery Slot</h1><a href="{{ route('admin.delivery-slots.index') }}" class="btn btn-outline-secondary">Back</a></div>
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.delivery-slots.update',$deliverySlot) }}" class="row g-3">@csrf @method('PUT') @include('admin.delivery-slots.form', ['slot'=>$deliverySlot])<div class="col-12"><button class="btn btn-success">Update Slot</button></div></form></div></div>
@endsection
