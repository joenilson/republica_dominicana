/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Funciones para el manejo de NCF para Republica Dominicana
 * versión 1
 * @TODO Agregar boton de Corregir NCF
 * @TODO Manejar el boton de Rectificar por aqui
 * @TODO Manejar el boton de Anular por aqui
 *
 */
function calcular(){
    var m =  $("#monto_rectificar").val();
    var i =  $("#impuesto_rectificar").val();
    var mt = m*((i/100)+1);
    mt.toFixed(4);
    $("#monto_total").val(mt);
}

$(document).ready(function() {
    $("#b_rectificar").click(function(event) {
        event.preventDefault();
        $("#modal_rectificar").modal('show');
    });
    $("#link_eliminar").click(function(event){
        event.preventDefault();
        $("#f_eliminar").attr('action', url_borrar_factura);
        $("#f_eliminar").attr('motivo', $("#motivo").val());
        $("#f_eliminar").submit();
    });
});

