@extends(backpack_view('blank'))

@php
    $breadcrumbs ??= [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        $crud->entity_name_plural => url($crud->route),
        ucfirst(trans('svenk.kanban-operation::kanban-operation.kanban')) => false,
    ];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none"
        bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
        <p class="ms-2 ml-2 mb-0" id="datatable_info_stack" bp-section="page-subheading">{!! $crud->getSubheading() ?? '' !!}</p>
    </section>
@endsection

@section('content')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-9">
            @if ($crud->buttons()->where('stack', 'top')->count() || $crud->exportButtons())
                <div class="d-print-none {{ $crud->hasAccess('create') ? 'with-border' : '' }}">
                    @include('crud::inc.button_stack', ['stack' => 'top'])
                </div>
            @endif
        </div>

        <div class="col-sm-3">
            <div id="datatable_search_stack" class="mt-sm-0 mt-2 d-print-none">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                            <path d="M21 21l-6 -6"></path>
                        </svg>
                    </span>
                    <input type="search" class="form-control" placeholder="{{ trans('backpack::crud.search') }}..." />
                </div>
            </div>
        </div>
    </div>

    {{-- Backpack Filters TODO: make this work with the items --}}
    {{-- <div class="row mb-1">
        <div class="col-12">
            @if ($crud->filtersEnabled())
                @include('crud::inc.filters_navbar')
            @endif
        </div>
    </div> --}}

    <div class="row">

        @foreach ($columns as $key => $column)
            @php
                $columnWidth = 12 / count($columns);
                if ($columnWidth < 3) {
                    $columnWidth = 3;
                }
            @endphp
            <div class="col-md-{{ $columnWidth }} ">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"> {{ $column['label'] }} </h3>
                    </div>
                    <div class="card-body-3 p-0">
                        <div class="kanban-column" data-column="{{ $key }}">

                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        const kanbanData = {
            fields: @json($fields),
            hasUpdateAccess: @json($crud->hasAccess('update')),
            editTranslation: @json(trans('backpack::crud.edit')),
            updateSuccess: '{{ __('svenk.kanban-operation::kanban-operation.update-success') }}',
            updateError: '{{ __('svenk.kanban-operation::kanban-operation.update-error') }}',
            crudRoute: @json(url($crud->route)),
            kanbanColumnField: '{{ $crud->get('kanban.column_field') }}',
            csrfToken: '{{ csrf_token() }}',
            kanbanFlow: @json($crud->get('kanban.flow')),
        };
    </script>
@endsection

@section('after_styles')
    @bassetBlock('backpack/pro/operations/kanban.css')
        <style>
            .kanban-column {
                min-height: 100px;
            }

            .kanban-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                margin-bottom: 10px;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }

            .kanban-item-title {
                flex-grow: 1;
                margin-right: 10px;
            }

            .kanban-item .btn {
                flex-shrink: 0;
            }

            .kanban-item-placeholder {
                border: 2px dashed #dee2e6;
                margin-bottom: 10px;
            }


            .invalid-target {
                cursor: not-allowed !important;
            }

            .hover-invalid {
                background-color: #ffdddd !important;
            }

            .ui-sortable-helper {
                cursor: grabbing !important;
            }
        </style>
    @endBassetBlock

    @stack('crud_list_styles')
@endsection

@section('after_scripts')

    @basset('https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js')
    @bassetBlock('backpack/pro/operations/kanban.js')
        <script>
            $(document).ready(function() {
                const {
                    crudRoute,
                    kanbanColumnField,
                    fields,
                    hasUpdateAccess,
                    editTranslation,
                    updateSuccess,
                    updateError,
                    kanbanFlow,
                    csrfToken
                } = kanbanData;

                let allItems = [];
                const $searchInput = $('#datatable_search_stack input');

                function loadKanbanItems() {
                    $.get(`${crudRoute}/kanban/items`, (response) => {
                        allItems = response;
                        renderItems(allItems);
                        initDragAndDrop();
                    });
                }

                function renderItems(items) {
                    $('.kanban-column').empty();
                    items.forEach(item => {
                        console.log(item.description);

                        const $column = $(`.kanban-column[data-column="${item[kanbanColumnField]}"]`);
                        const $item = $(
                            `<div class="kanban-item" data-id="${item[fields.id]}"><span class="kanban-item-title">${item[fields.title]}</span>${hasUpdateAccess ? `<a href="${crudRoute}/${item[fields.id]}/edit" class="btn btn-sm btn-link pr-0"><span><i class="la la-edit"></i> ${editTranslation}</span></a>` : ''}</div>`
                        );

                        $column.append($item);
                    });
                }

                function initDragAndDrop() {
                    let isUpdating = false;
                    $(".kanban-column").sortable({
                        connectWith: ".kanban-column",
                        placeholder: "kanban-item-placeholder",
                        cursor: "move",
                        start: (event, ui) => {
                            ui.item.oldColumn = ui.item.parent().data('column');
                            $(".kanban-column").each(function() {
                                if (!isValidTransition(ui.item.oldColumn, $(this).data('column'))) {
                                    $(this).addClass('invalid-target');
                                }
                            });
                        },
                        stop: () => {
                            $(".kanban-column").removeClass('invalid-target');
                            isUpdating = false;
                        },
                        over: (event, ui) => {
                            const targetColumn = $(event.target).data('column');
                            const isValid = isValidTransition(ui.item.oldColumn, targetColumn);
                            ui.placeholder.toggle(isValid);
                            $(event.target).toggleClass('hover-invalid', !isValid);
                        },
                        out: (event) => $(event.target).removeClass('hover-invalid'),
                        update: (event, ui) => {
                            if (isUpdating) return;
                            isUpdating = true;

                            const itemId = ui.item.data('id');
                            const newColumn = ui.item.parent().data('column');
                            const oldColumn = ui.item.oldColumn;

                            if (oldColumn !== newColumn) {
                                isValidTransition(oldColumn, newColumn) ?
                                    updateItemColumn(itemId, newColumn, ui.item) :
                                    cancelUpdate($(event.target));
                            } else {
                                isUpdating = false;
                            }
                        }
                    }).disableSelection();
                }

                function updateItemColumn(itemId, newColumn, $item) {
                    $.post(`${crudRoute}/kanban/item/${itemId}`, {
                            [kanbanColumnField]: newColumn,
                            _token: csrfToken
                        },
                        () => {
                            const updatedItem = allItems.find(item => item[fields.id] == itemId);
                            if (updatedItem) {
                                updatedItem[kanbanColumnField] = newColumn;
                                notify(updateSuccess, 'success');
                            }
                        }
                    ).fail(() => {
                        notify(updateError, 'error');
                        renderItems(allItems);
                    });
                }

                function cancelUpdate($target) {
                    console.log('cancel');
                    $target.sortable('cancel');
                    notify(updateError, 'warning');
                    isUpdating = false;
                }

                function isValidTransition(oldColumn, newColumn) {

                    if (kanbanFlow[oldColumn] == null || kanbanFlow[oldColumn] == undefined) {
                        return true;
                    }

                    return !kanbanFlow || (kanbanFlow[oldColumn] && kanbanFlow[oldColumn].includes(newColumn));
                }

                function applySearch() {
                    const searchRegex = new RegExp($searchInput.val().split(' ').join('.*?'), 'i');
                    const filteredItems = allItems.filter(item => searchRegex.test(item[fields.title]));
                    renderItems(filteredItems);
                    initDragAndDrop();
                }

                function notify(text, type = 'success') {
                    new Noty({
                        type,
                        text,
                        timeout: 3000,
                        progressBar: true
                    }).show();
                }

                loadKanbanItems();
                $searchInput.on('input', applySearch);
            });
        </script>
    @endBassetBlock
@endsection
