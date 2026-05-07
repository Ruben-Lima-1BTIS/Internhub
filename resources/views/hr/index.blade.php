@extends('layouts.app')

@section('content')


@foreach($users as $user)
<div class="flex gap-3 border p-1 rounded">
    <p>{{ $user->email }}</p>
    <p>{{ $user->role }}</p>
    <p> {{ $user->name }}</p>
</div>

@endforeach

@endsection