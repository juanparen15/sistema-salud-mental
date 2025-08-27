<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Http\Resources\PatientResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientApiController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Patient::class, 'patient');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::with(['monthlyFollowups' => function ($query) {
            $query->latest('follow_date');
        }]);

        // Filtros opcionales
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('identification_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('age_min')) {
            $query->where('age', '>=', $request->age_min);
        }

        if ($request->has('age_max')) {
            $query->where('age', '<=', $request->age_max);
        }

        if ($request->has('gender') && $request->gender !== '') {
            $query->where('gender', $request->gender);
        }

        if ($request->has('municipality') && $request->municipality !== '') {
            $query->where('municipality', $request->municipality);
        }

        $patients = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => PatientResource::collection($patients->items()),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identification_number' => 'required|string|max:20|unique:patients',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'age' => 'required|integer|min:0|max:150',
            'gender' => 'required|in:M,F,Otro',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'municipality' => 'required|string|max:100',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $patient = Patient::create($validated);

        return response()->json([
            'message' => 'Paciente creado exitosamente',
            'data' => new PatientResource($patient)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): JsonResponse
    {
        $patient->load(['monthlyFollowups' => function ($query) {
            $query->with('user')->latest('follow_date');
        }]);

        return response()->json([
            'data' => new PatientResource($patient)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'identification_number' => 'required|string|max:20|unique:patients,identification_number,' . $patient->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'age' => 'required|integer|min:0|max:150',
            'gender' => 'required|in:M,F,Otro',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'municipality' => 'required|string|max:100',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $patient->update($validated);

        return response()->json([
            'message' => 'Paciente actualizado exitosamente',
            'data' => new PatientResource($patient)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json([
            'message' => 'Paciente eliminado exitosamente'
        ]);
    }

    /**
     * Get patients with recent follow-ups
     */
    public function withRecentFollowups(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        $patients = Patient::whereHas('monthlyFollowups', function ($query) use ($days) {
            $query->where('follow_date', '>=', now()->subDays($days));
        })->with(['monthlyFollowups' => function ($query) use ($days) {
            $query->where('follow_date', '>=', now()->subDays($days))
                  ->with('user')
                  ->latest('follow_date');
        }])->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => PatientResource::collection($patients->items()),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ]
        ]);
    }

    /**
     * Get patients statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_patients' => Patient::count(),
            'patients_by_gender' => Patient::selectRaw('gender, COUNT(*) as count')
                                         ->groupBy('gender')
                                         ->pluck('count', 'gender'),
            'patients_by_municipality' => Patient::selectRaw('municipality, COUNT(*) as count')
                                                ->groupBy('municipality')
                                                ->orderBy('count', 'desc')
                                                ->limit(10)
                                                ->pluck('count', 'municipality'),
            'age_distribution' => [
                '0-17' => Patient::whereBetween('age', [0, 17])->count(),
                '18-29' => Patient::whereBetween('age', [18, 29])->count(),
                '30-49' => Patient::whereBetween('age', [30, 49])->count(),
                '50-64' => Patient::whereBetween('age', [50, 64])->count(),
                '65+' => Patient::where('age', '>=', 65)->count(),
            ],
            'recent_followups' => Patient::whereHas('monthlyFollowups', function ($query) {
                $query->where('follow_date', '>=', now()->subDays(30));
            })->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}