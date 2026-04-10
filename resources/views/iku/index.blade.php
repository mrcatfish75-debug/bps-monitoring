<x-app-layout>
<div class="p-6">

    <div class="flex justify-between mb-4">
        <h1 class="text-2xl font-bold">Data IKU</h1>

        <a href="{{ route('iku.create') }}"
           class="bg-blue-500 text-white px-4 py-2 rounded">
           + Tambah IKU
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-200 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border">Kode</th>
                <th class="p-2 border">Nama</th>
                <th class="p-2 border">Deskripsi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($ikus as $iku)
                <tr>
                    <td class="p-2 border">{{ $iku->code }}</td>
                    <td class="p-2 border">{{ $iku->name }}</td>
                    <td class="p-2 border">{{ $iku->description }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center p-4">
                        Belum ada data
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
</x-app-layout>