﻿// Включаем строгий режим
"use strict";

// Добавляем переменную flag, для разветвления логики, по текстовому или архивному файлам.
var flag = null;

// Функция определения типа данных, выбранного файла.
function validateType(fileInput, type) {
    var fileObj, oType;
    // Будем читать первый выбранный файл
    if (typeof ActiveXObject == "function") { // IE
        fileObj = (new ActiveXObject("Scripting.FileSystemObject")).getFile(fileInput.value);
    } else {
        fileObj = fileInput.files[0];
    }

    // Через input.value переданного файла, вырезаем mime-тип файла. Необходимо для определения zip-архива.
    var input_type = document.getElementById("files").value;
    input_type = input_type.split(".")[1];

    // Определяем тип файла. Если он не текстовый или архивный, делаем 'return false' кнопке.
    oType = fileObj.type;
    if(fileObj.type !=='text/plain' && input_type !=='zip') {
        return false;
    }

    // Если выбран текстовый файл, ставим значение флага, равное "text".
    if(fileObj.type === 'text/plain') {
        flag = "text";
    }

    // Если выбран zip-архив, ставим значение флага, равное "zip".
    if(input_type === 'zip') {
        flag = "zip";
    }

    return true;
}

// При произведении выбора, определяем тип файла. С помощью функции 'validateType'.
$('#files').change(function () {
    // Если тип не верен:
    if(!validateType(this, 1)) {
        // Очищаем, под кнопкой, поле с названием файла.
        $("#fileName").val('');
        // Выводим в модальном окне предупреждающий текст
        $(".container").append('<div id="myModalBox_senderr" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-header">' +
        '<h5 class="modal-title">Выбран неверный тип файла. Можно загружать только файлы .txt или zip-архивы.</h5></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">' +
        'Закрыть</button></div></div></div></div>');
        $("#myModalBox_senderr").modal('show');
    } else {

        if(flag === "text") {
            // После выбора файла, меняем текст на кнопке.
            add_submit.innerHTML = 'Загрузить файл';
        }
        if(flag === "zip") {
            // После выбора архива, меняем текст на кнопке.
            add_submit.innerHTML = 'Загрузить архив';
        }

    }
});

// Добавляем функционал процесса загрузки, выбранного файла.
function log(html) {
    document.getElementById('log').innerHTML = html;
}

function onSuccess() {
    log('success');
}

function onError() {
    log('error');
}

function onProgress(loaded, total) {
    log(loaded + ' / ' + total);
}

// При нажатии на кнопку "Загрузить", запускаем функцию upload(). В ней происходит настройка xhr-запроса, отправление запроса с данными на сервер.
// Отображение процесса загрузки и подсчет отправленных кб.
document.forms.upload.onsubmit = function(e) {
    var input = this.elements.files;
    var file = input.files[0];
    if (file) {
        upload(file);
    }

    // Добавляем кнопке промежуточное состояние - "Загружаю".
    if(flag === "text") {
        // После выбора файла, меняем текст на кнопке.
        add_submit.innerHTML = 'Загружаю, упаковываю...';
    }
    if(flag === "zip") {
        // После выбора zip-архива, меняем текст на кнопке.
        add_submit.innerHTML = 'Загружаю, распаковываю...';
    }
    // После загрузки файла на сервер, отключаем кнопку.
    add_submit.disabled = true;

    // Очищаем под кнопкой "Выберите файл", поле с названием файла.
    $("#fileName").val('');

    return false;
}

function upload(file) {

    // Отправляем данные php-скрипту через xhr объект, т.к метод serialize() в $.ajax не передает вложенные файлы.
    var form = document.forms.upload;
    var formData = new FormData(form);
    var XHR = ("onload" in new XMLHttpRequest()) ? XMLHttpRequest : XDomainRequest;
    var xhr = new XHR();

    // Навешиваем события onload, onerror, завершения запроса
    xhr.onload = xhr.onerror = function() {

            // Плавно показываем кнопки с возможностью скачивания файлов.
            setTimeout(function() {

                // Показываем кнопки, для скачивания файла.
                if(flag === "text") {
                    $(".link").fadeIn(1000);
                }
                if(flag === "zip") {
                    $(".link_unpack").fadeIn(1000);
                }

                // Показываем дополнительнуюю ссылку внизу справа под формой, с возможностью обновления страницы.
                $(".update__link").css({'opacity' : '1'});

            }, 1000);

            // Если статус ответа 200:
            if(this.status == 200) {

                // Конвертируем строку с json-данными в javascript-объект, для вывода их в ссылках.
                var data = jQuery.parseJSON(this.responseText),
                    link_url = data;
                // В зависимости от того что было загружено на сервер: txt или zip, выбираем первый индекс массива. Для вывода значения в соотвествующей ссылке на скачивание.
                if(flag === "text") {
                    var link_url_file = data.split(",")[0];
                }
                if(flag === "zip") {
                    var link_url_unpack = data.split(",")[0];
                }
                var link_url_compr = data.split(",")[1],
                    link_url_zip = data.split(",")[2];
                $("#download").attr("href", link_url_file);
                $("#download_compr").attr("href", link_url_compr);
                $("#download_zip").attr("href", link_url_zip);
                $("#download_unpack").attr("href", link_url_unpack);

		    } else {
		        log("error " + this.status);
		    }
	};

    // Обработчик для закачки файла
    xhr.upload.onprogress = function(event) {
        setTimeout(function(){
            //Делаем искусственную задержку, состояния кнопки. Выводим на ней информацию о загруженных кб.
            add_submit.innerHTML = 'Готово! Загружено на сервер ' + event.total + " кб.";
        }, 1000);
    }

    // Настраиваем xhr-запрос, отправляем его.
    xhr.open("POST", "json.php?r=" + Math.random(), true);
    // Максимальная продолжительность асинхронного запроса (30 сек.)
    xhr.timeout = 30000;
    xhr.send(formData);
}

// Обновление страницы, по кнопке "Обновить страницу".
$('#reload').bind("click", function(e){
    window.location.reload();
    var anchor = $("#main");
    $('html, body').animate({scrollTop: $('#main').offset().top}, 1);
    e.preventDefault();
});