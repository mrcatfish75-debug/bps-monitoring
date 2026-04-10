<x-app-layout>
<div class="p-6">

    <div class="flex justify-between mb-4">
        <h1 class="text-2xl font-bold">Data Tim</h1>

        <a href="{{ route('team.create') }}"
           class="bg-blue-500 text-white px-4 py-2 rounded">
           + Buat Tim
        </a>
    </div>

    <table class="w-full border">
        <thead>
            <tr>
                <th class="border p-2">Nama Tim</th>
                <th class="border p-2">Ketua</th>
            </tr>
        </thead>

        <tbody>
            @foreach($teams as $team)
                <tr>
                    <td class="border p-2">{{ $team->name }}</td>
                    <td class="border p-2">{{ $team->leader->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>