<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('agente.php');
require_model('cliente.php');
require_model('cuenta_banco_cliente.php');
require_model('direccion_cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('grupo_clientes.php');
require_model('pais.php');
require_model('serie.php');
require_model('ncf_tipo.php');
require_model('ncf_entidad_tipo.php');

class ventas_cliente extends fs_controller {

    public $agente;
    public $allow_delete;
    public $cliente;
    public $cuenta_banco;
    public $divisa;
    public $forma_pago;
    public $grupo;
    public $pais;
    public $serie;
    public $ncf_tipo;

    public function __construct() {
        parent::__construct(__CLASS__, 'Cliente', 'ventas', FALSE, FALSE);
    }

    protected function private_core() {
        $this->ppage = $this->page->get('ventas_clientes');
        $this->agente = new agente();
        $this->cuenta_banco = new cuenta_banco_cliente();
        $this->divisa = new divisa();
        $this->forma_pago = new forma_pago();
        $this->grupo = new grupo_clientes();
        $this->pais = new pais();
        $this->serie = new serie();
        $this->ncf_tipo = new ncf_tipo();
        $this->ncf_entidad_tipo = new ncf_entidad_tipo();
        /// cargamos el cliente
        $cliente = new cliente();
        $this->cliente = FALSE;        
        if (isset($_POST['codcliente'])) {
            $this->cliente = $cliente->get($_POST['codcliente']);
            //$this->ncf_cliente_tipo = $this->ncf_entidad_tipo->get($this->empresa->id,$_POST['codcliente'], 'CLI');
        } else if (isset($_GET['cod'])) {
            $this->cliente = $cliente->get($_GET['cod']);
            //$this->ncf_cliente_tipo = $this->ncf_entidad_tipo->get($this->empresa->id,$_GET['cod'], 'CLI');
        }
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);



        /// ¿Hay que hacer algo más?
        if (isset($_GET['delete_cuenta'])) { /// eliminar cuenta bancaria
            $cuenta = $this->cuenta_banco->get($_GET['delete_cuenta']);
            if ($cuenta) {
                if ($cuenta->delete()) {
                    $this->new_message('Cuenta bancaria eliminada correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar la cuenta bancaria.');
            } else
                $this->new_error_msg('Cuenta bancaria no encontrada.');
        }
        else if (isset($_GET['delete_dir'])) { /// eliminar dirección
            $dir = new direccion_cliente();
            $dir0 = $dir->get($_GET['delete_dir']);
            if ($dir0) {
                if ($dir0->delete()) {
                    $this->new_message('Dirección eliminada correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar la dirección.');
            } else
                $this->new_error_msg('Dirección no encontrada.');
        }
        else if (isset($_POST['coddir'])) { /// añadir/modificar dirección
            $dir = new direccion_cliente();
            if ($_POST['coddir'] != '') {
                $dir = $dir->get($_POST['coddir']);
            }
            $dir->apartado = $_POST['apartado'];
            $dir->ciudad = $_POST['ciudad'];
            $dir->codcliente = $this->cliente->codcliente;
            $dir->codpais = $_POST['pais'];
            $dir->codpostal = $_POST['codpostal'];
            $dir->descripcion = $_POST['descripcion'];
            $dir->direccion = $_POST['direccion'];
            $dir->domenvio = isset($_POST['direnvio']);
            $dir->domfacturacion = isset($_POST['dirfact']);
            $dir->provincia = $_POST['provincia'];
            if ($dir->save()) {
                $this->new_message("Dirección guardada correctamente.");
            } else
                $this->new_message("¡Imposible guardar la dirección!");
        }
        else if (isset($_POST['iban'])) { /// añadir/modificar dirección
            if (isset($_POST['codcuenta'])) {
                $cuentab = $this->cuenta_banco->get($_POST['codcuenta']);
            } else {
                $cuentab = new cuenta_banco_cliente();
                $cuentab->codcliente = $_POST['codcliente'];
            }
            $cuentab->descripcion = $_POST['descripcion'];

            if ($_POST['ciban'] != '') {
                $cuentab->iban = $this->calcular_iban($_POST['ciban']);
            } else
                $cuentab->iban = $_POST['iban'];

            $cuentab->swift = $_POST['swift'];

            if ($cuentab->save()) {
                $this->new_message('Cuenta bancaria guardada correctamente.');
            } else
                $this->new_error_msg('Imposible guardar la cuenta bancaria.');
        }
        else if (isset($_POST['codcliente'])) { /// modificar cliente
            $this->cliente->nombre = $_POST['nombre'];
            $this->cliente->razonsocial = $_POST['razonsocial'];
            $this->cliente->cifnif = $_POST['cifnif'];
            $this->cliente->telefono1 = $_POST['telefono1'];
            $this->cliente->telefono2 = $_POST['telefono2'];
            $this->cliente->fax = $_POST['fax'];
            $this->cliente->web = $_POST['web'];
            $this->cliente->email = $_POST['email'];
            $this->cliente->observaciones = $_POST['observaciones'];
            $this->cliente->codserie = $_POST['codserie'];
            $this->cliente->codpago = $_POST['codpago'];
            $this->cliente->coddivisa = $_POST['coddivisa'];
            $this->cliente->regimeniva = $_POST['regimeniva'];
            $this->cliente->recargo = isset($_POST['recargo']);
            $this->cliente->debaja = isset($_POST['debaja']);

            $this->cliente->codagente = NULL;
            if ($_POST['codagente'] != '---') {
                $this->cliente->codagente = $_POST['codagente'];
            }

            $this->cliente->codgrupo = NULL;
            if ($_POST['codgrupo'] != '---') {
                $this->cliente->codgrupo = $_POST['codgrupo'];
            }
            
            if(isset($_POST['tipo_comprobante'])){
                $continue = TRUE;
                $tipo_comprobante = \filter_input(INPUT_POST, 'tipo_comprobante');
                if($tipo_comprobante == '01' AND strlen($this->cliente->cifnif)<9){
                    $this->new_error_msg("¡Imposible actualizar información de NCF para el cliente, por favor corrija primero la Cédula o RNC asignados!");
                }else{
                    $ncf_entidad_tipo = new ncf_entidad_tipo();
                    $ncf_entidad_tipo->idempresa = $this->empresa->id;
                    $ncf_entidad_tipo->entidad = \filter_input(INPUT_POST, 'codcliente');
                    $ncf_entidad_tipo->tipo_entidad = 'CLI';
                    $ncf_entidad_tipo->tipo_comprobante = $tipo_comprobante;
                    $ncf_entidad_tipo->usuario_creacion = $this->user->nick;
                    $ncf_entidad_tipo->fecha_creacion = \Date('d-m-Y H:i');
                    $ncf_entidad_tipo->usuario_modificacion = $this->user->nick;
                    $ncf_entidad_tipo->fecha_modificacion = \Date('d-m-Y H:i');
                    $ncf_entidad_tipo->estado = 'true';
                    if (!$ncf_entidad_tipo->save()) {
                        $this->new_error_msg("¡Imposible actualizar información de NCF para  Cliente ".$ncf_entidad_tipo->entidad."!");
                    }
                }
            }
            

            if ($this->cliente->save()) {
                $this->new_message("Datos del cliente modificados correctamente.");
            } else
                $this->new_error_msg("¡Imposible modificar los datos del cliente!");
        }

        if ($this->cliente) {
            $this->page->title = $this->cliente->codcliente;
            $this->ncf_cliente_tipo = $this->ncf_entidad_tipo->get($this->empresa->id,$this->cliente->codcliente, 'CLI');
        } else {
            $this->new_error_msg("¡Cliente no encontrado!");
        }
    }

    public function url() {
        if (!isset($this->cliente)) {
            return parent::url();
        } else if ($this->cliente) {
            return $this->cliente->url();
        } else
            return $this->ppage->url();
    }

    public function this_year($previous = 0) {
        return intval(Date('Y')) - $previous;
    }

    private function calcular_iban($ccc) {
        $codpais = substr($this->empresa->codpais, 0, 2);

        foreach ($this->cliente->get_direcciones() as $dir) {
            if ($dir->domfacturacion) {
                $codpais = substr($dir->codpais, 0, 2);
                break;
            }
        }

        $pesos = array('A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35'
        );

        $dividendo = $ccc . $pesos[substr($codpais, 0, 1)] . $pesos[substr($codpais, 1, 1)] . '00';
        $digitoControl = 98 - bcmod($dividendo, '97');

        if (strlen($digitoControl) == 1)
            $digitoControl = '0' . $digitoControl;

        return $codpais . $digitoControl . $ccc;
    }

    /*
     * Devuelve un array con los datos estadísticos de las compras del cliente
     * en los cinco últimos años.
     */

    public function stats_from_cli() {
        $stats = array();
        $years = array();
        for ($i = 4; $i >= 0; $i--)
            $years[] = intval(Date('Y')) - $i;

        $meses = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');

        foreach ($years as $year) {
            for ($i = 1; $i <= 12; $i++) {
                $stats[$year . '-' . $i]['mes'] = $meses[$i - 1] . ' ' . $year;
                $stats[$year . '-' . $i]['albaranes'] = 0;
                $stats[$year . '-' . $i]['facturas'] = 0;
            }

            if (strtolower(FS_DB_TYPE) == 'postgresql')
                $sql_aux = "to_char(fecha,'FMMM')";
            else
                $sql_aux = "DATE_FORMAT(fecha, '%m')";

            $data = $this->db->select("SELECT " . $sql_aux . " as mes, sum(total) as total
            FROM albaranescli WHERE fecha >= " . $this->empresa->var2str(Date('1-1-' . $year)) . "
            AND fecha <= " . $this->empresa->var2str(Date('31-12-' . $year)) . " AND codcliente = " . $this->empresa->var2str($this->cliente->codcliente) . "
            GROUP BY " . $sql_aux . " ORDER BY mes ASC;");
            if ($data) {
                foreach ($data as $d)
                    $stats[$year . '-' . intval($d['mes'])]['albaranes'] = number_format($d['total'], FS_NF0, '.', '');
            }

            $data = $this->db->select("SELECT " . $sql_aux . " as mes, sum(total) as total
            FROM facturascli WHERE fecha >= " . $this->empresa->var2str(Date('1-1-' . $year)) . "
            AND fecha <= " . $this->empresa->var2str(Date('31-12-' . $year)) . " AND codcliente = " . $this->empresa->var2str($this->cliente->codcliente) . "
            GROUP BY " . $sql_aux . " ORDER BY mes ASC;");
            if ($data) {
                foreach ($data as $d)
                    $stats[$year . '-' . intval($d['mes'])]['facturas'] = number_format($d['total'], FS_NF0, '.', '');
            }
        }

        return $stats;
    }

    public function tiene_facturas() {
        $tiene = FALSE;

        if ($this->db->table_exists('facturascli')) {
            $data = $this->db->select_limit("SELECT * FROM facturascli WHERE codcliente = '" . $this->cliente->codcliente . "'", 5, 0);
            if ($data) {
                $tiene = TRUE;
            }
        }

        if (!$tiene AND $this->db->table_exists('albaranescli')) {
            $data = $this->db->select_limit("SELECT * FROM albaranescli WHERE codcliente = '" . $this->cliente->codcliente . "'", 5, 0);
            if ($data) {
                $tiene = TRUE;
            }
        }

        return $tiene;
    }

}
