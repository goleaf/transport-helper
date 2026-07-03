@extends('layouts.app')

@section('title')
{{ $template->name }}
@endsection

@section('content')
<x-supply.form-template-show :template="$template" :field-types="$fieldTypes" />
@endsection
