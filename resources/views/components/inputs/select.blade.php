<select {!! $attributes->merge(['class' => 'bg-gray-200 border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-blue-500']) !!}>
    {{ $slot }}
</select>