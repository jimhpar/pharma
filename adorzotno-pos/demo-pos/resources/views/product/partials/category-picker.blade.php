@php
    $pickerId = $pickerId ?? 'catPicker';
    $options  = $options  ?? collect();
    $selected = array_map('strval', (array) ($selected ?? []));
@endphp

{{-- Hidden select for form submission --}}
<select name="categories[]" multiple hidden id="{{ $pickerId }}-hidden">
    @foreach($options as $opt)
        <option value="{{ $opt->id }}"
            {{ in_array((string) $opt->id, $selected) ? 'selected' : '' }}>
            {{ $opt->display_name ?? $opt->name }}
        </option>
    @endforeach
</select>

<div class="mcat-root" id="{{ $pickerId }}">

    {{-- Tag-input trigger --}}
    <div class="mcat-field form-control" id="{{ $pickerId }}-field">
        <div class="mcat-tags" id="{{ $pickerId }}-tags"></div>
        <input class="mcat-search-input"
               id="{{ $pickerId }}-search"
               type="text"
               placeholder="Search or select categories…"
               autocomplete="off">
    </div>

    {{-- Dropdown --}}
    <div class="mcat-dropdown" id="{{ $pickerId }}-dropdown">

        {{-- Build hierarchy: group children under parents --}}
        @php
            $byParent = $options->groupBy(fn($c) => $c->parent_id ?? 0);
            $parents  = $options->filter(fn($c) => empty($c->parent_id));
            $orphans  = $options->filter(fn($c) => !empty($c->parent_id)
                && !$options->contains('id', $c->parent_id));
        @endphp

        @if($parents->isEmpty() && $orphans->isEmpty())
            <div class="mcat-empty-state">No categories available.</div>
        @else
            <div class="mcat-scroll" id="{{ $pickerId }}-list">

                {{-- Parent categories with their children --}}
                @foreach($parents as $parent)
                @php $children = $byParent->get($parent->id, collect()); @endphp
                <div class="mcat-group" data-group="{{ $parent->id }}">

                    {{-- Parent row --}}
                    <div class="mcat-opt mcat-parent {{ in_array((string)$parent->id, $selected) ? 'is-checked' : '' }}"
                         data-id="{{ $parent->id }}"
                         data-label="{{ $parent->display_name ?? $parent->name }}"
                         data-search="{{ strtolower($parent->name) }}">
                        <div class="mcat-opt-left">
                            <span class="mcat-parent-icon">
                                @if($children->isNotEmpty())
                                    <i class="bi bi-folder2"></i>
                                @else
                                    <i class="bi bi-tag"></i>
                                @endif
                            </span>
                            <span class="mcat-opt-text">{{ $parent->name }}</span>
                        </div>
                        <span class="mcat-checkbox"><i class="bi bi-check2"></i></span>
                    </div>

                    {{-- Children --}}
                    @foreach($children as $child)
                    @php $grandchildren = $byParent->get($child->id, collect()); @endphp

                        <div class="mcat-opt mcat-child {{ in_array((string)$child->id, $selected) ? 'is-checked' : '' }}"
                             data-id="{{ $child->id }}"
                             data-label="{{ $child->display_name ?? $child->name }}"
                             data-search="{{ strtolower($child->name . ' ' . $parent->name) }}">
                            <div class="mcat-opt-left">
                                <span class="mcat-child-indent">
                                    @if($loop->last && $grandchildren->isEmpty()) ╰ @else ├ @endif
                                </span>
                                <span class="mcat-opt-text">{{ $child->name }}</span>
                            </div>
                            <span class="mcat-checkbox"><i class="bi bi-check2"></i></span>
                        </div>

                        {{-- Grand-children --}}
                        @foreach($grandchildren as $grand)
                        <div class="mcat-opt mcat-grandchild {{ in_array((string)$grand->id, $selected) ? 'is-checked' : '' }}"
                             data-id="{{ $grand->id }}"
                             data-label="{{ $grand->display_name ?? $grand->name }}"
                             data-search="{{ strtolower($grand->name . ' ' . $child->name . ' ' . $parent->name) }}">
                            <div class="mcat-opt-left">
                                <span class="mcat-grand-indent">╰</span>
                                <span class="mcat-opt-text">{{ $grand->name }}</span>
                            </div>
                            <span class="mcat-checkbox"><i class="bi bi-check2"></i></span>
                        </div>
                        @endforeach

                    @endforeach
                </div>
                @endforeach

                {{-- Orphan children (parent not in list) --}}
                @foreach($orphans as $orphan)
                <div class="mcat-opt mcat-parent {{ in_array((string)$orphan->id, $selected) ? 'is-checked' : '' }}"
                     data-id="{{ $orphan->id }}"
                     data-label="{{ $orphan->display_name ?? $orphan->name }}"
                     data-search="{{ strtolower($orphan->name) }}">
                    <div class="mcat-opt-left">
                        <span class="mcat-parent-icon"><i class="bi bi-tag"></i></span>
                        <span class="mcat-opt-text">{{ $orphan->name }}</span>
                    </div>
                    <span class="mcat-checkbox"><i class="bi bi-check2"></i></span>
                </div>
                @endforeach

                <div class="mcat-no-results d-none">No categories match your search.</div>
            </div>
        @endif
    </div>
</div>

<style>
/* ── Category Picker ─────────────────────────────── */
.mcat-root { position: relative; }

.mcat-field {
    display: flex; flex-wrap: wrap; align-items: center;
    gap: 5px; min-height: 42px; height: auto;
    padding: 6px 10px; cursor: text;
    transition: border-color .15s, box-shadow .15s;
}
.mcat-field.open,
.mcat-field:focus-within {
    border-color: #86b7fe;
    box-shadow: 0 0 0 .25rem rgba(13,110,253,.18);
}

/* Tags */
.mcat-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: #e8f0fe; color: #1a56db;
    border: 1px solid #c3d7fc; border-radius: 6px;
    padding: 3px 8px; font-size: .77rem; font-weight: 600;
    max-width: 200px; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}
.mcat-tag-remove {
    background: none; border: none; padding: 0; cursor: pointer;
    color: #6d8fd6; font-size: .85rem; line-height: 1;
    display: flex; align-items: center; border-radius: 3px;
    transition: color .1s, background .1s; flex-shrink: 0;
}
.mcat-tag-remove:hover { color: #b91c1c; background: #fce8e8; }

.mcat-search-input {
    flex: 1; min-width: 120px; border: none; outline: none;
    background: transparent; font-size: .85rem; color: #344054; padding: 1px 0;
}
.mcat-search-input::placeholder { color: #9aa5b4; }

/* Dropdown */
.mcat-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    z-index: 1055; background: #fff;
    border: 1.5px solid #d0d9e8; border-radius: 10px;
    box-shadow: 0 8px 28px rgba(0,0,0,.13);
    display: none; overflow: hidden;
}
.mcat-dropdown.open { display: block; }

.mcat-scroll {
    max-height: 280px; overflow-y: auto;
    padding: 6px 6px 4px;
}
.mcat-scroll::-webkit-scrollbar { width: 5px; }
.mcat-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* Groups */
.mcat-group { margin-bottom: 2px; }

/* Option rows */
.mcat-opt {
    display: flex; align-items: center;
    justify-content: space-between;
    padding: 7px 10px; border-radius: 7px;
    cursor: pointer; user-select: none;
    transition: background .1s;
}
.mcat-opt-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.mcat-opt-text { font-size: .82rem; color: #344054; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Parent style */
.mcat-parent { }
.mcat-parent:hover { background: #f0f6ff; }
.mcat-parent.is-checked { background: #eef3ff; }
.mcat-parent .mcat-opt-text { font-weight: 700; color: #1e293b; font-size: .83rem; }
.mcat-parent-icon { color: #6b7c93; font-size: .82rem; flex-shrink: 0; }

/* Child style */
.mcat-child { padding-left: 14px; }
.mcat-child:hover { background: #f8faff; }
.mcat-child.is-checked { background: #eef3ff; }
.mcat-child .mcat-opt-text { color: #374151; }
.mcat-child-indent {
    color: #c8d4e0; font-size: .75rem; flex-shrink: 0;
    width: 14px; text-align: center;
}

/* Grand-child style */
.mcat-grandchild { padding-left: 36px; }
.mcat-grandchild:hover { background: #f8faff; }
.mcat-grandchild.is-checked { background: #eef3ff; }
.mcat-grandchild .mcat-opt-text { color: #6b7280; font-size: .8rem; }
.mcat-grand-indent {
    color: #c8d4e0; font-size: .75rem; flex-shrink: 0;
    width: 14px; text-align: center;
}

/* Divider between groups */
.mcat-group + .mcat-group { border-top: 1px solid #f1f5f9; margin-top: 4px; padding-top: 4px; }

/* Checkbox */
.mcat-checkbox {
    width: 18px; height: 18px; flex-shrink: 0;
    border: 2px solid #c3ccd9; border-radius: 5px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; color: transparent; font-size: .72rem;
    transition: all .12s;
}
.mcat-opt.is-checked .mcat-checkbox {
    background: #0d6efd; border-color: #0d6efd; color: #fff;
}

.mcat-no-results, .mcat-empty-state {
    text-align: center; color: #9aa5b4;
    font-size: .82rem; padding: 20px 0;
}
/* ──────────────────────────────────────────────── */
</style>

<script>
(function () {
    var ROOT   = document.getElementById(@json($pickerId));
    var FIELD  = document.getElementById(@json($pickerId) + '-field');
    var SEARCH = document.getElementById(@json($pickerId) + '-search');
    var TAGS   = document.getElementById(@json($pickerId) + '-tags');
    var DROP   = document.getElementById(@json($pickerId) + '-dropdown');
    var LIST   = document.getElementById(@json($pickerId) + '-list');
    var HIDDEN = document.getElementById(@json($pickerId) + '-hidden');

    if (!ROOT || !FIELD || !SEARCH || !DROP || !HIDDEN) return;

    var NO_RESULTS = LIST ? LIST.querySelector('.mcat-no-results') : null;

    // Collect all option rows
    var allOpts = LIST ? Array.from(LIST.querySelectorAll('.mcat-opt')).map(function (el) {
        return { el: el, id: el.dataset.id, label: el.dataset.label, search: el.dataset.search || '' };
    }) : [];

    // ── Sync hidden select ────────────────────────
    function syncHidden() {
        var checkedIds = allOpts.filter(function (o) {
            return o.el.classList.contains('is-checked');
        }).map(function (o) { return o.id; });

        Array.from(HIDDEN.options).forEach(function (opt) {
            opt.selected = checkedIds.includes(opt.value);
        });
    }

    // ── Render tags ───────────────────────────────
    function renderTags() {
        TAGS.innerHTML = '';
        allOpts.filter(function (o) { return o.el.classList.contains('is-checked'); })
               .forEach(function (o) {
            var tag = document.createElement('span');
            tag.className = 'mcat-tag';
            tag.title = o.label;
            tag.innerHTML = '<span style="overflow:hidden;text-overflow:ellipsis">' + o.label + '</span>' +
                '<button type="button" class="mcat-tag-remove" data-id="' + o.id + '" title="Remove">' +
                '<i class="bi bi-x"></i></button>';
            TAGS.appendChild(tag);
        });
        SEARCH.placeholder = TAGS.children.length ? '' : 'Search or select categories…';
    }

    // ── Toggle ────────────────────────────────────
    function toggleOpt(id) {
        var o = allOpts.find(function (x) { return x.id === id; });
        if (!o) return;
        o.el.classList.toggle('is-checked');
        syncHidden();
        renderTags();
    }

    // ── Open / Close ──────────────────────────────
    function openDrop() {
        DROP.classList.add('open');
        FIELD.classList.add('open');
        SEARCH.value = '';
        filterList('');
        SEARCH.focus();
    }
    function closeDrop() {
        DROP.classList.remove('open');
        FIELD.classList.remove('open');
        SEARCH.value = '';
        filterList('');
    }

    // ── Filter ────────────────────────────────────
    function filterList(q) {
        var lower = q.toLowerCase().trim();
        var visible = 0;

        // When filtering, show/hide individual rows
        allOpts.forEach(function (o) {
            var match = !lower || o.search.includes(lower);
            o.el.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        // Show/hide group dividers: hide group if all children hidden
        if (LIST) {
            Array.from(LIST.querySelectorAll('.mcat-group')).forEach(function (grp) {
                var anyVisible = Array.from(grp.querySelectorAll('.mcat-opt'))
                    .some(function (el) { return el.style.display !== 'none'; });
                grp.style.display = anyVisible ? '' : 'none';
            });
        }

        if (NO_RESULTS) NO_RESULTS.classList.toggle('d-none', visible > 0);
    }

    // ── Events ────────────────────────────────────
    FIELD.addEventListener('click', function (e) {
        if (e.target.closest('.mcat-tag-remove')) return;
        if (DROP.classList.contains('open')) { SEARCH.focus(); return; }
        openDrop();
    });

    TAGS.addEventListener('click', function (e) {
        var btn = e.target.closest('.mcat-tag-remove');
        if (!btn) return;
        e.stopPropagation();
        toggleOpt(btn.dataset.id);
    });

    if (LIST) {
        LIST.addEventListener('click', function (e) {
            var opt = e.target.closest('.mcat-opt');
            if (!opt || !opt.dataset.id) return;
            toggleOpt(opt.dataset.id);
        });
    }

    SEARCH.addEventListener('input', function () { filterList(this.value); });
    SEARCH.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeDrop(); FIELD.focus(); }
    });

    document.addEventListener('mousedown', function (e) {
        if (!ROOT.contains(e.target)) closeDrop();
    });

    // ── Init ──────────────────────────────────────
    syncHidden();
    renderTags();
})();
</script>
