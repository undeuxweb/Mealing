@extends('layouts.app')

@section('title', 'Edit Ingredient')

@include('ingredients.sidebar')

@section('content')
    <div class="font-sans">
        <h1 class="font-sans break-normal text-gray-900 pt-6 pb-2 text-xl">Edit Ingredient</h1>
        <hr class="border-b border-gray-400">
    </div>

    <form action="{{ route('ingredients.update', [$ingredient->id]) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="flex flex-wrap my-5 md:items-center">
            <div class="w-full md:w-1/4">
                <label class="block text-gray-500 font-bold mb-1 md:mb-0 pr-4" for="name">
                    Name
                </label>
            </div>
            <div class="w-full md:w-3/4">
                <x-input name="name" type="text" placeholder="Name" :error="$errors->has('name')" value="{{ $ingredient->name }}" required class="md:w-80"></x-input>
                <p class="text-red-500 text-xs italic">
                    {{ $errors->first('name') }}
                </p>
            </div>
        </div>

        <div class="md:flex md:items-center">
            <div class="w-full">
                <x-button type="submit">
                    Submit
                </x-button>
            </div>
        </div>
    
    </form>
@endsection