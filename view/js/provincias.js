/*  
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved. 
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
