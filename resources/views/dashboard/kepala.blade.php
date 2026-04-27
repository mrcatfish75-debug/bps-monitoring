<x-app-layout>
<div class="p-6">

    <h1 class="text-2xl font-bold mb-6">Dashboard Kepala BPS</h1>

    <!-- SUMMARY -->
    <div class="grid grid-cols-4 gap-4 mb-6">

        <div class="bg-white p-4 shadow rounded">
            <p class="text-sm text-gray-500">Total Project</p>
            <p class="text-xl font-bold">{{ $totalProject }}</p>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <p class="text-sm text-gray-500">RK Ketua</p>
            <p class="text-xl font-bold">{{ $totalRkKetua }}</p>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <p class="text-sm text-gray-500">RK Anggota</p>
            <p class="text-xl font-bold">{{ $totalRkAnggota }}</p>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <p class="text-sm text-gray-500">Daily Task</p>
            <p class="text-xl font-bold">{{ $totalTask }}</p>
        </div>

    </div>

    <!-- TABLE PROJECT -->
    <table class="w-full border text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Project</th>
                <th class="border p-2">IKU</th>
                <th class="border p-2">Ketua</th>
                <th class="border p-2">Task</th>
            </tr>
        </thead>

        <tbody>
            @foreach($projects as $p)
            <tr>
                <td class="border p-2">{{ $p->name }}</td>

                <td class="border p-2">
                    {{ $p->rkKetua->iku->name ?? '-' }}
                </td>

                <td class="border p-2">
                    {{ $p->leader->name }}
                </td>

                <td class="border p-2">
                    {{ $p->rkAnggotas->sum(fn($rk) => $rk->dailyTasks->count()) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>