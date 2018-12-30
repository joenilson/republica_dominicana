/* 
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
 * Funcion para generar el daterangepicker en modo rango de fechas
 * @param {string} f_rango es el componente donde se mostrará el calendario
 * @param {string} f_desde es el id del input donde grabaremos la fecha de inicio
 * @param {string} f_hasta es el id del input donde grabaremos la fecha de fin
 * @param {string} formato es el formato en que guardaremos y mostraremos la fecha
 * @param {boolean} rangos es un campo para saber si mostramos o no el selector de rangos predefinidos
 * @param {boolean} tiempos es un campo booleano para saber si mostramos el selector de tiempo hora, minuto
 * @returns {empty} devuelve el selector con el rango de fechas
 */
function rango_fechas(f_rango, f_desde, f_hasta, formato, rangos, tiempos){
    moment().format(formato);
    if(typeof($('#'+f_rango)) !== 'undefined'){
        $('#'+f_rango).daterangepicker({
            singleDatePicker: false,
            showDropdowns: true,
            timePicker: tiempos,
            timePickerIncrement: (tiempos)?5:0,
            ranges: (rangos)?{
                'Hoy': [moment(), moment()],
                'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Ultimos 7 Días': [moment().subtract(6, 'days'), moment()],
                'Ultimos 30 días': [moment().subtract(29, 'days'), moment()],
                'Este mes': [moment().startOf('month'), moment().endOf('month')],
                'Anterior Mes': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }:null,
            locale: {
                format: formato,
                separator: " - ",
                applyLabel: "Tomar",
                cancelLabel: "Cancelar",
                fromLabel: "Desde",
                toLabel: "Hasta",
                customRangeLabel: "Manual"
            },
            opens: "right",
            startDate: moment().startOf('month'),
            endDate: moment()
        });
        
        $('#'+f_rango).on('apply.daterangepicker', function(ev, picker) {
            $('#'+f_desde).val(picker.startDate.format(formato));
            $('#'+f_hasta).val(picker.endDate.format(formato));
        });
        
        if($('#'+f_desde).val()){
            $('#'+f_rango).data('daterangepicker').setStartDate($('#'+f_desde).val());
        }else{
            $('#'+f_desde).val($('#'+f_rango).data('daterangepicker').startDate.format(formato));
        }

        if($('#'+f_hasta).val()){
            $('#'+f_rango).data('daterangepicker').setEndDate($('#'+f_hasta).val());
        }else{
            $('#'+f_hasta).val($('#'+f_rango).data('daterangepicker').endDate.format(formato));
        }
    }
}

/*
 * Funcion para generar el daterangepicker en modo single
 * @param id_field es el id donde llamaremos al calendario
 * @param formato es el formato en que necesitamos la fecha
 * @param tiempos es un cambo boolean si necesitamos o no la parte de tiempo hora, minuto
 */
function fecha(id_field, formato, tiempos){
    moment().format(formato);
    if(typeof($('#'+id_field)) !== 'undefined'){
        $('#'+id_field).daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: tiempos,
            timePickerIncrement: (tiempos)?5:0,
            locale: {
                format: formato,
                separator: " - ",
                applyLabel: "Tomar",
                cancelLabel: "Cancelar",
                fromLabel: "Desde",
                toLabel: "Hasta",
                customRangeLabel: "Manual"
            },
            opens: "left",
            startDate: moment()
        });
    }
}