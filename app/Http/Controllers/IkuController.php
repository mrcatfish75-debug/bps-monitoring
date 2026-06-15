<?php

namespace App\Http\Controllers;

use App\Models\Iku;
use App\Models\Iki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class IkuController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->year;
        $search = $request->search;

        $query = Iku::with([
            'rkKetuas.team.leader',
            'rkKetuas.projects.members',
            'rkKetuas.projects.rkAnggotas.user',
            'rkKetuas.projects.rkAnggotas.ikis.dailyTasks',
            'rkKetuas.projects.rkAnggotas.ikis.approver',
        ]);

        if ($year) {
            $query->where('year', $year);
        }

        if ($search) {
            $this->applyIkuSearch($query, $search);
        }

        $ikus = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $ikus->getCollection()->transform(function ($iku) {
            $summary = $this->buildIkuSummary($iku);

            $iku->rk_ketua_count = $summary['rk_ketua_count'];
            $iku->project_count = $summary['project_count'];
            $iku->rk_anggota_count = $summary['rk_anggota_count'];
            $iku->completed_rk_anggota_count = $summary['completed_rk_anggota_count'];
            $iku->total_iki_count = $summary['total_iki_count'];
            $iku->approved_iki_count = $summary['approved_iki_count'];
            $iku->submitted_iki_count = $summary['submitted_iki_count'];
            $iku->rejected_iki_count = $summary['rejected_iki_count'];
            $iku->daily_task_count = $summary['daily_task_count'];

            return $iku;
        });

        // IKU Template Picker
        $ikuTemplates = Iku::query()
            ->select('name', 'year', 'satuan', 'target')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(function ($iku) {
                return strtolower(trim((string) $iku->name));
            })
            ->values();

        return view('iku.index', compact(
            'ikus',
            'year',
            'search',
            'ikuTemplates'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'required|numeric',
            'satuan' => 'required',
            'target' => 'required|numeric',
        ]);

        Iku::create([
            'name' => $request->name,
            'year' => $request->year,
            'satuan' => $request->satuan,
            'target' => $request->target,
        ]);

        return back()->with('success', 'IKU berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $iku = Iku::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'year' => 'required|numeric',
            'satuan' => 'required',
            'target' => 'required|numeric',
        ]);

        $iku->update([
            'name' => $request->name,
            'year' => $request->year,
            'satuan' => $request->satuan,
            'target' => $request->target,
        ]);

        return back()->with('success', 'IKU berhasil diupdate');
    }

    public function destroy($id)
    {
        $iku = Iku::findOrFail($id);

        if ($iku->rkKetuas()->exists()) {
            return back()->with('error', 'IKU tidak bisa dihapus (sudah dipakai)');
        }

        $iku->delete();

        return back()->with('success', 'IKU berhasil dihapus');
    }

    public function show($id)
    {
        $iku = Iku::with([
            'rkKetuas.team.leader',
            'rkKetuas.ketua',
            'rkKetuas.projects.leader',
            'rkKetuas.projects.members',
            'rkKetuas.projects.rkAnggotas.user',
            'rkKetuas.projects.rkAnggotas.ikis.dailyTasks',
            'rkKetuas.projects.rkAnggotas.ikis.approver',
        ])->findOrFail($id);

        return response()->json($this->formatIkuForJson($iku));
    }

    public function search(Request $request)
    {
        $query = Iku::with([
            'rkKetuas.team.leader',
            'rkKetuas.projects.members',
            'rkKetuas.projects.rkAnggotas.user',
            'rkKetuas.projects.rkAnggotas.ikis.dailyTasks',
            'rkKetuas.projects.rkAnggotas.ikis.approver',
        ]);

        if ($request->year) $query->where('year', $request->year);
        if ($request->search) $this->applyIkuSearch($query, $request->search);

        $ikus = $query->latest()->limit(20)->get();

        $data = $ikus->map(function ($iku) {
            $summary = $this->buildIkuSummary($iku);
            return [
                'id' => $iku->id,
                'code' => $this->hasIkuColumn('code') ? $iku->code : null,
                'name' => $iku->name,
                'description' => $this->hasIkuColumn('description') ? $iku->description : null,
                'year' => $iku->year,
                'satuan' => $iku->satuan,
                'target' => $iku->target,
                'progress' => $iku->progress,
                'progress_label' => $iku->progress_label ?? null,
                'rk_ketua_count' => $summary['rk_ketua_count'],
                'project_count' => $summary['project_count'],
                'rk_anggota_count' => $summary['rk_anggota_count'],
                'completed_rk_anggota_count' => $summary['completed_rk_anggota_count'],
                'total_iki_count' => $summary['total_iki_count'],
                'approved_iki_count' => $summary['approved_iki_count'],
                'submitted_iki_count' => $summary['submitted_iki_count'],
                'rejected_iki_count' => $summary['rejected_iki_count'],
                'draft_iki_count' => $summary['draft_iki_count'],
                'daily_task_count' => $summary['daily_task_count'],
            ];
        });

        return response()->json($data);
    }

    private function applyIkuSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%');
            if ($this->hasIkuColumn('code')) $q->orWhere('code', 'like', '%' . $search . '%');
            if ($this->hasIkuColumn('description')) $q->orWhere('description', 'like', '%' . $search . '%');

            $q->orWhereHas('rkKetuas', fn($sub) => $sub->where('description', 'like', '%' . $search . '%'))
              ->orWhereHas('rkKetuas.team', fn($sub) => $sub->where('name', 'like', '%' . $search . '%'))
              ->orWhereHas('rkKetuas.projects', fn($sub) => $sub->where('name', 'like', '%' . $search . '%'))
              ->orWhereHas('rkKetuas.projects.rkAnggotas', fn($sub) => $sub->where('description', 'like', '%' . $search . '%'))
              ->orWhereHas('rkKetuas.projects.rkAnggotas.ikis', fn($sub) => $sub->where('description', 'like', '%' . $search . '%')
                  ->orWhere('final_evidence', 'like', '%' . $search . '%'));
        });
    }

    private function hasIkuColumn(string $column): bool
    {
        static $columns = [];
        if (!array_key_exists($column, $columns)) {
            $columns[$column] = Schema::hasColumn('ikus', $column);
        }
        return $columns[$column];
    }

    private function buildIkuSummary(Iku $iku): array
    {
        $iku->loadMissing(['rkKetuas.projects.rkAnggotas.ikis.dailyTasks']);

        $rkKetuas = $iku->rkKetuas;
        $projects = $rkKetuas->flatMap(fn($rk) => $rk->projects)->values();
        $rkAnggotas = $projects->flatMap(fn($proj) => $proj->rkAnggotas)->values();
        $ikis = $rkAnggotas->flatMap(fn($rkA) => $rkA->ikis)->values();
        $dailyTasks = $ikis->flatMap(fn($iki) => $iki->dailyTasks)->values();
        $completedRkAnggotas = $rkAnggotas->filter(fn($rkA) => $rkA->is_completed)->count();

        return [
            'rk_ketua_count' => $rkKetuas->count(),
            'project_count' => $projects->count(),
            'rk_anggota_count' => $rkAnggotas->count(),
            'completed_rk_anggota_count' => $completedRkAnggotas,
            'total_iki_count' => $ikis->count(),
            'approved_iki_count' => $ikis->where('status', Iki::STATUS_APPROVED)->count(),
            'submitted_iki_count' => $ikis->where('status', Iki::STATUS_SUBMITTED)->count(),
            'rejected_iki_count' => $ikis->where('status', Iki::STATUS_REJECTED)->count(),
            'draft_iki_count' => $ikis->where('status', Iki::STATUS_DRAFT)->count(),
            'daily_task_count' => $dailyTasks->count(),
        ];
    }

    private function buildRkKetuaSummary($rkKetua): array { /*... sama seperti buildIkuSummary, tetap berfungsi ...*/ }
    private function buildProjectSummary($project): array { /*... sama seperti buildIkuSummary, tetap berfungsi ...*/ }
    private function formatIkuForJson(Iku $iku): array { /*... tetap berfungsi ...*/ }
}