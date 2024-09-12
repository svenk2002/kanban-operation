<?php

namespace Svenk\KanbanOperation;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

trait KanbanOperation
{
    /**
     * Define which routes are needed for this operation.
     */
    protected function setupKanbanRoutes(string $segment, string $routeName, string $controller): void
    {
        // Kanban View
        Route::get($segment . '/kanban', [
            'as'        => $routeName . '.kanban',
            'uses'      => $controller . '@kanbanView',
            'operation' => 'kanban',
        ]);

        // Fetch kanban items
        Route::get($segment . '/kanban/items', [
            'as'        => $segment . '.fetchKanbanItems',
            'uses'      => $controller . '@getKanbanItems',
            'operation' => 'kanban',
        ]);

        // Update kanban item
        Route::post($segment . '/kanban/item/{id}', [
            'as'        => $segment . '.updateKanbanItem',
            'uses'      => $controller . '@updateKanbanItem',
            'operation' => 'kanban',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     * 
     * @return void
     */
    protected function setupKanbanOperationDefaults(): void
    {
        CRUD::allowAccess('kanban');

        CRUD::operation('kanban', function () {
            if (CRUD::hasAccess('create')) {
                CRUD::addButton('top', 'create', 'view', 'crud::buttons.create');
            }
        });


        CRUD::operation(['kanban', 'list', 'show'], function () {
            CRUD::addButton('top', 'toggle_kanban_view', 'view', 'svenk.kanbanoperation::buttons.toggle_kanban_view');
        });

        CRUD::set('kanban.javascript-configuration', []);
        CRUD::set('kanban.column_field', 'status');
        CRUD::set('kanban.label_field', 'title');
    }

    /**
     * Show the kanban view.
     * 
     * @return View
     */
    protected function kanbanView(): View
    {
        CRUD::hasAccessOrFail('kanban');

        //Fill the flow for each column
        $columns = CRUD::get('kanban.columns');
        $flow = [];
        foreach ($columns as $key => $column) $flow[$key] = $column['flow'] ?? null;
        CRUD::set('kanban.flow', $flow);

        return view('svenk.kanban-operation::kanban_view', [
            'crud' => $this->crud,
            'columns' => CRUD::get('kanban.columns'),
            'fields' => $this->getKanbanFieldsMap(),
        ]);
    }

    /**
     * Fetch the kanban items.
     * 
     * @param Request $request
     * @return Collection
     */
    public function getKanbanItems(Request $request): Collection
    {
        method_exists($this, 'setupListOperation') && $this->setupListOperation();

        $columnField = CRUD::get('kanban.column_field');

        try {
            $items = $this->crud->query->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch kanban items: ' . $e->getMessage());
            $items = collect();
        }

        return $items->map(function ($item) use ($columnField) {

            return Arr::only(
                $item->toArray(),
                [...$this->getKanbanFieldsMap(), 'buttons', $columnField]
            );
        });
    }

    /**
     * Update a kanban item.
     * 
     * @param Request $request
     * @param int $id
     */
    public function updateKanbanItem(Request $request, int $id): void
    {
        method_exists($this, 'setupUpdateOperation') && $this->setupUpdateOperation();

        if (! CRUD::hasAccessToAll(['update', 'kanban'])) {
            abort(403);
        }

        $columnField = CRUD::get('kanban.column_field');
        $flow = CRUD::get('kanban.flow');

        $this->validate($request, [
            $columnField => 'required|string',
        ]);

        $item = $this->crud->query->findOrFail($id);
        $currentStatus = $item->$columnField;
        $newStatus = $request->input($columnField);

        if ($currentStatus === $newStatus) return;

        if ($flow !== null) {
            if (!isset($flow[$currentStatus]) && $flow[$currentStatus] !== null) abort(422, 'State not set');


            if ($flow[$currentStatus] !== null && !in_array($newStatus, $flow[$currentStatus])) abort(422, 'Invalid status transition');
        }

        $item->updateOrFail([
            $columnField => $newStatus,
        ]);
    }

    /**
     * Get the kanban fields map
     * 
     * @return array
     */
    protected function getKanbanFieldsMap(): array
    {
        return [
            'id' => CRUD::getModel()->getKeyName(),
            'title' => CRUD::get('kanban.label_field') ?? 'title',
        ];
    }
}
