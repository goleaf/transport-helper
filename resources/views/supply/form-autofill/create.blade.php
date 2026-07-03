@extends('layouts.app')

@section('title')
Autofill Form From Email
@endsection

@section('content')
@include('supply.emails.autofill', ['email' => $email, 'templates' => $templates])
@endsection
