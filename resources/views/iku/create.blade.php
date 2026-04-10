<x-app-layout>
<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">

    <h1 class="text-xl font-bold mb-4">Tambah IKU</h1>

    @if($errors->any())
        <div class="bg-red-200 p-2 mb-4 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('iku.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label>Kode IKU</label>
            <input type="text" name="code"
                class="w-full border rounded p-2">
        </div>

        <div>
            <label>Nama IKU</label>
            <input type="text" name="name"
                class="w-full border rounded p-2">
        </div>

        <div>
            <label>Deskripsi</label>
            <textarea name="description"
                class="w-full border rounded p-2"></textarea>
        </div>

        <button class="bg-blue-500 text-white px-4 py-2 rounded">
            Simpan
        </button>
    </form>

</div>
</x-app-layout>