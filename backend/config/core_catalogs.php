<?php

return [
    'principles' => [
        'El core solo soporta catalogos realmente transversales para mas de un modulo.',
        'Si una entidad requiere workflow, SLA, aprobaciones o UX propia, debe vivir en un modulo de negocio.',
        'Los catalogos universales se administran desde Data Engine o desde superficies core ya existentes, no desde modulos ad hoc.',
    ],
    'universal_catalogs' => [
        [
            'key' => 'organizations',
            'label' => 'Empresas',
            'resource_key' => 'organizations',
            'category' => 'tenancy',
            'why' => 'Representa el tenant operativo principal del sistema.',
        ],
        [
            'key' => 'offices',
            'label' => 'Oficinas',
            'resource_key' => 'offices',
            'category' => 'tenancy',
            'why' => 'Permite modelar sucursales o sedes compartidas por multiples modulos.',
        ],
        [
            'key' => 'teams',
            'label' => 'Equipos',
            'resource_key' => 'tenant-teams',
            'category' => 'tenancy',
            'why' => 'Agrupa trabajo operativo sin imponer un modulo de negocio especifico.',
        ],
        [
            'key' => 'people',
            'label' => 'Personas',
            'resource_key' => 'people',
            'category' => 'people',
            'why' => 'Entidad humana base reutilizable por identidad, RRHH, CRM y otros modulos.',
        ],
        [
            'key' => 'divisions',
            'label' => 'Divisiones',
            'resource_key' => 'divisions',
            'category' => 'people',
            'why' => 'Estructura organizativa base del cliente.',
        ],
        [
            'key' => 'areas',
            'label' => 'Areas',
            'resource_key' => 'areas',
            'category' => 'people',
            'why' => 'Nivel operativo intermedio reutilizable por multiples modulos.',
        ],
        [
            'key' => 'positions',
            'label' => 'Cargos',
            'resource_key' => 'positions',
            'category' => 'people',
            'why' => 'Catalogo estructural de puestos de trabajo.',
        ],
        [
            'key' => 'work-assignments',
            'label' => 'Asignaciones laborales',
            'resource_key' => 'work-assignments',
            'category' => 'people',
            'why' => 'Contexto operativo real para permisos, jefaturas, aprobaciones y adscripcion por oficina.',
        ],
    ],
    'not_universal_examples' => [
        'Leads',
        'Noticias',
        'Tickets',
        'Pedidos',
        'Cobros',
        'Procesos de aprobacion especificos',
    ],
];
