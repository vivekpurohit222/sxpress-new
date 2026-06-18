<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.summary') ? 'active' : '' }}" href="{{ route('accounting.gst.summary') }}">Summary</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.collection') ? 'active' : '' }}" href="{{ route('accounting.gst.collection') }}">Collection</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.liability') ? 'active' : '' }}" href="{{ route('accounting.gst.liability') }}">Liability</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.input-tax') ? 'active' : '' }}" href="{{ route('accounting.gst.input-tax') }}">Input Tax</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.output-tax') ? 'active' : '' }}" href="{{ route('accounting.gst.output-tax') }}">Output Tax</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.gst.settings') ? 'active' : '' }}" href="{{ route('accounting.gst.settings') }}">Settings</a>
    </li>
</ul>
