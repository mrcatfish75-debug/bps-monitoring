<x-app-layout>
<div class="p-6">

    <div class="flex justify-between mb-4">
        <h1 class="text-2xl font-bold">Data Project</h1>

        <a href="{{ route('project.create') }}"
           class="bg-blue-500 text-white px-4 py-2 rounded">
           + Buat Project
        </a>
    </div>

    <table class="w-full border">
        <thead>
            <tr>
                <th class="border p-2">Nama</th>
                <th class="border p-2">IKU</th>
                <th class="border p-2">Tim</th>
                <th class="border p-2">Ketua</th>
                <th class="border p-2">Status</th>
            </tr>
        </thead>

        <tbody>
            @foreach($projects as $p)
            <tr>
                <td class="border p-2">{{ $p->name }}</td>
                <td class="border p-2">{{ $p->iku->name }}</td>
                <td class="border p-2">{{ $p->team->name }}</td>
                <td class="border p-2">{{ $p->leader->name }}</td>
                <td class="border p-2">{{ $p->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>