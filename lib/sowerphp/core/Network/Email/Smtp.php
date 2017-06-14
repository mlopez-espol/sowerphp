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

namespace sowerphp\core;

// clases PEAR
require_once('Mail.php');
require_once('Mail/mime.php');

/**
 * Clase para enviar correo electrónico mediante SMTP
 * Requiere:
 *   # pear install Mail Mail_mime Net_SMTP
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-09-16
 */
class Network_Email_Smtp
{

    protected $_config = null; ///< Configuración para SMTP
    protected $_header = null; ///< Datos de la cabecera del mensaje
    protected $_data = null; ///< Datos del mensaje (incluyendo adjuntos)

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración del correo a enviar
     * @param header Cabecerá del correo electrónico
     * @param data Datos (cuerpo) de correo electrónico
     * @param debug =true se muestra debug, =false modo silencioso
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-03-05
     */
    public function __construct ($config, $header, $data, $debug = false)
    {
        // Configuración para la conexión al servidor
        $this->_config = array(
            'host' => $config['host'],
            'port' => $config['port'],
            'auth' => true,
            'username' => $config['user'],
            'password' => $config['pass'],
            'debug' => $debug
        );
        // Cabecera
        $this->_header = $header;
        // Datos
        $this->_data = $data;
        // desactivar errores (ya que Mail no pasa validación E_STRICT)
        ini_set('error_reporting', false);
    }

    /**
     * Método que envía el correo
     * @return Arreglo con los estados de retorno por cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-05-12
     */
    public function send()
    {
        // Crear correo
        $mailer = \Mail::factory('smtp', $this->_config);
        $mail = new \Mail_mime();
        // Asignar mensaje
        $mail->setTXTBody($this->_data['text']);
        $mail->setHTMLBody($this->_data['html']);
        // Si existen archivos adjuntos agregarlos
        if (!empty($this->_data['attach'])) {
            foreach ($this->_data['attach'] as &$file) {
                $result = $mail->addAttachment(
                    isset($file['tmp_name']) ? $file['tmp_name'] : $file['data'],
                    $file['type'],
                    $file['name'],
                    isset($file['tmp_name']) ? true : false
                );
                if (is_a($result, 'PEAR_Error')) {
                    return [
                        'type' => $result->getType(),
                        'code' => $result->getCode(),
                        'message' => $result->getMessage(),
                    ];
                }
            }
        }
        // cuerpo y cabecera con codificación en UTF-8
        $body = $mail->get([
            'text_encoding' => '8bit',
            'text_charset'  => 'UTF-8',
            'html_charset'  => 'UTF-8',
            'head_charset'  => 'UTF-8',
            'head_encoding' => '8bit',
        ]); // debe llamarse antes de headers
        $to = implode(', ', $this->_header['to']);
        $headers_data = [
            'From' => $this->_header['from'],
            'To' => $to,
            'Subject' => $this->_header['subject'],
        ];
        if (!empty($this->_header['cc'])) {
            $headers_data['Cc'] = implode(', ', $this->_header['cc']);
        }
        if (!empty($this->_header['replyTo'])) {
            //$headers_data['Reply-To'] = $headers_data['Return-Path'] = $this->_header['replyTo'];
            $headers_data['Reply-To'] = $this->_header['replyTo'];
        }
        $headers = $mail->headers($headers_data);
        if(!empty($this->_header['cc'])) {
            $to .= ', '.implode(', ', $this->_header['cc']);
        }
        if(!empty($this->_header['bcc'])) {
            $to .= ', '.implode(', ', $this->_header['bcc']);
        }
        // Enviar correo a todos los destinatarios
        $result = $mailer->send($to, $headers, $body);
        // retornar estado del envío del mensaje
        if (is_a($result, 'PEAR_Error')) {
            return [
                'type' => $result->getType(),
                'code' => $result->getCode(),
                'message' => $result->getMessage(),
            ];
        } else return true;
    }

}
