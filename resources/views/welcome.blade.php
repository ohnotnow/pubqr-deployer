@extends('layouts.app')

@section('content')
    <div class="absolute top-0 right-0 p-8">
        <a href="{{ route('help') }}">Help?</a>
    </div>
    <div class="flex flex-col justify-center min-h-screen py-12 bg-gray-50 sm:px-6 lg:px-8">
                <div class="space-y-6 justify-center">
                    <a href="{{ route('home') }}">
                        <x-logo class="w-auto h-16 mx-auto text-indigo-600" />
                    </a>

                    <h1 class="text-5xl font-extrabold tracking-wider text-center text-gray-600">
                        {{ config('app.name') }}
                    </h1>

                    <div class="">
                        @livewire('deployment-form')
                    </div>
                </div>
    </div>
@endsection
