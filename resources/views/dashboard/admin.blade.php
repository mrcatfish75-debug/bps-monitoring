<x-app-layout>

<div class="p-6">

    <!-- ================= STATS ================= -->
    <div class="grid grid-cols-4 gap-5 mb-6">

        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-400 text-sm">Users</div>
            <div id="users" class="text-3xl font-bold">
                {{ $totalUser ?? '-' }}
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-400 text-sm">Teams</div>
            <div id="teams" class="text-3xl font-bold">
                {{ $totalTeam ?? '-' }}
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-400 text-sm">Projects</div>
            <div id="projects" class="text-3xl font-bold">
                {{ $totalProject ?? '-' }}
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-400 text-sm">Tasks</div>
            <div id="tasks" class="text-3xl font-bold">
                {{ $totalTask ?? '-' }}
            </div>
        </div>

    </div>

    <!-- ================= CHART ================= -->
    <div class="bg-white p-6 rounded-xl shadow mb-6">
        <h2 class="font-semibold mb-4">Project Progress</h2>
        <canvas id="chart"></canvas>
    </div>

    <!-- ================= CALENDAR ================= -->
    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="font-semibold mb-4">Calendar</h2>

        <div id="calendar" class="text-gray-400">
            (Calendar akan diisi dari deadline project / task)
        </div>
    </div>

</div>

</x-app-layout>

<!-- ================= SCRIPT ================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
fetch("{{ route('admin.stats') }}")
.then(res => res.json())
.then(data => {

    // ================= UPDATE STATS =================
    document.getElementById('users').innerText = data.users ?? 0;
    document.getElementById('teams').innerText = data.teams ?? 0;
    document.getElementById('projects').innerText = data.projects ?? 0;
    document.getElementById('tasks').innerText = data.tasks ?? 0;

    // ================= CHART =================
    if(data.project_progress && data.project_progress.length > 0){

        new Chart(document.getElementById('chart'), {
            type: 'bar',
            data: {
                labels: data.project_progress.map(p => p.name),
                datasets: [{
                    label: 'Progress (%)',
                    data: data.project_progress.map(p => p.progress),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

    } else {
        document.getElementById('chart').replaceWith(
            document.createTextNode('Tidak ada data project')
        );
    }

})
.catch(err => {
    console.error('Dashboard error:', err);
});
</script>