@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Order Menu</h1>
    <p>Branch: {{ request('branch') }}</p>
    <p>Table: {{ request('table') }}</p>
    <p>Token: {{ request('token') }}</p>
    <!-- Add order form here -->
</div>
@endsection