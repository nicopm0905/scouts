<?php

// El registro público está desactivado: las cuentas las crea un administrador.

test('registration screen is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('guests cannot self-register', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
