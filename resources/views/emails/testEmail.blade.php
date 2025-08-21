@extends('layouts.mails')

@section('title', 'Votre programme calorique est prêt')
@section('subtitle', 'Objectifs quotidiens et macros personnalisées')
@section('preheader', 'Découvrez vos kcal et macros recommandées.')
@section('action_url', "https://okj.com")
@section('action_text', 'Voir mon programme') {{-- texte du bouton --}}


@section('content')
    <p>Bonjour dddd,</p>
    <p>Votre objectif quotidien est de <strong>547887 kcal</strong>.</p>
@endsection
