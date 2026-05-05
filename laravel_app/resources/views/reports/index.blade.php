@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<h1>Reports</h1>
<p style="margin-bottom:1rem; color:var(--muted); font-size:0.9rem;">
    Download your expenses per month as CSV or PDF.
</p>

<div class="card">
    @if(empty($months))
        <p>No expenses found yet. Add some expenses first.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $month)
                    @php
                        [$year, $m] = explode('-', $month);
                    @endphp
                    <tr>
                        <td>{{ $month }}</td>
                        <td>
                            <div style="display:flex; flex-direction:column; align-items:flex-start; gap:0.25rem;">
                                <a href="{{ route('reports.csv', ['year' => $year, 'month' => $m]) }}"
                                   class="btn btn-primary" style="padding:0.25rem 0.7rem; font-size:0.85rem;">
                                    Download CSV
                                </a>
                                <a href="{{ route('reports.pdf', ['year' => $year, 'month' => $m]) }}"
                                   class="btn btn-primary" style="padding:0.25rem 0.7rem; font-size:0.85rem;">
                                    Download PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

