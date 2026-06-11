@if($grs->count() > 0)
<table class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>GR No.</th>
        <th>Date</th>
        <th>Consignor</th>
        <th>Consignee</th>
        <th>From → To</th>
        <th>Weight</th>
        <th>Total (₹)</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($grs as $row)
      <tr>
        <td><strong>{{$row->gr_no}}</strong></td>
        <td>{{\Carbon\Carbon::parse($row->copy_date)->format('d M Y')}}</td>
        <td>{{Str::limit($row->consignor, 20)}}</td>
        <td>{{Str::limit($row->consignee, 20)}}</td>
        <td>{{$row->from_dest}} → {{$row->to_dest}}</td>
        <td>{{$row->weight ?: '0'}} kg</td>
        <td><strong>₹{{number_format($row->total_amount ?: 0, 2)}}</strong></td>
        <td>
            {{-- Workflow status badge --}}
            @php
                $statusClass = match($row->status ?? 'created') {
                    'created'    => 'bg-secondary',
                    'dispatched' => 'bg-primary',
                    'in_transit' => 'bg-warning text-dark',
                    'delivered'  => 'bg-info',
                    'closed'     => 'bg-success',
                    'cancelled'  => 'bg-danger',
                    default      => 'bg-light text-dark',
                };
                $statusLabel = match($row->status ?? 'created') {
                    'created'    => 'Created',
                    'dispatched' => 'Dispatched',
                    'in_transit' => 'In Transit',
                    'delivered'  => 'Delivered',
                    'closed'     => 'Closed',
                    'cancelled'  => 'Cancelled',
                    default      => ucfirst($row->status ?? 'Created'),
                };
            @endphp
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>

            {{-- Payment status badge --}}
            @if($row->paid == 1)
                <span class="badge badge-success">PAID</span>
            @elseif($row->to_pay == 1)
                @if($row->topay_collected == 1)
                    <span class="badge badge-info">TO-PAY ✓</span>
                @else
                    <span class="badge badge-warning">TO-PAY</span>
                @endif
            @endif

            {{-- POD badge --}}
            @if($row->pod_file)
                <span class="badge badge-dark">POD</span>
            @endif
        </td>
        <td>
            <a href="{{url('/gr/'.$row->id.'/edit')}}" class="btn btn-sm btn-primary">Edit</a>
            @if($row->to_pay == 1 && $row->topay_collected != 1)
                <button type="button" class="btn btn-sm btn-success" onclick="markTopayCollected({{ $row->id }})">
                    Mark Collected
                </button>
            @elseif($row->to_pay == 1 && $row->topay_collected == 1)
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="undoTopayCollected({{ $row->id }})">
                    Undo
                </button>
            @endif
            <form action="{{url('/gr/'.$row->id.'/delete')}}" method="POST" style="display:inline;">
               @method('DELETE')
                 {{ csrf_field() }}
                 <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
            <a href="{{url('/gr/'.$row->id.'/print')}}" class="btn btn-sm btn-info">Print</a>
        </td>
      </tr>
      @endforeach
    </tbody>
</table>
@else
<div class="text-center text-muted py-5">
    <h4>No GRs found</h4>
</div>
@endif

<script>
function markTopayCollected(id) {
    if (confirm('Mark TO-PAY as collected for this GR?')) {
        $.ajax({
            url: '/gr/' + id + '/mark-topay-collected',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert('TO-PAY marked as collected');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON.message || 'Failed'));
            }
        });
    }
}

function undoTopayCollected(id) {
    if (confirm('Undo TO-PAY collection?')) {
        $.ajax({
            url: '/gr/' + id + '/undo-topay-collected',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert('TO-PAY collection undone');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON.message || 'Failed'));
            }
        });
    }
}
</script>
