<?php

namespace App\Http\Controllers;

use App\Models\RkAnggota;
use App\Models\Project;
use App\Models\User;
use App\Models\Iki;
use App\Models\RkAnggotaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RkAnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'kepala', 'ketua', 'anggota'], true)) {
            abort(403, 'Role tidak diizinkan mengakses RK Anggota.');
        }

        /*
        |--------------------------------------------------------------------------
        | Mode Context
        |--------------------------------------------------------------------------
        | /ketua/rk-anggota
        | - mode ketua/reviewer
        | - melihat RK Anggota dari project yang dia pimpin.
        |
        | /ketua/rk-anggota?mode=mine
        | - mode pekerjaan saya
        | - melihat RK Anggota miliknya sendiri sebagai anggota project lain.
        |--------------------------------------------------------------------------
        */
        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        $query = RkAnggota::with([
            'project.team',
            'project.leader',
            'project.rkKetua.iku',
            'user',

            /*
            |--------------------------------------------------------------------------
            | IKI Flow
            |--------------------------------------------------------------------------
            | RK Anggota sekarang adalah wadah.
            | Progress dan approval utama berasal dari IKI.
            |--------------------------------------------------------------------------
            */
            'ikis.dailyTasks',
            'ikis.approver',

            /*
            |--------------------------------------------------------------------------
            | Legacy relation
            |--------------------------------------------------------------------------
            | Tetap diload untuk kompatibilitas data lama dan tampilan lama.
            |--------------------------------------------------------------------------
            */
            'dailyTasks',
            'approver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role Scope
        |--------------------------------------------------------------------------
        | Admin:
        | - melihat semua RK Anggota.
        |
        | Kepala:
        | - monitoring semua RK Anggota.
        |
        | Anggota:
        | - hanya melihat RK miliknya sendiri.
        |
        | Ketua mode normal:
        | - melihat RK Anggota dari project yang dia pimpin.
        |
        | Ketua mode mine:
        | - melihat RK Anggota miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'anggota' || $isMineMode) {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'ketua') {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if (
            $request->filled('user_id')
            && !($user->role === 'anggota' || $isMineMode)
        ) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('year')) {
            $query->whereHas('project.rkKetua.iku', function ($q) use ($request) {
                $q->where('year', $request->year);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhereHas('project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('project.team', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('ikis', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    });
            });
        }

        $rkAnggotas = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dropdown User
        |--------------------------------------------------------------------------
        | Kepala tetap dapat list user untuk filter, bukan untuk create.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'anggota' || $isMineMode) {
            $users = User::where('id', $user->id)->get();
        } else {
            $users = User::whereIn('role', ['anggota', 'ketua'])
                ->orderBy('name')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Dropdown Project
        |--------------------------------------------------------------------------
        */
        $projectQuery = Project::with([
            'team',
            'leader',
            'members',
            'rkKetua.iku',
        ]);

        if ($user->role === 'ketua' && !$isMineMode) {
            $projectQuery->where('leader_id', $user->id);
        }

        if ($user->role === 'anggota' || $isMineMode) {
            $projectQuery->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $projects = $projectQuery
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RK Anggota Template Picker
        |--------------------------------------------------------------------------
        | Template dari database dinormalisasi khusus untuk dropdown.
        |
        | Jika satu baris template berisi banyak poin seperti:
        | 1. Kegiatan A 2. Kegiatan B 3. Kegiatan C
        |
        | maka sistem akan memecahnya menjadi beberapa item pilihan terpisah.
        | Ini tidak mengubah data di database dan tidak mengubah flow utama.
        |--------------------------------------------------------------------------
        */
        $rkTemplates = $this->buildRkTemplatePickerOptions();

        return view('rk_anggota.index', compact(
            'rkAnggotas',
            'projects',
            'users',
            'rkTemplates'
        ));
    }

    public function store(Request $request)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'project_id' => 'required|exists:projects,id',

            /*
            | Backward compatible:
            | - form lama: user_id
            | - form baru: user_ids[]
            */
            'user_id' => 'nullable|required_without:user_ids|exists:users,id',
            'user_ids' => 'nullable|required_without:user_id|array|min:1',
            'user_ids.*' => 'exists:users,id',

            'description' => 'required|string',
        ], [
            'user_id.required_without' => 'Pilih minimal satu anggota.',
            'user_ids.required_without' => 'Pilih minimal satu anggota.',
        ]);

        $project = Project::with([
            'members',
            'leader',
        ])->findOrFail($request->project_id);

        $assigneeIds = $this->resolveAssigneeIds($request);

        $this->authorizeCreateRkForAssignees($project, $assigneeIds, $request);

        $assignees = User::whereIn('id', $assigneeIds)->get();

        foreach ($assignees as $assignee) {
            $this->ensureValidAssigneeRole($assignee);
            $this->ensureUserIsProjectMember($project, (int) $assignee->id);
        }

        foreach ($assignees as $assignee) {
            RkAnggota::create([
                'project_id' => $project->id,
                'user_id' => $assignee->id,
                'description' => $request->description,

                /*
                |--------------------------------------------------------------------------
                | RK Anggota status legacy
                |--------------------------------------------------------------------------
                | RK Anggota sekarang hanya wadah.
                | Status approval utama ada di IKI.
                |--------------------------------------------------------------------------
                */
                'status' => RkAnggota::STATUS_DRAFT,
            ]);
        }

        $message = $assignees->count() > 1
            ? 'RK Anggota berhasil dibuat untuk ' . $assignees->count() . ' anggota.'
            : 'RK Anggota dibuat.';

        return back()->with('success', $message);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $rk = RkAnggota::with([
            'project.members',
            'ikis',
        ])->findOrFail($id);

        $this->authorizeManageRk($rk);

        /*
        |--------------------------------------------------------------------------
        | Edit Lock
        |--------------------------------------------------------------------------
        | RK Anggota boleh diedit selama belum punya IKI yang submitted/approved.
        | Kalau sudah ada IKI masuk review/disetujui, identitas RK jangan diubah
        | agar histori monitoring tetap konsisten.
        |--------------------------------------------------------------------------
        */
        if ($this->hasLockedIki($rk)) {
            return back()->with('error', 'RK Anggota tidak bisa diubah karena sudah memiliki IKI yang disubmit atau disetujui.');
        }

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
        ]);

        $user = auth()->user();

        $project = Project::with(['members', 'leader'])->findOrFail($request->project_id);
        $assignee = User::findOrFail($request->user_id);

        /*
        |--------------------------------------------------------------------------
        | Authorization Update
        |--------------------------------------------------------------------------
        | Admin:
        | - boleh mengubah semua RK.
        |
        | Ketua leader project:
        | - boleh mengubah RK dari project yang dia pimpin.
        | - tidak boleh menjadikan dirinya sendiri sebagai anggota project sendiri.
        |
        | Anggota / Ketua mode mine:
        | - hanya boleh mengubah RK miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role !== 'admin') {
            $isProjectLeader = $user->role === 'ketua'
                && (int) $project->leader_id === (int) $user->id;

            if ($isProjectLeader) {
                if ((int) $assignee->id === (int) $user->id) {
                    return back()->with('error', 'Ketua project tidak boleh membuat/mengubah RK Anggota untuk dirinya sendiri pada project yang dia pimpin.');
                }
            } else {
                if ((int) $request->user_id !== (int) $user->id) {
                    abort(403, 'Kamu hanya boleh mengubah RK Anggota milikmu sendiri.');
                }

                if ((int) $rk->user_id !== (int) $user->id) {
                    abort(403, 'Kamu hanya boleh mengubah RK Anggota milikmu sendiri.');
                }
            }
        }

        $this->ensureValidAssigneeRole($assignee);
        $this->ensureUserIsProjectMember($project, (int) $assignee->id);

        $rk->update([
            'project_id' => $project->id,
            'user_id' => $assignee->id,
            'description' => $request->description,
        ]);

        return back()->with('success', 'RK Anggota diupdate.');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy RK Anggota Approval
    |--------------------------------------------------------------------------
    | Approval utama sudah dipindahkan ke IKI.
    | Method ini sengaja tetap ada agar route lama tidak error, tetapi tidak lagi
    | mengubah status RK Anggota.
    |--------------------------------------------------------------------------
    */

    public function submit(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $rk = RkAnggota::with(['ikis'])->findOrFail($id);

        $this->authorizeSubmitRk($rk);

        if ($rk->ikis->count() === 0) {
            return back()->with('error', 'Buat minimal satu IKI terlebih dahulu. Submit sekarang dilakukan melalui IKI, bukan RK Anggota.');
        }

        return back()->with('error', 'Submit RK Anggota sudah tidak digunakan. Silakan submit IKI pada menu IKI.');
    }

    public function approve($id)
    {
        $this->abortIfMonitoringOnly();

        $rk = RkAnggota::with(['project', 'ikis'])->findOrFail($id);

        $this->authorizeReviewRk($rk);

        return back()->with('error', 'Approval RK Anggota sudah tidak digunakan. Silakan approve IKI pada menu IKI.');
    }

    public function reject(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $rk = RkAnggota::with(['project', 'ikis'])->findOrFail($id);

        $this->authorizeReviewRk($rk);

        return back()->with('error', 'Reject RK Anggota sudah tidak digunakan. Silakan reject IKI pada menu IKI.');
    }

    public function destroy($id)
    {
        $this->abortIfMonitoringOnly();

        $rk = RkAnggota::with([
            'project',
            'ikis',
        ])->findOrFail($id);

        $this->authorizeManageRk($rk);

        /*
        |--------------------------------------------------------------------------
        | Delete Guard
        |--------------------------------------------------------------------------
        | Jangan hapus RK yang sudah memiliki IKI.
        | Karena IKI adalah unit approval dan punya Daily Task/bukti.
        |--------------------------------------------------------------------------
        */
        if ($rk->ikis->count() > 0) {
            return back()->with('error', 'RK Anggota tidak bisa dihapus karena sudah memiliki IKI. Hapus IKI terkait terlebih dahulu jika memang diperlukan.');
        }

        $rk->delete();

        return back()->with('success', 'RK Anggota dihapus.');
    }

    public function show($id)
    {
        $rk = RkAnggota::with([
            'project.team',
            'project.rkKetua.iku',
            'project.leader',
            'user',

            /*
            |--------------------------------------------------------------------------
            | IKI detail
            |--------------------------------------------------------------------------
            */
            'ikis.dailyTasks',
            'ikis.approver',

            /*
            |--------------------------------------------------------------------------
            | Legacy daily tasks
            |--------------------------------------------------------------------------
            */
            'dailyTasks',
            'approver',
        ])->findOrFail($id);

        $this->authorizeViewRk($rk);

        return response()->json($this->formatRkForJson($rk));
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Helpers
    |--------------------------------------------------------------------------
    */

    private function authorizeViewRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'kepala'], true)) {
            return;
        }

        if ((int) $rk->user_id === (int) $user->id) {
            return;
        }

        if (
            $rk->project
            && (int) $rk->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak melihat RK Anggota ini.');
    }

    private function authorizeManageRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        /*
        | Ketua leader project boleh manage RK dari project yang dia pimpin.
        | Ini mendukung pembagian RK ke anggota.
        */
        if (
            $user->role === 'ketua'
            && $rk->project
            && (int) $rk->project->leader_id === (int) $user->id
        ) {
            if ((int) $rk->user_id === (int) $user->id) {
                abort(403, 'Kamu tidak boleh mengelola RK Anggota milikmu sendiri pada project yang kamu pimpin.');
            }

            return;
        }

        if ((int) $rk->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh mengelola RK Anggota milikmu sendiri.');
    }

    private function authorizeSubmitRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ((int) $rk->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh submit RK Anggota milikmu sendiri.');
    }

    private function authorizeReviewRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ((int) $rk->user_id === (int) $user->id) {
            abort(403, 'Kamu tidak boleh review RK Anggota milikmu sendiri.');
        }

        if (
            $user->role === 'ketua'
            && $rk->project
            && (int) $rk->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh review RK Anggota dari project yang kamu pimpin.');
    }

    private function authorizeCreateRkForAssignees(Project $project, Collection $assigneeIds, Request $request): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        if ($user->role === 'ketua' && !$isMineMode && (int) $project->leader_id === (int) $user->id) {
            if ($assigneeIds->contains((int) $user->id)) {
                abort(403, 'Ketua project tidak boleh membuat RK Anggota untuk dirinya sendiri pada project yang dia pimpin.');
            }

            return;
        }

        /*
        | Anggota atau ketua mode mine hanya boleh membuat RK untuk dirinya sendiri.
        */
        if ($assigneeIds->count() !== 1 || (int) $assigneeIds->first() !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh membuat RK Anggota untuk dirimu sendiri.');
        }
    }

    private function resolveAssigneeIds(Request $request): Collection
    {
        $ids = collect();

        if ($request->filled('user_id')) {
            $ids->push((int) $request->user_id);
        }

        if ($request->filled('user_ids')) {
            $ids = $ids->merge(
                collect($request->user_ids)->map(fn ($id) => (int) $id)
            );
        }

        return $ids
            ->filter()
            ->unique()
            ->values();
    }

    private function ensureUserIsProjectMember(Project $project, int $userId): void
    {
        if (!$project->members->contains('id', $userId)) {
            abort(422, 'User bukan anggota project. RK Anggota hanya bisa dibuat untuk user yang ada di project_members.');
        }
    }

    private function ensureValidAssigneeRole(User $user): void
    {
        if (!in_array($user->role, ['anggota', 'ketua'], true)) {
            abort(422, 'User tidak valid sebagai anggota project. Hanya role anggota atau ketua yang boleh diberi RK Anggota.');
        }
    }

    private function abortIfMonitoringOnly(): void
    {
        if (auth()->user()?->role === 'kepala') {
            abort(403, 'Kepala hanya dapat melakukan monitoring.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RK Anggota Template Picker Helpers
    |--------------------------------------------------------------------------
    */

    private function buildRkTemplatePickerOptions(): Collection
    {
        return RkAnggotaTemplate::active()
            ->select([
                'id',
                'description',
                'description_hash',
                'category',
                'is_active',
            ])
            ->orderBy('category')
            ->orderBy('description')
            ->get()
            ->flatMap(function ($template) {
                return $this->splitTemplateDescription($template->description)
                    ->map(function ($description, $index) use ($template) {
                        return (object) [
                            'id' => $template->id,
                            'template_key' => ($template->description_hash ?: 'template-' . $template->id) . '-' . $index,
                            'description' => $description,
                            'description_hash' => $template->description_hash,
                            'category' => $template->category,
                            'category_label' => $template->category ?: 'RK Anggota',
                            'is_active' => (bool) $template->is_active,
                        ];
                    });
            })
            ->filter(fn ($template) => trim((string) $template->description) !== '')
            ->unique(fn ($template) => mb_strtolower(trim($template->description)))
            ->values();
    }

    private function splitTemplateDescription(?string $description): Collection
    {
        $description = trim((string) $description);

        if ($description === '') {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisasi teks
        |--------------------------------------------------------------------------
        | Data dari Excel kadang masuk sebagai satu teks panjang:
        | 1. Kegiatan A 2. Kegiatan B 3. Kegiatan C
        |--------------------------------------------------------------------------
        */
        $normalized = preg_replace('/\s+/u', ' ', $description);
        $normalized = trim((string) $normalized);

        if ($normalized === '') {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Pecah template bernomor
        |--------------------------------------------------------------------------
        | Pola yang didukung:
        | 1. Teks
        | 2. Teks
        | 10. Teks
        |--------------------------------------------------------------------------
        */
        preg_match_all(
            '/(?:^|\s)(\d+)\.\s*(.*?)(?=\s+\d+\.\s*|$)/u',
            $normalized,
            $matches
        );

        if (!empty($matches[2])) {
            return collect($matches[2])
                ->map(fn ($part) => trim((string) $part))
                ->filter()
                ->unique()
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        | Kalau bukan format bernomor, tetap kirim sebagai satu template normal.
        |--------------------------------------------------------------------------
        */
        return collect([$normalized]);
    }

    /*
    |--------------------------------------------------------------------------
    | IKI Helpers
    |--------------------------------------------------------------------------
    */

    private function hasLockedIki(RkAnggota $rk): bool
    {
        $rk->loadMissing('ikis');

        return $rk->ikis
            ->whereIn('status', [
                Iki::STATUS_SUBMITTED,
                Iki::STATUS_APPROVED,
            ])
            ->isNotEmpty();
    }

    private function formatRkForJson(RkAnggota $rk): array
    {
        $project = $rk->project;
        $team = $project?->team;
        $rkKetua = $project?->rkKetua;
        $iku = $rkKetua?->iku;
        $leader = $project?->leader;
        $owner = $rk->user;

        $ikis = $rk->ikis->map(function ($iki) {
            return [
                'id' => $iki->id,
                'description' => $iki->description,
                'target' => $iki->target,
                'unit' => $iki->unit,
                'status' => $iki->status,
                'status_label' => $iki->status_label,
                'progress' => $iki->progress,
                'final_evidence' => $iki->final_evidence,
                'submitted_at' => $iki->submitted_at,
                'approved_at' => $iki->approved_at,
                'approved_by' => $iki->approved_by,
                'rejection_note' => $iki->rejection_note,
                'daily_task_count' => $iki->dailyTasks->count(),
                'approver' => $iki->approver ? [
                    'id' => $iki->approver->id,
                    'name' => $iki->approver->name,
                    'email' => $iki->approver->email,
                ] : null,
            ];
        })->values();

        return [
            'id' => $rk->id,
            'project_id' => $rk->project_id,
            'user_id' => $rk->user_id,
            'description' => $rk->description,
            'target' => $rk->target,

            /*
            |--------------------------------------------------------------------------
            | Status legacy
            |--------------------------------------------------------------------------
            | Tetap dikirim untuk kompatibilitas Blade lama.
            | Approval final tidak lagi memakai status ini.
            |--------------------------------------------------------------------------
            */
            'status' => $rk->status,
            'status_label' => $rk->status_label,

            'submitted_at' => $rk->submitted_at,
            'approved_at' => $rk->approved_at,
            'approved_by' => $rk->approved_by,
            'final_evidence' => $rk->final_evidence,
            'rejection_note' => $rk->rejection_note,

            /*
            |--------------------------------------------------------------------------
            | Progress baru
            |--------------------------------------------------------------------------
            | Progress RK Anggota berasal dari IKI.
            |--------------------------------------------------------------------------
            */
            'progress' => $rk->progress,
            'is_completed' => $rk->is_completed,

            'iki_count' => $rk->ikis->count(),
            'approved_iki_count' => $rk->ikis
                ->where('status', Iki::STATUS_APPROVED)
                ->count(),

            'daily_task_count' => $rk->ikis
                ->flatMap(fn ($iki) => $iki->dailyTasks)
                ->count(),

            'ikis' => $ikis,

            'user' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => $owner->role,
            ] : null,

            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status ?? null,
                'progress' => $project->progress ?? 0,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,

                'leader' => $leader ? [
                    'id' => $leader->id,
                    'name' => $leader->name,
                    'email' => $leader->email,
                ] : null,

                'team' => $team ? [
                    'id' => $team->id,
                    'name' => $team->name,
                ] : null,

                'rk_ketua' => $rkKetua ? [
                    'id' => $rkKetua->id,
                    'description' => $rkKetua->description,
                    'target' => $rkKetua->target,
                    'iku' => $iku ? [
                        'id' => $iku->id,
                        'name' => $iku->name,
                        'year' => $iku->year,
                        'target' => $iku->target,
                        'satuan' => $iku->satuan,
                    ] : null,
                ] : null,

                /*
                | CamelCase alias untuk JS lama.
                */
                'rkKetua' => $rkKetua ? [
                    'id' => $rkKetua->id,
                    'description' => $rkKetua->description,
                    'target' => $rkKetua->target,
                    'iku' => $iku ? [
                        'id' => $iku->id,
                        'name' => $iku->name,
                        'year' => $iku->year,
                        'target' => $iku->target,
                        'satuan' => $iku->satuan,
                    ] : null,
                ] : null,
            ] : null,
        ];
    }
}