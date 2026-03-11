@extends('layouts.Index')
@section('title', 'dashboard')
@section('header')
<body>
    <div class="container mt-5">
   <h1 class="text-center">Welcome, {{ auth()->user()->name }}!</h1>
<p class="lead text-center">This is your dashboard!</p>

        <div class="row">
            <div class="col-md-6">
                <h4>User Information</h4>
                <ul>
                    <li><strong>Name:</strong> {{ auth()->user()->name }}</li>
                    <li><strong>Email:</strong> {{ auth()->user()->email }}</li>
                    
                </ul>
            </div>
            <div class="col-md-6">
                <h4>Options</h4>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Log out</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

@endsection