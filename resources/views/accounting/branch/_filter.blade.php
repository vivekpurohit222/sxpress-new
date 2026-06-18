<form method="GET" action="{{ url()->current() }}" class="d-flex align-items-end gap-2 mb-3 flex-wrap no-print">
    {{-- Preserve existing query params that aren't date-related --}}
    @foreach(request()->except(['from_date', 'to_date']) as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <div>
        <label for="from_date" class="form-label mb-0 small">From Date</label>
        <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', $fromDate ?? '') }}">
    </div>

    <div>
        <label for="to_date" class="form-label mb-0 small">To Date</label>
        <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', $toDate ?? '') }}">
    </div>

    <div>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa fa-filter"></i> Filter
        </button>
    </div>
</form>
