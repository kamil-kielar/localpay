@extends('layouts.app')
@section('title', 'Rejestracja — LokalPay Pro')
@section('content')
<main class="auth-shell"><a class="auth-brand" href="{{ route('home') }}"><span class="brand-mark">L</span> LokalPay Pro</a><section class="auth-card"><h1>Utwórz konto</h1><p>Wybrany plan: <strong>{{ ucfirst($selectedPlan) }}</strong>. Płatny pakiet wybierzesz po potwierdzeniu e-maila.</p>
<form method="post" action="{{ route('register') }}">@csrf
<input type="hidden" name="selected_plan" value="{{ $selectedPlan }}">
@foreach([['name','Imię i nazwisko','text'],['organization_name','Nazwa organizacji','text'],['email','E-mail','email'],['phone','Telefon (opcjonalnie, bez SMS)','tel']] as $field)<label class="form-label mt-2">{{ $field[1] }}<input class="form-control" type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" {{ $field[0] !== 'phone' ? 'required' : '' }}></label>@error($field[0])<div class="text-danger small">{{ $message }}</div>@enderror@endforeach
<label class="form-label mt-2">Hasło<input class="form-control" type="password" name="password" required autocomplete="new-password"></label><label class="form-label mt-2">Powtórz hasło<input class="form-control" type="password" name="password_confirmation" required></label><button class="btn btn-lime w-100 mt-4">Utwórz konto</button></form><p class="mt-4 mb-0">Masz konto? <a href="{{ route('login') }}">Zaloguj się</a></p></section></main>
@endsection
