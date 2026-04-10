@extends('filament-panels::layouts.auth')

@section('content')
    <form wire:submit="authenticate" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-between">
            <x-filament::button type="submit" class="flex-1">
                {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
            </x-filament::button>
        </div>
    </form>

    {{-- Link para recuperar contraseña --}}
    <div class="text-center">
        <a
            href="{{ route('filament.admin.auth.password-reset.request') }}"
            class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            {{ __('filament-panels::pages/auth/login.form.actions.request-password-reset.label') }}
        </a>
    </div>

    {{-- Link para Colaboradores --}}
    <x-colaborador-login-link />
@endsection
