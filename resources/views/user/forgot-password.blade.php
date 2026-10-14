@extends('layouts.default')
@section('classe-body', 'login-page bg-body-secondary')
@section('content')
    <main class="login-box">
        <h1 class="login-logo">
            <a href="#"><b>Recuperar Senha</b></a>
        </h1>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">
                    Esqueceu-se da sua senha? Introduza o seu e-mail para receber as instruções de recuperação.
                </p>

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div class="error text-danger mb-2">{{ $error }}</div>
                    @endforeach
                @endif

                <form action="{{ route('password.update') }}" method="post">
                    @csrf
                    
                    <label class="visually-hidden" for="email">Email</label>
                    <div class="input-group mb-3">
                        <input id="email" name="email" type="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required />
                        <div class="input-group-text">
                            <span class="bi bi-envelope"></span>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: center;">
                        <div class="col-12">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enviar link de recuperação</button>
                            </div>
                        </div>
                    </div>
                </form>

                <p class="mt-3 mb-1 text-center">
                    <a href="{{ url('/register') }}">Voltar ao Registo</a>
                </p>
            </div>
        </div>
    </main>
@endsection