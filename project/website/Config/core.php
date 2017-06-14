<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

/**
 * @file core.php
 * Configuración propia de cada proyecto
 * @version 2014-12-11
 */

 // Configuración depuración
 \sowerphp\core\Configure::write('debug', true);
 \sowerphp\core\Configure::write('error.level', E_ALL);

// Tema de la página (diseño)
\sowerphp\core\Configure::write('page.layout', 'Bootstrap');

// Textos de la página
\sowerphp\core\Configure::write('page.header.title', 'Proyecto web');
\sowerphp\core\Configure::write('page.body.title', 'Proyecto web');
\sowerphp\core\Configure::write('page.footer', [
    'left' => '<span>Página web generada utilizando el framework <a href="http://sowerphp.org">SowerPHP</a></span>',
    'right' => ''
]);

// Menú principal del sitio web
\sowerphp\core\Configure::write('nav.website', [
    //'/contacto'=>'Contacto',
]);

// Configuración para el correo electrónico
\sowerphp\core\Configure::write('email.default', [
    'type' => 'smtp',
    'host' => 'ssl://smtp.gmail.com',
    'port' => 465,
    'user' => 'miguel.lopezt86@gmail.com',
    'pass' => '',
    'from' => ['email'=>'miguel.lopezt86@gmail.com', 'name'=>'Miguel Lopez'],
    'to' => '',
]);

// Configuración para la base de datos
\sowerphp\core\Configure::write('database.default', [
    'type' => 'MySQL',
    'user' => 'root',
    'pass' => '',
    'name' => 'sowerphp',
]);

// Módulos que se utilizarán en la aplicación
/*\sowerphp\core\Module::uses([
    '',
]);*/

// Menú principal de la aplicación (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('nav.app', [
    '/sistema'=>'Sistema'
]);*/

// Configuración para autorización secundaria (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('auth2', [
    'name' => 'Latch',
    'url' => 'https://latch.elevenpaths.com',
    'app_id' => '',
    'app_key' => '',
    'default' => false,
]);*/

// Configuración para reCAPTCHA (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('recaptcha', [
    'public_key' => '',
    'private_key' => '',
]);*/

// Configuración para auto registro de usuarios (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('app.self_register', [
    'groups' => [],
]);*/
