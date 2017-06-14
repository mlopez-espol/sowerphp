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

/**
 * Clase base para trabajar con una base de datos cualquiera
 *
 * Define métodos que deberán ser implementados, clases específicas para
 * la conexión con X base de datos deberán extender esta clase
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2016-09-24
 */
abstract class Model_Datasource_Database_Manager extends \PDO
{

    public $config; ///< Configuración de la base de datos
    protected $inTransaction = 0; ///< Contador de solicitudes de transacciones en curso
    public static $querysCount = 0; ///< Indica la cantidad de consultas que se han realizado entre todas las BD

    /**
     * Manejador de errores para la base de datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-21
     */
    public function error($msg)
    {
        if ($this->inTransaction)
            $this->rollBack();
        throw new Exception_Model_Datasource_Database(array(
            'msg' => $msg
        ));
    }

    /**
     * Realizar consulta en la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return PDOStatement
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-23
     */
    public function query($sql, $params = array())
    {
        // verificar que exista una consulta
        if(empty($sql)) {
            $this->error('¡Consulta no puede estar vacía!');
        }
        // contabilizar consulta
        self::$querysCount++;
        // preparar consulta
        $stmt = $this->prepare($sql);
        if ($stmt===false) {
            $this->error('No fue posible preparar la consulta:'."\n\n".$sql);
        }
        //asignar parámetros
        foreach ($params as $key => &$param) {
            if (is_array($param)) {
                $stmt->bindParam($key, $param[0], $param[1]);
            } else if ($param===null || $param==='') {
                $stmt->bindValue($key, null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindParam($key, $param);
            }
        }
        // realizar consulta
        $stmt->execute();
        if(!$stmt || $stmt->errorCode()!=='00000') {
            $this->error(
                implode("\n", $stmt->errorInfo())."\n\n".$sql
            );
        }
        // retornar identificador de la consulta
        return $stmt;
    }

    /**
     * Obtener una tabla (como arreglo) desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Array Arreglo bidimensional con la tabla y sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getTable($sql, $params = array())
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una sola fila desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Array Arreglo unidimensional con la fila
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-02-27
     */
    public function getRow($sql, $params = array())
    {
        $stmt = $this->query($sql, $params);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return !empty($data) ? $data : [];
    }

    /**
     * Obtener una sola columna desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Array Arreglo unidimensional con la columna
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-06
     */
    public function getCol($sql, $params = array())
    {
        $stmt = $this->query($sql, $params);
        $cols = [];
        while (($col = $stmt->fetchColumn())!==false) {
            $cols[] = $col;
        }
        $stmt->closeCursor();
        return $cols;
    }

    /**
     * Obtener un solo valor desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Mixed Valor devuelto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-02-27
     */
    public function getValue($sql, $params = array())
    {
        $stmt = $this->query($sql, $params);
        $data = $stmt->fetchColumn();
        $stmt->closeCursor();
        return !empty($data) ? $data : '';
    }

    /**
     * Obtener un generador para una tabla desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Generator Object
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-06
     */
    public function getTableGenerator($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))!==false) {
            yield $row;
        }
        $stmt->closeCursor();
    }

    /**
     * Obtener un generador para una sola columna desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Generator Object
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-06
     */
    public function getColGenerator($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        while (($col = $stmt->fetchColumn())!==false) {
            yield $col;
        }
        $stmt->closeCursor();
    }

    /**
     * Obtener un arreglo con índice el identificador del registro que se
     * está consultando con algún valor asociado
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben enlazar a la consulta
     * @return Array Arreglo unidimensional con los índices y sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-31
     */
    public function getAssociativeArray($sql, $params = [])
    {
        return Utility_Array::tableToAssociativeArray($this->getTable($sql, $params));
    }

    /**
     * Wrapper para comenzar una transacción (evita iniciar más de una transacción)
     * @param serializable =true ejecutará la transacción de forma SERIALIZABLE (sólo MariaDB/MySQL y PostgreSQL)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-03-28
     */
    public function beginTransaction($serializable = false)
    {
        if (!$this->inTransaction) {
            // serializar transacción en MariaDB y MySQL
            if ($serializable and in_array($this->config['type'], ['MariaDB', 'MySQL'])) {
                $this->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            }
            // iniciar transacción
            if (parent::beginTransaction()) {
                // serializar transacción en PostgreSQL
                if ($serializable and $this->config['type']=='PostgreSQL') {
                    $this->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
                    $this->query('SET TRANSACTION READ WRITE');
                }
                // contabilizar la transacción
                $this->inTransaction++;
                return true;
            } else {
                return false;
            }
        } else {
            $this->inTransaction++;
            return true;
        }
    }

    /**
     * Wrapper para aceptar una transacción (evita hacer commit cuando no es la
     * primera transacción)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-28
     */
    public function commit()
    {
        if ($this->inTransaction) {
            if ($this->inTransaction==1)
                parent::commit();
            $this->inTransaction--;
            return true;
        }
        return false;
    }

    /**
     * Wrapper para cancelar una transacción (evita cancelar más de una transacción)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-28
     */
    public function rollBack()
    {
        if ($this->inTransaction and parent::rollBack()) {
            $this->inTransaction = 0;
            return true;
        }
        return false;
    }

    /**
     * Entrega información de una tabla (nombre, comentario, columnas,
     * pks y fks)
     * @param table Tabla a buscar sus datos
     * @return Arreglo con los datos de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-05-10
     */
    public function getInfoFromTable($tablename)
    {
        // nombre de la tabla
        $table['name'] = $tablename;
        // obtener comentario de la tabla
        $table['comment'] = $this->getCommentFromTable($table['name']);
        // obtener pks de la tabla
        $table['pk'] = $this->getPksFromTable($table['name']);
        // obtener fks de la tabla
        $fkAux = $this->getFksFromTable($table['name']);
        $fk = array();
        foreach($fkAux as &$aux) {
            $fk[array_shift($aux)] = $aux;
        }
        unset($fkAux);
        // obtener columnas de la tabla
        $columns = $this->getColsFromTable($table['name']);
        // recorrer columnas para definir pk, fk, auto, null/not null, default, comentario
        foreach($columns as &$column) {
            // definir null o not null
            $column['null'] = $column['null']=='YES' ? 1 : 0;
            // definir si es auto_increment (depende de la base de datos como se hace)
            if ($this->config['type']=='PostgreSQL') {
                $column['auto'] = substr($column['default'], 0, 7)=='nextval' ? 1 : 0;
            } else if ($this->config['type']=='MariaDB' || $this->config['type']=='MySQL') {
                $column['auto'] = $column['extra']=='auto_increment' ? 1 : 0;
                unset ($column['extra']);
            }
            // limpiar default, quitar lo que viene despues de ::
            if(!$column['auto']) {
                $aux = explode('::', $column['default']);
                $column['default'] = trim(array_shift($aux), '\'');
                if($column['default']=='NULL') $column['default'] = null;
            }
            // definir fk
            $column['fk'] = array_key_exists($column['name'], $fk) ? $fk[$column['name']] : null;
        }
        $table['columns'] = $columns;
        return $table;
    }

    /**
     * Seleccionar una tabla con los nombres de las columnas
     * @param sql Consulta SQL que se desea realizar
     * @param params Parámetros que se deben pasar a la consulta preparada
     * @return Arreglo con una tabla que tiene nombres de columnas y luego los datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-25
     */
    public function getTableWithColsNames($sql, $params = array())
    {
        $omitir = ['blob'];
        // variables para datos y claves
        $data = [];
        $columns = [];
        // realizar consulta
        $stmt = $this->query($sql, $params);
        // obtener información de las columnas
        $ncolumnas = $stmt->columnCount();
        for($i=0; $i<$ncolumnas; ++$i) {
            $aux = $stmt->getColumnMeta($i);
            $columns[$aux['name']] = $aux;
            unset($columns[$aux['name']]['name'], $aux);
        }
        $data[] = array_keys($columns);
        // agregar las filas de la consulta
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as &$row) {
            foreach ($row as $col => &$value) {
                if (in_array($columns[$col]['native_type'], $omitir))
                    $value = '['.$columns[$col]['native_type'].']';
            }
            $data[] = $row;
        }
        // retornar tabla
        return $data;
    }

    /**
     * Obtener una tabla mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo bidimensional con la tabla y sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getTableFromSP($procedure)
    {
    }

    /**
     * Obtener una sola fila mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo unidimensional con la fila
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getRowFromSP($procedure)
    {
    }

    /**
     * Obtener una sola columna mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo unidimensional con la columna
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getColFromSP($procedure)
    {
    }

    /**
     * Obtener un solo valor mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Mixed Valor devuelto por el procedimiento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getValueFromSP($procedure)
    {
    }

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL
     * @param sql Consulta SQL a la que se le agrega el límite
     * @return String Consulta con el límite agregado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function setLimit($sql, $records, $offset = 0)
    {
    }

    /**
     * Genera filtro para utilizar like en la consulta SQL
     * @param colum Columna por la que se filtrará
     * @param value Valor a buscar mediante like
     * @return String Filtro utilizando like
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function like($column, $value)
    {
    }

    /**
     * Concatena los parámetros pasados al método
     *
     * El método acepta n parámetros, pero dos como mínimo deben ser
     * pasados.
     * @param par1 Parámetro 1 que se quiere concatenar
     * @param par2 Parámetro 2 que se quiere concatenar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function concat($par1, $par2)
    {
    }

    /**
     * Entrega una fecha en cierto formato, se puede entregar a partir de cierta
     * fecha y hora o bien con la fecha y hora actual
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-10-12
     */
    public function date($format, $datetime = null, $cast = null)
    {
    }

    /**
     * Extrae un valor desde un nodo de un XML almacenado en una columna de la
     * base de datos
     * Este método es por compatibilidad, aquellas BD que no soportan este método
     * entregarán NULL para cada PATH solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-12-21
     */
    public function xml($column, $path, $namespace = null, $data_format = null)
    {
        if (!is_array($path))
            $path = [$path];
        $select = [];
        foreach ($path as $p) {
            $select[] = 'NULL';
        }
        return count($select)>1 ? $select : array_shift($select);
    }

    /**
     * Listado de tablas de la base de datos
     * @return Array Arreglo con las tablas (nombre y comentario)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getTables()
    {
    }

    /**
     * Obtener comentario de una tabla
     * @param table Nombre de la tabla
     * @return String Comentario de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getCommentFromTable($table)
    {
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto)
     * @param table Tabla a la que se quiere buscar las columnas
     * @return Array Arreglo con la información de las columnas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getColsFromTable($table)
    {
    }

    /**
     * Listado de claves primarias de una tabla
     * @param table Tabla a buscar su o sus claves primarias
     * @return Arreglo con la o las claves primarias
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getPksFromTable($table)
    {
    }

    /**
     * Listado de claves foráneas de una tabla
     * @param table Tabla a buscar su o sus claves foráneas
     * @return Arreglo con la o las claves foráneas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function getFksFromTable($table)
    {
    }

    /**
     * Método que entrega el tipo de base de datos instanciada
     * @return Tipo de la BD instanciada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-02-04
     */
    public function __toString()
    {
        return $this->config['type'];
    }

}
