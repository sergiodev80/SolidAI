<?php

return [
    'resources' => [
        'label' => 'Recursos',
    ],
    'pages' => [
        'dashboard' => [
            'heading' => 'Panel de Control',
        ],
    ],
    'resources' => [
        'pages' => [
            'create_record' => [
                'breadcrumb' => 'Crear',
                'title' => 'Crear :label',
                'form' => [
                    'submit' => [
                        'label' => 'Crear',
                    ],
                ],
            ],
            'edit_record' => [
                'breadcrumb' => 'Editar',
                'title' => 'Editar :label',
                'form' => [
                    'submit' => [
                        'label' => 'Guardar cambios',
                    ],
                ],
            ],
            'list_records' => [
                'breadcrumb' => 'Lista',
                'heading' => ':label',
                'subheading' => 'Administrar :label',
            ],
            'view_record' => [
                'breadcrumb' => 'Ver',
                'title' => 'Ver :label',
            ],
        ],
    ],
    'actions' => [
        'create' => [
            'label' => 'Nuevo :label',
            'modal' => [
                'heading' => 'Crear :label',
                'form' => [
                    'submit' => [
                        'label' => 'Crear',
                    ],
                ],
            ],
        ],
    ],
];
