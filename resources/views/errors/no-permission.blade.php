@extends('component.layout')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-4 text-center">
        <h3 class="text-danger">No Permission</h3>
        <p>You do not have permission to access this page.</p>
        <a href="{{ route('dashboard.index') }}" class="btn btn-primary">Go Back</a>
    </div>
</div>
@endsection
