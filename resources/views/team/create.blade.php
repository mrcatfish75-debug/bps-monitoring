<x-app-layout>
<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">

    <h1 class="text-xl font-bold mb-4">Buat Tim</h1>

    <form action="{{ route('team.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Nama Tim</label>
            <input type="text" name="name"
                class="w-full border p-2 rounded">
        </div>

        <div class="mb-3">
            <label>Ketua Tim</label>
            <select name="leader_id" class="w-full border p-2 rounded">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Anggota</label>
            <select name="members[]" multiple class="w-full border p-2 rounded">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="bg-blue-500 text-white px-4 py-2 rounded">
            Simpan
        </button>
    </form>

</div>
</x-app-layout>