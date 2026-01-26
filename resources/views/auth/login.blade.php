@extends('component.layout')
@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-4" style="width:400px;">
        <h4 class="text-center mb-3">Login</h4>

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
@endsection
