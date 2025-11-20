<x-guest-layout>
    <div class="login-box">
        <div class="login-logo">
            <a href="{{ url('/') }}"><strong>Uni</strong>Match</a>
        </div>
        <div class="card card-outline card-brand">
            <div class="card-header text-center border-0 pb-0">
                <h3 class="mb-1">Bienvenida/o</h3>
                <p class="text-muted mb-0">Accede con tu correo institucional</p>
            </div>
            <div class="card-body login-card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-group mb-3">
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="Correo institucional"
                            required
                            autofocus
                            autocomplete="username"
                        >
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="input-group mb-3">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-6">
                            <div class="icheck-brand">
                                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">Recordarme</label>
                            </div>
                        </div>
                        <div class="col-6 text-right">
                            <button type="submit" class="btn btn-brand btn-block">Ingresar</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm">¿Olvidaste tu contraseña?</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm">Crear cuenta</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
