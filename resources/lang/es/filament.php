<?php

return [
    'pages' => [
        'auth' => [
            'login' => [
                'title' => 'Iniciar sesión',
                'heading' => 'Iniciar sesión',
                'actions' => [
                    'login' => [
                        'label' => 'Iniciar sesión',
                    ],
                ],
                'fields' => [
                    'email' => [
                        'label' => 'Correo electrónico',
                    ],
                    'password' => [
                        'label' => 'Contraseña',
                    ],
                    'remember' => [
                        'label' => 'Recordarme',
                    ],
                ],
                'messages' => [
                    'failed' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
                ],
            ],
            'register' => [
                'title' => 'Registrarse',
                'heading' => 'Registrarse',
                'actions' => [
                    'register' => [
                        'label' => 'Registrarse',
                    ],
                ],
                'fields' => [
                    'email' => [
                        'label' => 'Correo electrónico',
                    ],
                    'name' => [
                        'label' => 'Nombre',
                    ],
                    'password' => [
                        'label' => 'Contraseña',
                    ],
                    'passwordConfirmation' => [
                        'label' => 'Confirmar contraseña',
                    ],
                ],
            ],
            'password-reset' => [
                'title' => 'Restablecer contraseña',
                'heading' => 'Restablecer contraseña',
                'actions' => [
                    'reset' => [
                        'label' => 'Restablecer',
                    ],
                ],
                'fields' => [
                    'email' => [
                        'label' => 'Correo electrónico',
                    ],
                    'password' => [
                        'label' => 'Contraseña',
                    ],
                    'passwordConfirmation' => [
                        'label' => 'Confirmar contraseña',
                    ],
                ],
            ],
        ],
    ],
    
    'components' => [
        'form' => [
            'actions' => [
                'save' => [
                    'label' => 'Guardar',
                ],
                'cancel' => [
                    'label' => 'Cancelar',
                ],
            ],
            'validation' => [
                'required' => 'Este campo es obligatorio.',
                'email' => 'Por favor ingrese un correo electrónico válido.',
                'min' => 'Este campo debe tener al menos :min caracteres.',
                'max' => 'Este campo no puede tener más de :max caracteres.',
            ],
        ],
        'table' => [
            'actions' => [
                'edit' => [
                    'label' => 'Editar',
                ],
                'view' => [
                    'label' => 'Ver',
                ],
                'delete' => [
                    'label' => 'Eliminar',
                ],
            ],
            'filters' => [
                'reset' => 'Limpiar filtros',
            ],
            'pagination' => [
                'showing_results_of' => 'Mostrando :first a :last de :total resultados',
            ],
        ],
    ],
];
