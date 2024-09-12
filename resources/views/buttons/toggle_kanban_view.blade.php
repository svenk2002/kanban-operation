@php
    [$href, $title, $icon] = match ($crud->getCurrentOperation()) {
        'kanban' => [$crud->route, ucfirst(trans('svenk.kanban-operation::kanban-operation.list-view')), 'list'],
        default => [
            $crud->route . '/kanban',
            ucfirst(trans('svenk.kanban-operation::kanban-operation.kanban-view')),
            'columns',
        ],
    };
@endphp

@if (
    ($crud->getCurrentOperation() === 'kanban' && $crud->hasAccess('list')) ||
        ($crud->getCurrentOperation() === 'list' && $crud->hasAccess('kanban')))
    <a href="{{ url($href) }}" bp-button="kanban" class="btn btn-primary">
        <i class="la la-{{ $icon }}"></i><span> {{ $title }}</span>
    </a>
@endif
