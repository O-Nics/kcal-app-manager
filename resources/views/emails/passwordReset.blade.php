@extends('layouts.mails')

@section('title', 'Réinitialisation de votre mot de passe')
@section('subtitle', 'Votre mot de passe a été oublié')
@section('preheader', 'Réinitialisez votre mot de passe sur ' . config('app.name'))
@section('action_url', $url)
@section('action_text', 'Réinitialiser mon mot de passe') {{-- texte du bouton --}}
@section('content')
    <p>Bonjour <strong>{{ $user->name }}</strong></p>
    <p>Vous avez oublié votre mot de passe, voici le lien permettant de le réinitialiser.</p>
@endsection
