@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">{{ $title }}</h5>
                    <small class="text-muted">Pick a field, then drag a rectangle on the image. Text auto-fits inside the box at generation time.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('admin/certificate-type/edit/' . $details->id) }}" class="btn btn-label-secondary btn-sm">Back to certificate type</a>
                    <button type="button" class="btn btn-label-warning btn-sm" id="reset-template-coords">Reset to defaults</button>
                    <button type="button" class="btn btn-primary btn-sm" id="save-template-coords">Save</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3">
                        <h6 class="mb-2">Fields</h6>
                        <div class="list-group mb-3" id="coord-field-list"></div>

                        <h6 class="mb-2">Selected field</h6>
                        <div id="coord-field-settings" class="border rounded p-2"></div>

                        <div class="form-text mt-2">
                            <strong>Tip:</strong> The font auto-shrinks until it fits the rectangle. The size you set is the <em>maximum</em>.
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div id="template-canvas" class="border rounded p-2" style="background:#f8f9fa;">
                            <div id="template-canvas-inner"
                                style="position:relative; display:block; width:100%; max-width:640px; margin:0 auto; user-select:none;">
                                <img id="template-preview-img"
                                    src="{{ url('storage/app/uploads/temp/' . $details->certificate_template) }}"
                                    alt="Template" draggable="false"
                                    style="display:block; width:100%; height:auto;">
                                <div id="template-rects"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const saveUrl = '{{ url('admin/certificate-type/save-template-coords/' . $details->id) }}';

        const COORD_FIELDS = {
            company_name: 'Company Name',
            company_address: 'Company Address',
            scope: 'Scope',
            certificate_number: 'Certificate No',
            issue_date: 'Issue Date',
            date_of_expiry: 'Expiry Date',
            next_audit_date: 'Next Audit Date'
        };

        const DEFAULT_COORDS = {
            company_name:       { x: 200, y: 295, w: 320, h: 40, align: 'center', valign: 'middle', size: 22, color: '#000000', bold: true },
            company_address:    { x: 160, y: 345, w: 400, h: 30, align: 'center', valign: 'middle', size: 12, color: '#444444' },
            scope:              { x: 120, y: 530, w: 480, h: 70, align: 'center', valign: 'middle', size: 11, color: '#000000' },
            certificate_number: { x: 320, y: 645, w: 240, h: 26, align: 'left',   valign: 'middle', size: 14, color: '#000000' },
            issue_date:         { x: 110, y: 680, w: 120, h: 22, align: 'left',   valign: 'middle', size: 11, color: '#000000' },
            date_of_expiry:     { x: 280, y: 680, w: 120, h: 22, align: 'left',   valign: 'middle', size: 11, color: '#000000' },
            next_audit_date:    { x: 460, y: 680, w: 130, h: 22, align: 'left',   valign: 'middle', size: 11, color: '#000000' }
        };

        const initialCoords = @json($details->template_coords ?? null);

        let coordsState = (initialCoords && typeof initialCoords === 'object' && !Array.isArray(initialCoords))
            ? JSON.parse(JSON.stringify(initialCoords))
            : {};
        let activeCoordField = 'company_name';
        let op = null;

        function ensureFieldDefaults() {
            Object.keys(COORD_FIELDS).forEach(function(key) {
                const def = DEFAULT_COORDS[key];
                if (!coordsState[key]) {
                    coordsState[key] = Object.assign({}, def);
                    return;
                }
                const cur = coordsState[key];
                ['x','y','w','h','size','align','valign','color','bold'].forEach(function(p){
                    if (cur[p] === undefined && def && def[p] !== undefined) {
                        cur[p] = def[p];
                    }
                });
                if (!cur.w) cur.w = def?.w ?? 200;
                if (!cur.h) cur.h = def?.h ?? 30;
                if (!cur.valign) cur.valign = def?.valign ?? 'middle';
            });
        }

        function getNatural() {
            const img = $('#template-preview-img')[0];
            return { nw: img && img.naturalWidth ? img.naturalWidth : 0, nh: img && img.naturalHeight ? img.naturalHeight : 0 };
        }

        function clampField(cfg) {
            const { nw, nh } = getNatural();
            if (!nw || !nh) return cfg;
            cfg.w = Math.max(20, Math.min(cfg.w | 0, nw));
            cfg.h = Math.max(12, Math.min(cfg.h | 0, nh));
            cfg.x = Math.max(0, Math.min(cfg.x | 0, nw - cfg.w));
            cfg.y = Math.max(0, Math.min(cfg.y | 0, nh - cfg.h));
            return cfg;
        }

        function renderCoordFieldList() {
            const $list = $('#coord-field-list').empty();
            Object.keys(COORD_FIELDS).forEach(function(key) {
                const cfg = coordsState[key] || {};
                const active = key === activeCoordField ? 'active' : '';
                const meta = `${cfg.w ?? 0}×${cfg.h ?? 0}`;
                $list.append(
                    '<button type="button" class="list-group-item list-group-item-action coord-field-btn d-flex justify-content-between align-items-center ' + active + '" data-field="' + key + '">' +
                    '<span>' + COORD_FIELDS[key] + '</span>' +
                    '<small class="text-muted">' + meta + '</small>' +
                    '</button>'
                );
            });
        }

        function renderCoordFieldSettings() {
            const cfg = coordsState[activeCoordField] || {};
            const html = `
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">X</label>
                        <input type="number" class="form-control form-control-sm coord-input" data-prop="x" value="${cfg.x ?? 0}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Y</label>
                        <input type="number" class="form-control form-control-sm coord-input" data-prop="y" value="${cfg.y ?? 0}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Width</label>
                        <input type="number" class="form-control form-control-sm coord-input" data-prop="w" min="20" value="${cfg.w ?? 0}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Height</label>
                        <input type="number" class="form-control form-control-sm coord-input" data-prop="h" min="12" value="${cfg.h ?? 0}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Max size</label>
                        <input type="number" class="form-control form-control-sm coord-input" data-prop="size" min="6" value="${cfg.size ?? 12}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Color</label>
                        <input type="color" class="form-control form-control-sm form-control-color coord-input" data-prop="color" value="${cfg.color || '#000000'}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">H. Align</label>
                        <select class="form-select form-select-sm coord-input" data-prop="align">
                            <option value="left" ${cfg.align === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${cfg.align === 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${cfg.align === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">V. Align</label>
                        <select class="form-select form-select-sm coord-input" data-prop="valign">
                            <option value="top" ${cfg.valign === 'top' ? 'selected' : ''}>Top</option>
                            <option value="middle" ${(cfg.valign === 'middle' || !cfg.valign) ? 'selected' : ''}>Middle</option>
                            <option value="bottom" ${cfg.valign === 'bottom' ? 'selected' : ''}>Bottom</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Weight</label>
                        <select class="form-select form-select-sm coord-input" data-prop="bold">
                            <option value="0" ${!cfg.bold ? 'selected' : ''}>Normal</option>
                            <option value="1" ${cfg.bold ? 'selected' : ''}>Bold</option>
                        </select>
                    </div>
                </div>`;
            $('#coord-field-settings').html(html);
        }

        function renderRects() {
            const $rects = $('#template-rects').empty();
            const { nw, nh } = getNatural();
            if (!nw || !nh) return;

            Object.keys(COORD_FIELDS).forEach(function(key) {
                const cfg = coordsState[key];
                if (!cfg || cfg.w === undefined || cfg.h === undefined) return;
                const isActive = key === activeCoordField;
                const left   = (cfg.x / nw) * 100;
                const top    = (cfg.y / nh) * 100;
                const width  = (cfg.w / nw) * 100;
                const height = (cfg.h / nh) * 100;

                const handles = isActive
                    ? '<span class="rect-handle rect-handle-nw" data-handle="nw"></span>' +
                      '<span class="rect-handle rect-handle-ne" data-handle="ne"></span>' +
                      '<span class="rect-handle rect-handle-sw" data-handle="sw"></span>' +
                      '<span class="rect-handle rect-handle-se" data-handle="se"></span>'
                    : '';

                $rects.append(
                    '<div class="template-rect ' + (isActive ? 'active' : '') + '" data-field="' + key + '" ' +
                    'style="left:' + left + '%; top:' + top + '%; width:' + width + '%; height:' + height + '%;" ' +
                    'title="' + COORD_FIELDS[key] + '">' +
                    '<span class="template-rect-label">' + COORD_FIELDS[key] + '</span>' +
                    handles +
                    '</div>'
                );
            });
        }

        function refreshEditor() {
            renderCoordFieldList();
            renderCoordFieldSettings();
            renderRects();
        }

        function refreshOverlayOnly() {
            renderCoordFieldList();
            renderRects();
        }

        function getImageOffset(event) {
            const $img = $('#template-preview-img');
            const rect = $img[0].getBoundingClientRect();
            const scaleX = $img[0].naturalWidth / rect.width;
            const scaleY = $img[0].naturalHeight / rect.height;
            return {
                x: Math.round((event.clientX - rect.left) * scaleX),
                y: Math.round((event.clientY - rect.top) * scaleY)
            };
        }

        $(function() {
            ensureFieldDefaults();
            refreshEditor();

            const $img = $('#template-preview-img');
            if ($img[0].complete && $img[0].naturalWidth) {
                renderRects();
            } else {
                $img.on('load', renderRects);
            }
            $(window).on('resize', renderRects);
        });

        $(document).on('click', '.coord-field-btn', function() {
            activeCoordField = $(this).data('field');
            refreshEditor();
        });

        $(document).on('input change', '.coord-input', function() {
            const prop = $(this).data('prop');
            let val = $(this).val();
            if (prop === 'bold') {
                val = val === '1' || val === true;
            } else if (['x', 'y', 'w', 'h', 'size'].includes(prop)) {
                val = val === '' ? undefined : parseInt(val, 10);
            }
            if (!coordsState[activeCoordField]) coordsState[activeCoordField] = {};
            if (val === undefined || val === '') {
                delete coordsState[activeCoordField][prop];
            } else {
                coordsState[activeCoordField][prop] = val;
            }
            if (['x','y','w','h'].includes(prop)) {
                clampField(coordsState[activeCoordField]);
                refreshOverlayOnly();
            }
        });

        $('#reset-template-coords').on('click', function() {
            if (!confirm('Reset all field positions to defaults?')) return;
            coordsState = JSON.parse(JSON.stringify(DEFAULT_COORDS));
            refreshEditor();
        });

        $(document).on('mousedown', '.template-rect', function(e) {
            if ($(e.target).hasClass('rect-handle')) return;
            e.preventDefault();
            e.stopPropagation();
            const field = $(this).data('field');
            activeCoordField = field;
            const start = getImageOffset(e);
            const cfg = coordsState[field];
            op = { type: 'move', field, startMouse: start, startCfg: { x: cfg.x, y: cfg.y } };
            refreshEditor();
        });

        $(document).on('mousedown', '.rect-handle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const handle = $(this).data('handle');
            const field = activeCoordField;
            const cfg = coordsState[field];
            const start = getImageOffset(e);
            op = { type: 'resize', handle, field, startMouse: start, startCfg: { x: cfg.x, y: cfg.y, w: cfg.w, h: cfg.h } };
        });

        $('#template-preview-img').on('mousedown', function(e) {
            if (e.button !== 0) return;
            e.preventDefault();
            const start = getImageOffset(e);
            op = { type: 'draw', field: activeCoordField, startMouse: start };
        });

        $(document).on('mousemove', function(e) {
            if (!op) return;
            const cur = getImageOffset(e);
            const cfg = coordsState[op.field];
            if (!cfg) return;

            if (op.type === 'move') {
                const dx = cur.x - op.startMouse.x;
                const dy = cur.y - op.startMouse.y;
                cfg.x = op.startCfg.x + dx;
                cfg.y = op.startCfg.y + dy;
                clampField(cfg);
            } else if (op.type === 'resize') {
                const dx = cur.x - op.startMouse.x;
                const dy = cur.y - op.startMouse.y;
                let nx = op.startCfg.x, ny = op.startCfg.y, nw = op.startCfg.w, nh = op.startCfg.h;
                if (op.handle.includes('e')) nw = op.startCfg.w + dx;
                if (op.handle.includes('s')) nh = op.startCfg.h + dy;
                if (op.handle.includes('w')) { nx = op.startCfg.x + dx; nw = op.startCfg.w - dx; }
                if (op.handle.includes('n')) { ny = op.startCfg.y + dy; nh = op.startCfg.h - dy; }
                if (nw < 20) { nw = 20; if (op.handle.includes('w')) nx = op.startCfg.x + op.startCfg.w - 20; }
                if (nh < 12) { nh = 12; if (op.handle.includes('n')) ny = op.startCfg.y + op.startCfg.h - 12; }
                cfg.x = nx; cfg.y = ny; cfg.w = nw; cfg.h = nh;
                clampField(cfg);
            } else if (op.type === 'draw') {
                const x = Math.min(op.startMouse.x, cur.x);
                const y = Math.min(op.startMouse.y, cur.y);
                const w = Math.abs(cur.x - op.startMouse.x);
                const h = Math.abs(cur.y - op.startMouse.y);
                if (w < 4 && h < 4) return;
                cfg.x = x; cfg.y = y; cfg.w = Math.max(20, w); cfg.h = Math.max(12, h);
                clampField(cfg);
            }
            refreshEditor();
        });

        $(document).on('mouseup', function() {
            if (op && op.type === 'draw') {
                const cfg = coordsState[op.field];
                if (cfg && (cfg.w < 20 || cfg.h < 12)) {
                    cfg.w = Math.max(cfg.w, 20);
                    cfg.h = Math.max(cfg.h, 12);
                    clampField(cfg);
                    refreshEditor();
                }
            }
            op = null;
        });

        $('#save-template-coords').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            $.post(saveUrl, {
                _token: csrfToken,
                template_coords: JSON.stringify(coordsState)
            }, function(res) {
                $btn.prop('disabled', false).text('Save');
                if (typeof processAjaxResponse === 'function') {
                    processAjaxResponse(res, 800);
                } else if (res && res.status == 1 && res.redirect_url) {
                    window.location.href = res.redirect_url;
                }
            }, 'json').fail(function() {
                $btn.prop('disabled', false).text('Save');
                alert('Unable to save template field positions.');
            });
        });
    </script>
    <style>
        #template-rects { position: absolute; inset: 0; pointer-events: none; }
        .template-rect {
            position: absolute;
            box-sizing: border-box;
            border: 1.5px dashed rgba(105, 108, 255, 0.85);
            background: rgba(105, 108, 255, 0.08);
            cursor: move;
            pointer-events: auto;
            z-index: 2;
        }
        .template-rect.active {
            border-color: rgba(255, 62, 29, 0.95);
            background: rgba(255, 62, 29, 0.10);
            border-style: solid;
            z-index: 3;
        }
        .template-rect-label {
            position: absolute;
            top: -18px;
            left: -1px;
            background: rgba(105, 108, 255, 0.9);
            color: #fff;
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 3px 3px 0 0;
            white-space: nowrap;
            line-height: 1.3;
        }
        .template-rect.active .template-rect-label { background: rgba(255, 62, 29, 0.95); }
        .rect-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #fff;
            border: 2px solid rgba(255, 62, 29, 0.95);
            border-radius: 2px;
            pointer-events: auto;
        }
        .rect-handle-nw { left: -5px; top: -5px; cursor: nwse-resize; }
        .rect-handle-ne { right: -5px; top: -5px; cursor: nesw-resize; }
        .rect-handle-sw { left: -5px; bottom: -5px; cursor: nesw-resize; }
        .rect-handle-se { right: -5px; bottom: -5px; cursor: nwse-resize; }
        #template-preview-img { cursor: crosshair; }
        .coord-field-btn small { font-family: monospace; }
    </style>
@endsection
