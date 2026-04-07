@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 mb-4">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-0">{{ $classroom->name }}</h4>
                    <small class="text-body-secondary">
                        {{ $classroom->created_at?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}</small>
                </div>
               
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Batches in this classroom</h5>
                    </div>
                    
                    <div class="card-body">
                        @forelse ($classroom->batches as $batch)
                            @php
                                $count = $classroom->students->count();
                            @endphp
                            <div class="d-flex justify-content-between align-items-center @if(!$loop->last) border-bottom pb-3 mb-3 @endif">
                                <div>
                                    <h6 class="mb-1">{{ $batch->name }}</h6>
                                    @if ($batch->schedule)
                                        <small class="text-body-secondary d-block">{{ $batch->schedule }}</small>
                                    @endif
                                    @if ($batch->status)
                                        <span class="badge bg-label-primary mt-1">{{ $batch->status }}</span>
                                    @endif
                                </div>
                                <span class="badge bg-label-secondary">{{ $count }} student{{ $count === 1 ? '' : 's' }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary small mb-0">No batches in this classroom yet.</p>
                            <a href="{{ url('user/teacher/batches') }}" class="btn btn-sm btn-primary mt-2">Manage batches</a>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Students</h5>
                        
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Batch</th>
                                        <!-- <th class="text-end">Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($classroom->students as $row)
                                    
                                        <tr>
                                            <td class="fw-medium">{{ $row->name }}</td>
                                            <td><small>{{ $row->email ?? '—' }}</small></td>
                                            <td><small>{{ $row->phone ?? '—' }}</small></td>
                                            <td><span class="badge bg-label-info">{{ $row->batch->name ?? '—' }}</span></td>
                                            <!-- <td class="text-end">
                                                <a href="{{ url('user/teacher/students/view/' . base64_encode($row->id)) }}"
                                                    class="btn btn-sm btn-icon btn-label-secondary" title="View">
                                                    <i class="icon-base ti tabler-eye"></i>
                                                </a>
                                            </td> -->
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-body-secondary small text-center py-4">No students mapped to this classroom for your account.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
