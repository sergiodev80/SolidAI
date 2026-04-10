@extends('filament-panels::layouts.auth')

@section('content')
    <form wire:submit="authenticate" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
        </x-filament::button>
    </form>

    {{-- Link para Colaboradores --}}
    <x-colaborador-login-link />
@endsection
