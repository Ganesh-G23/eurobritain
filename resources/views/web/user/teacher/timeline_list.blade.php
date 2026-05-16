@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Timeline List</h5>
                        <a href="{{ url('user/teacher/timelines') }}" class="btn btn-sm btn-primary">Add Timeline</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th>Classroom</th>
                                    <th>Batches</th>
                                    <th>Date</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($timelines ?? [] as $timeline)
                                    @php
                                        $batchLabel = collect($timeline->normalizedBatchIds())
                                            ->map(fn ($id) => $batch_names[$id] ?? null)
                                            ->filter()
                                            ->implode(', ');
                                    @endphp
                                    <tr>
                                        <td>{{ $timeline->topic }}</td>
                                        <td>{{ $timeline->classroom->name ?? '-' }}</td>
                                        <td>{{ $batchLabel !== '' ? $batchLabel : '—' }}</td>
                                        <td>{{ $timeline->date ? $timeline->date->format('d M Y') : '—' }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ url('user/teacher/timelines/edit/' . $timeline->id) }}"
                                                class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                <i class="icon-base ti tabler-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-body-secondary py-4">No timelines added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
