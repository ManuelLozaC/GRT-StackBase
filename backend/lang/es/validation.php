<?php

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'array' => 'El campo :attribute debe ser una lista valida.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'date' => 'El campo :attribute debe ser una fecha valida.',
    'email' => 'El campo :attribute debe ser un correo valido.',
    'exists' => 'El valor seleccionado en :attribute no es valido.',
    'integer' => 'El campo :attribute debe ser un numero entero.',
    'password' => [
        'letters' => 'La :attribute debe incluir al menos una letra.',
        'mixed' => 'La :attribute debe incluir al menos una mayuscula y una minuscula.',
        'numbers' => 'La :attribute debe incluir al menos un numero.',
        'symbols' => 'La :attribute debe incluir al menos un simbolo.',
        'uncompromised' => 'La :attribute aparece en filtraciones conocidas. Usa otra diferente.',
    ],
    'max' => [
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'nullable' => 'El campo :attribute puede ser vacio.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_without' => 'El campo :attribute es obligatorio cuando falta :values.',
    'same' => 'El campo :attribute debe coincidir con :other.',
    'string' => 'El campo :attribute debe ser un texto.',
    'unique' => 'El valor de :attribute ya esta en uso.',

    'attributes' => [
        'name' => 'nombre',
        'alias' => 'alias',
        'email' => 'correo',
        'telefono' => 'telefono',
        'password' => 'contrasena',
        'password_confirmation' => 'confirmacion de contrasena',
        'persona_id' => 'persona',
        'roles' => 'roles',
        'roles.*' => 'rol',
        'permissions' => 'permisos',
        'permissions.*' => 'permiso',
        'activo' => 'estado',
        'device_name' => 'dispositivo',
    ],
];
