<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller {

    public function index(Request $request) {
        $query = Application::query()
            ->with(['customer', 'plan']) // optional, but avoids N+1
            ->orderBy('created_at', 'asc'); // oldest first

        // Optional plan_type filter: ?plan_type=nbn|opticomm|mobile|null
        if ($request->filled('plan_type')) {
            $planType = $request->query('plan_type');

            $query->whereHas('plan', function ($q) use ($planType) {
                $q->where('type', $planType);
            });
        }

        $applications = $query->paginate(
            (int) $request->get('per_page', 15)
        );

        return ApplicationResource::collection($applications);
    }
}
