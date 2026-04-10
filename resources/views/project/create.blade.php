<x-app-layout>
<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">

    <h1 class="text-xl font-bold mb-4">Buat Project</h1>

    <form action="{{ route('project.store') }}" method="POST">
        @csrf

        <input name="name" placeholder="Nama Project"
            class="w-full border p-2 mb-3">

        <select name="iku_id" class="w-full border p-2 mb-3">
            @foreach($ikus as $iku)
                <option value="{{ $iku->id }}">{{ $iku->name }}</option>
            @endforeach
        </select>

        <select name="team_id" class="w-full border p-2 mb-3">
            @foreach($teams as $team)
                <option value="{{ $team->id }}">{{ $team->name }}</option>
            @endforeach
        </select>

        <select name="leader_id" class="w-full border p-2 mb-3">
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>

        <select name="members[]" multiple class="w-full border p-2 mb-3">
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>

        <button class="bg-blue-500 text-white px-4 py-2 rounded">
            Simpan
        </button>
    </form>

</div>
</x-app-layout>