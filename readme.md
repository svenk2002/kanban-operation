# KanbanOperation for Backpack

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

This package provides Kanban board functionality for projects that use the [Backpack for Laravel](https://backpackforlaravel.com/) administration panel. 

It adds a Kanban view to your CRUD panels, allowing you to visualize and manage your data in a Kanban-style board. This is particularly useful for tracking the status of items across different stages or categories.

## Screenshots

![Backpack Kanban Operation](https://github.com/user-attachments/assets/98588da8-6d89-4a9e-bcc4-14111806ecaa)

## Installation

You can install the package via composer:

```bash
composer require svenk/kanban-operation
```

## Usage

To use the Kanban operation in your CrudController:

1. Use the `KanbanOperation` trait in your controller:

```php
use Svenk\KanbanOperation\KanbanOperation;

class YourCrudController extends CrudController
{
    use KanbanOperation;

    // ...
}
```


2. Configure the Kanban board:

```php
    protected function setupKanbanOperation()
    {
        CRUD::set('kanban.label_field', 'name'); //The field to display in the kanban card
        CRUD::set('kanban.column_field', 'status'); //The field to use as the column

        CRUD::setOperationSetting('columns', [
            'pending' => [
                'label' => 'Pending',
                'flow' => ['in_progress', 'backlog'], //The columns that can be moved to
            ],
            'in_progress' => [
                'label' => 'In Progress',
                'flow' => ['done', 'pending'],
            ],
            'done' => [
                'label' => 'Done',
                'flow' => null, //Can be moved to any column
            ],
        ]);
    }
```

This sets up a Kanban board with three columns (To Do, In Progress, Done), using the `status` field to determine which column an item belongs to, and the `title` field as the label for each item.

## Customization

You can customize various aspects of the Kanban board:

- **Columns**: Define your own columns and their labels.
- **Item Fields**: Choose which model fields to use for the column and label.
- **Permissions**: Control access to the Kanban view and item updates.

## Change log

Please see the [Releases tab](https://github.com/svenk/kanban-operation/releases) for more information on what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email sven.kolthof19@gmail.com instead of using the issue tracker.

## Credits

- [SvenK2002][link-author]
- [All Contributors][link-contributors]

## License

This project was released under MIT, so you can install it on top of any Backpack & Laravel project. Please see the [license file](license.md) for more information. 

However, please note that you do need Backpack installed, so you need to also abide by its [YUMMY License](https://github.com/Laravel-Backpack/CRUD/blob/master/LICENSE.md). That means in production you'll need a Backpack license code. You can get a free one for non-commercial use (or a paid one for commercial use) on [backpackforlaravel.com](https://backpackforlaravel.com).

[ico-version]: https://img.shields.io/packagist/v/svenk/kanban-operation.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/svenk/kanban-operation.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/svenk/kanban-operation
[link-downloads]: https://packagist.org/packages/svenk/kanban-operation
[link-author]: https://github.com/svenk2002
[link-contributors]: ../../contributors
