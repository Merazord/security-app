@extends('layouts.Index')
@section('title', 'Dashboard')

@section('header')
    <div class="container mt-5">

        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <span class="badge bg-secondary ms-2">
            {{ config('app.instance', 'N/A') }}
        </span>
        <h1 class="text-center mb-2">¡Bienvenido, {{ auth()->user()->name }}!</h1>
        <p class="lead text-center text-muted">Este es tu panel de control.</p>

        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Información de usuario</h5>
                        <ul class="list-unstyled mb-0">
                            <li><strong>Nombre:</strong> {{ auth()->user()->name }}</li>
                            <li><strong>Correo:</strong> {{ auth()->user()->email }}</li>
                            <li>
                                <strong>Estado:</strong>
                                @if (auth()->user()->is_active)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Opciones</h5>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
