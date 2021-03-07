@extends('layouts.app')

@section('title', 'Create New Permission - Admin')

@include('admin.sidebar')

@section('content')
    <div>
        <h1 class="font-sans break-normal text-gray-900 pt-6 pb-2 text-xl">
            Create New Permission
        </h1>
        <hr class="border-b border-gray-400">
    </div>

    <div class="pt-5">
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="items-top md:grid md:grid-cols-9 md:space-x-6">
                    <label for="name" class="font-light text-xs md:pt-2 md:text-base">
                        Name
                    </label>
                    <div class="w-full md:col-span-4">
                        <x-inputs.text type="text" class="font-light text-sm" name="name" id="name" value="{{ old('name') }}" :error="$errors->has('name')" required="required" />
                        @error('name')
                            <p class="text-red-500 italic text-xs font-light">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-inputs.button type="submit">Add Permission</x-inputs.button>
                </div>
            </div>
        </form>
    </div>
@endsection