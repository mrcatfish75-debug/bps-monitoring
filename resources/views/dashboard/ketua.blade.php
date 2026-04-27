<x-app-layout>
<div class="p-6">

    <h1 class="text-2xl font-bold mb-6">
        Dashboard Ketua Tim
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- RK KETUA -->
        <a href="{{ route('rk-ketua.index') }}"
           class="group bg-blue-500 text-white p-6 rounded-xl shadow hover:bg-blue-600 transition">

            <div class="text-3xl mb-2">🎯</div>

            <h2 class="text-lg font-semibold">RK Ketua</h2>
            <p class="text-sm opacity-80">Kelola target kerja</p>

        </a>

        <!-- PROJECT -->
        <a href="{{ route('project.index') }}"
           class="group bg-green-500 text-white p-6 rounded-xl shadow hover:bg-green-600 transition">

            <div class="text-3xl mb-2">📁</div>

            <h2 class="text-lg font-semibold">Project</h2>
            <p class="text-sm opacity-80">Kelola proyek tim</p>

        </a>

        <!-- TEAM -->
        <a href="{{ route('team.index') }}"
           class="group bg-purple-500 text-white p-6 rounded-xl shadow hover:bg-purple-600 transition">

            <div class="text-3xl mb-2">🏢</div>

            <h2 class="text-lg font-semibold">Tim</h2>
            <p class="text-sm opacity-80">Lihat anggota tim</p>

        </a>

    </div>

</div>
</x-app-layout>