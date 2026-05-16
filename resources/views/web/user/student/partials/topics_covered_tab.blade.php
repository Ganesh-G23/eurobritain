<div class="tab-pane fade" id="horizontal-topics" role="tabpanel" aria-labelledby="topics-list-item">
    @if (!$classroom->batches->contains(fn ($b) => $b->is_my_batch))
        <div class="alert alert-info mb-0">You are not enrolled in any batch in this classroom.</div>
    @else
        @foreach ($classroom->batches as $batch)
            @php
                $bid = (int) $batch->id;
                $topics = collect($batch->topics_covered ?? []);
                $topicsFilterFrom = \Illuminate\Support\Carbon::now()->startOfMonth()->format('Y-m-d');
                $topicsFilterTo = \Illuminate\Support\Carbon::now()->endOfMonth()->format('Y-m-d');
            @endphp
            <div class="batch-topics @if ((int) $batch->id !== (int) ($default_batch_id ?? 0)) d-none @endif"
                id="batch-topics-{{ $bid }}">
                <p class="text-body-secondary small mb-3">{{ $batch->name }}</p>
                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="classroom-topics-filter-from-{{ $bid }}">From date</label>
                        <input type="date" class="form-control js-topics-date-from"
                            id="classroom-topics-filter-from-{{ $bid }}" data-batch-id="{{ $bid }}"
                            data-default-date="{{ $topicsFilterFrom }}" value="{{ $topicsFilterFrom }}"
                            max="2099-12-31">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="classroom-topics-filter-to-{{ $bid }}">To date</label>
                        <input type="date" class="form-control js-topics-date-to"
                            id="classroom-topics-filter-to-{{ $bid }}" data-batch-id="{{ $bid }}"
                            data-default-date="{{ $topicsFilterTo }}" value="{{ $topicsFilterTo }}"
                            max="2099-12-31">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary btn-lg-2 js-topics-date-search-btn"
                                data-batch-id="{{ $bid }}">
                                Search
                            </button>
                            <button type="button" class="btn btn-label-secondary btn-lg-2 js-topics-date-reset-btn"
                                data-batch-id="{{ $bid }}">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                @if ($topics->isEmpty())
                    <p class="text-body-secondary small mb-0">No topics covered yet for this batch.</p>
                @else
                    <p id="topics-filter-empty-{{ $bid }}"
                        class="text-body-secondary small mb-0 d-none">No topics in the selected date range.</p>
                    <div class="table-responsive topics-date-scroll">
                        <table class="table table-bordered table-striped align-middle mb-0"
                            id="topics-table-{{ $bid }}" data-batch-id="{{ $bid }}">
                            <thead>
                                <tr>
                                    <th class="text-nowrap" style="width: 3.5rem;">#</th>
                                    <th>Topics Covered</th>
                                    <th class="text-nowrap" style="width: 10rem;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topics as $entry)
                                    <tr data-topic-date="{{ $entry['date'] ?? '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-medium">{{ $entry['topic'] ?? '—' }}</td>
                                        <td class="text-body-secondary">
                                            {{ $entry['date_label'] ?? $entry['date'] ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>
