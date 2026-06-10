<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment {{ $gr->gr_no }} - SXpress Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .tracking-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .tracking-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .tracking-header h2 {
            margin: 0;
            font-weight: 700;
        }
        .gr-number {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #667eea;
        }
        .status-badge {
            font-size: 1.1rem;
            padding: 8px 24px;
            border-radius: 50px;
        }
        .timeline {
            padding: 40px 30px;
        }
        .timeline-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        .timeline-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            z-index: 2;
        }
        .timeline-marker.completed {
            background: #28a745;
            color: white;
        }
        .timeline-marker.current {
            background: #667eea;
            color: white;
            animation: pulse 2s infinite;
        }
        .timeline-marker.pending {
            background: #e0e0e0;
            color: #999;
        }
        .timeline-marker.cancelled {
            background: #dc3545;
            color: white;
        }
        .timeline-content {
            flex: 1;
            padding-left: 20px;
            padding-top: 5px;
        }
        .timeline-label {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .timeline-desc {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        .timeline-connector {
            position: absolute;
            left: 19px;
            top: 45px;
            width: 2px;
            height: calc(100% - 15px);
            background: #e0e0e0;
        }
        .timeline-item:last-child .timeline-connector {
            display: none;
        }
        .timeline-item.completed .timeline-connector,
        .timeline-item.current .timeline-connector {
            background: #28a745;
        }
        .info-section {
            padding: 30px;
            border-top: 1px solid #eee;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .pod-section {
            padding: 20px 30px 30px;
        }
        .pod-available {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pod-unavailable {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tracking-card">
            <!-- Header -->
            <div class="tracking-header">
                <div class="mb-3">
                    <img src="{{ asset('admin/images/saurashtraf.png') }}" alt="SXpress" style="height: 50px;">
                </div>
                <h4>Shipment Tracking</h4>
                <div class="gr-number">{{ $gr->gr_no }}</div>
                <div class="mt-3">
                    @php
                        $statusClass = match($gr->status ?? 'created') {
                            'created'    => 'bg-secondary',
                            'dispatched' => 'bg-primary',
                            'in_transit' => 'bg-warning text-dark',
                            'delivered'  => 'bg-info',
                            'closed'     => 'bg-success',
                            'cancelled'  => 'bg-danger',
                            default      => 'bg-light text-dark',
                        };
                        $statusLabel = match($gr->status ?? 'created') {
                            'created'    => 'Created',
                            'dispatched' => 'Dispatched',
                            'in_transit' => 'In Transit',
                            'delivered'  => 'Delivered',
                            'closed'     => 'Closed',
                            'cancelled'  => 'Cancelled',
                            default      => ucfirst($gr->status ?? 'Created'),
                        };
                    @endphp
                    <span class="badge {{ $statusClass }} status-badge">{{ $statusLabel }}</span>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="timeline">
                @if(isset($timeline['cancelled']))
                    <div class="timeline-item cancelled">
                        <div class="timeline-marker cancelled">
                            <i class="bi bi-x"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label text-danger">{{ $timeline['cancelled']['label'] }}</div>
                            <p class="timeline-desc">{{ $timeline['cancelled']['description'] }}</p>
                        </div>
                    </div>
                @else
                    @foreach($timeline as $step => $item)
                        @if($step !== 'cancelled')
                            <div class="timeline-item {{ $item['completed'] ? 'completed' : 'pending' }}">
                                @if($step > 1)
                                    <div class="timeline-connector"></div>
                                @endif
                                <div class="timeline-marker {{ $item['current'] ? 'current' : ($item['completed'] ? 'completed' : 'pending') }}">
                                    @if($item['completed'] && !$item['current'])
                                        <i class="bi bi-check"></i>
                                    @elseif($item['current'])
                                        <i class="bi bi-circle-fill"></i>
                                    @else
                                        <i class="bi bi-circle"></i>
                                    @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-label {{ $item['current'] ? 'text-primary' : '' }}">{{ $item['label'] }}</div>
                                    <p class="timeline-desc">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <!-- Shipment Details -->
            <div class="info-section">
                <h5 class="mb-4"><i class="bi bi-box-seam"></i> Shipment Details</h5>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">From</div>
                        <div class="info-value">{{ $gr->from_dest ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">To</div>
                        <div class="info-value">{{ $gr->to_dest ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Booking Date</div>
                        <div class="info-value">{{ $gr->copy_date ? \Carbon\Carbon::parse($gr->copy_date)->format('d M Y') : '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Consignor</div>
                        <div class="info-value">{{ Str::limit($gr->consignor, 30) ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Consignee</div>
                        <div class="info-value">{{ Str::limit($gr->consignee, 30) ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Packages</div>
                        <div class="info-value">{{ $gr->nugs ?? '-' }} ({{ $gr->meth ?? '-' }})</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Weight</div>
                        <div class="info-value">{{ $gr->weight ? number_format($gr->weight, 2) . ' kg' : '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Type</div>
                        <div class="info-value">
                            @if($gr->paid)
                                <span class="badge bg-success">PAID</span>
                            @elseif($gr->to_pay)
                                <span class="badge bg-warning text-dark">TO-PAY</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- POD Section -->
            <div class="pod-section">
                <h5 class="mb-3"><i class="bi bi-file-earmark-post"></i> Proof of Delivery</h5>
                @if($podAvailable)
                    <div class="pod-available">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div>
                            <strong>POD Available</strong>
                            @if($gr->pod_date)
                                <div class="small">Delivered on {{ \Carbon\Carbon::parse($gr->pod_date)->format('d M Y') }}</div>
                            @endif
                        </div>
                        <a href="{{ Storage::url($gr->pod_file) }}" target="_blank" class="btn btn-sm btn-success ms-auto">
                            <i class="bi bi-download"></i> View POD
                        </a>
                    </div>
                @else
                    <div class="pod-unavailable">
                        <i class="bi bi-hourglass-split text-warning"></i>
                        <div>
                            <strong>POD Not Available Yet</strong>
                            <div class="small">POD will be uploaded after delivery confirmation</div>
                        </div>
                    </div>
                @endif

                @if($gr->delivered_at)
                    <div class="mt-3">
                        <div class="info-item">
                            <div class="info-label">Delivered At</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($gr->delivered_at)->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Track your shipment online at <strong>SXpress Logistics</strong></p>
                <p class="mb-0"><i class="bi bi-telephone"></i> For queries: 097279 00008, 93750 88088</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>