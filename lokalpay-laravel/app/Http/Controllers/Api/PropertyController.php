<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Organization;
use App\Models\Property;
use App\Services\AuditService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(Organization $organization) { return PropertyResource::collection($organization->properties()->withCount('leases')->latest()->paginate(50)); }

    public function store(PropertyRequest $request, Organization $organization, PlanEntitlementService $plans, AuditService $audit): PropertyResource
    {
        $this->authorize('create', Property::class);
        $property = DB::transaction(function () use ($request, $organization, $plans): Property {
            Organization::query()->lockForUpdate()->findOrFail($organization->id);
            $plans->assertCanAddProperty($organization);
            return $organization->properties()->create($request->validated());
        });
        $audit->record('property.created', $property, null, $property->toArray());
        return new PropertyResource($property);
    }

    public function update(PropertyRequest $request, Organization $organization, Property $property, AuditService $audit): PropertyResource
    {
        abort_unless($property->organization_id === $organization->id, 404);
        $this->authorize('update', $property);
        $before = $property->toArray();
        $property->update($request->validated());
        $audit->record('property.updated', $property, $before, $property->fresh()->toArray());
        return new PropertyResource($property);
    }

    public function destroy(Organization $organization, Property $property, AuditService $audit): JsonResponse
    {
        abort_unless($property->organization_id === $organization->id, 404);
        $this->authorize('delete', $property);
        $audit->record('property.archived', $property, $property->toArray());
        $property->delete();
        return response()->json(['message' => 'Nieruchomość została zarchiwizowana.']);
    }
}
