@foreach($cats->sortBy('name') as $cat)
<div class="dm-cat-wrap">
    <label class="dm-cat-item">
        <input type="checkbox" class="dm-cat-cb" value="{{ $cat->id }}">
        <span>{{ $cat->name }}</span>
    </label>
    @if($catsByParent->has($cat->id))
    <div class="dm-cat-children">
        @include('discount_manager._cat_node', [
            'cats' => $catsByParent->get($cat->id),
            'catsByParent' => $catsByParent
        ])
    </div>
    @endif
</div>
@endforeach
