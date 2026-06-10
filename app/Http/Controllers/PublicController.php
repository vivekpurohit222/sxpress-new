<?php

namespace App\Http\Controllers;

use App\Models\Gr;
use Illuminate\Http\Request;

/**
 * PublicController
 *
 * Handles public-facing pages that don't require authentication.
 * Currently: GR tracking portal at /track/{gr_no}
 *
 * Per SXPRESS_PHASE_7
 */
class PublicController extends Controller
{
    /**
     * Track a GR by GR number.
     * Public endpoint - no authentication required.
     * GET /track/{gr_no}
     *
     * Shows:
     * - GR Number, Status, Origin, Destination
     * - Consignor, Consignee, Booking Date
     * - Status timeline (Created → Dispatched → In Transit → Delivered → Closed)
     * - POD availability
     * - Delivery date if delivered
     */
    public function track($gr_no)
    {
        // Find GR by GR number
        $gr = Gr::where('gr_no', $gr_no)->first();

        if (!$gr) {
            return view('track.not_found', compact('gr_no'));
        }

        // Build status timeline
        $timeline = $this->buildStatusTimeline($gr);

        // POD available?
        $podAvailable = !empty($gr->pod_file);

        return view('track.gr', compact('gr', 'timeline', 'podAvailable'));
    }

    /**
     * Build the status timeline for display.
     */
    private function buildStatusTimeline(Gr $gr): array
    {
        $statusOrder = [
            'created'    => 1,
            'dispatched' => 2,
            'in_transit' => 3,
            'delivered'  => 4,
            'closed'     => 5,
            'cancelled'  => 99,
        ];

        $currentStatus = $gr->status ?? 'created';
        $currentStep = $statusOrder[$currentStatus] ?? 0;

        $timeline = [];
        $steps = [
            1 => ['label' => 'Created', 'description' => 'GR booked at origin branch'],
            2 => ['label' => 'Dispatched', 'description' => 'Goods loaded and dispatched'],
            3 => ['label' => 'In Transit', 'description' => 'En route to destination'],
            4 => ['label' => 'Delivered', 'description' => 'Goods delivered to consignee'],
            5 => ['label' => 'Closed', 'description' => 'Freight memo created, billing complete'],
        ];

        foreach ($steps as $step => $info) {
            $timeline[$step] = [
                'label'       => $info['label'],
                'description' => $info['description'],
                'completed'   => $currentStep >= $step,
                'current'     => ($currentStatus !== 'cancelled' && $currentStep === $step),
                'cancelled'   => false,
            ];
        }

        if ($currentStatus === 'cancelled') {
            $timeline['cancelled'] = [
                'label'       => 'Cancelled',
                'description' => 'This shipment has been cancelled',
                'completed'   => true,
                'current'     => true,
                'cancelled'   => true,
            ];
        }

        return $timeline;
    }
}