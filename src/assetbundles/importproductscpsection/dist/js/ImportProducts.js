/**
 * Import Products plugin for Craft CMS
 *
 * ImportProducts Field JS
 *
 * @author    Davy Delbeke
 * @copyright Copyright (c) 2020 Davy Delbeke
 * @link      http://www.upclose.be
 * @package   ImportProducts
 * @since     1.0.0
 */

// function submitForm(e) {
//     e.preventDefault();
//
//     var form = document.getElementById('upload');
//     var formData = new FormData(form);
//
//     var responseContainer = document.getElementById('response-container');
//
//     var xhr = new XMLHttpRequest();
//     xhr.onreadystatechange = function(e){
//         responseContainer.innerHTML = ''
//         if (xhr.readyState === 4) {
//             var response = JSON.parse(xhr.responseText);
//             console.log(response);
//             if (xhr.status === 200) {
//                 console.log('successful');
//                 console.log(response);
//             } else {
//                 console.log('failed');
//             }
//         }
//     }
//
//     xhr.open('POST', '/actions/import-products/product/create', true);
//     xhr.send(formData);
// }
//
// document.querySelector('#upload').onsubmit = submitForm;
