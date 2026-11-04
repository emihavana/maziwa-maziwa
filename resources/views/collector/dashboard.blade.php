@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">

    @if (session('message'))
    <div class="mb-6 rounded-lg border border-slate-200 bg-emerald-50 px-4 py-3 text-sm text-slate-900 shadow-sm">
        {{ session('message') }}
    </div>
    @endif

    <livewire:collector-dashboard />
</div>
@endsection