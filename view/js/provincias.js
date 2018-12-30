/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var provincia_list = [
    {value: 'Azua - Azua de Compostela'},
    {value: 'Bahoruco - Neiba'},
    {value: 'Barahona - Santa Cruz de Barahona'},
    {value: 'Dajabón - Dajabón'},
    {value: 'Distrito Nacional - Santo Domingo'},
    {value: 'Duarte - San Francisco de Macorís'},
    {value: 'Elías Piña - Comendador'},
    {value: 'El Seibo - Santa Cruz del Seibo'},
    {value: 'Espaillat - Moca'},
    {value: 'Hato Mayor - Hato Mayor del Rey'},
    {value: 'Hermanas Mirabal - Salcedo'},
    {value: 'Independencia - Jimaní'},
    {value: 'La Altagracia - Salvaleón de Higüey'},
    {value: 'La Romana - La Romana'},
    {value: 'La Vega - Concepción de la Vega'},
    {value: 'María Trinidad Sánchez - Nagua'},
    {value: 'Monseñor Nouel - Bonao'},
    {value: 'Monte Cristi - San Fernando de Monte Cristi'},
    {value: 'Monte Plata - Monte Plata'},
    {value: 'Pedernales - Pedernales'},
    {value: 'Peravia - Baní'},
    {value: 'Puerto Plata - San Felipe de Puerto Plata'},
    {value: 'Samaná - Santa Bárbara de Samaná'},
    {value: 'Sánchez Ramírez - Cotuí'},
    {value: 'San Cristóbal - San Cristóbal'},
    {value: 'San José de Ocoa - San José de Ocoa'},
    {value: 'San Juan - San Juan de la Maguana'},
    {value: 'San Pedro de Macorís - San Pedro de Macorís'},
    {value: 'Santiago - Santiago de los Caballeros'},
    {value: 'Santiago Rodríguez - Sabaneta'},
    {value: 'Santo Domingo - Santo Domingo Este'},
    {value: 'Valverde - Mao'},
];

$(document).ready(function() {
   $("#ac_provincia, #ac_provincia2").autocomplete({
      lookup: provincia_list,
   });
});
