<?

//В header() задаем формат вывода JSON-записи, указываем, что документ является JavaScript'ом в кодировке UTF-8.
header('Content-Type: application/x-javascript; charset=utf8');

// Подключаем файл с компрессором Хаффмана
include ("huffman.php");

if(isset($_FILES['files'])) {
    $myfile = $_FILES["files"]["tmp_name"];
    $myfile_name = $_FILES["files"]["name"];
    $myfile_size = $_FILES["files"]["size"];
    $myfile_type = $_FILES["files"]["type"];
}

// Генерируем путь к файлам, для упакованного и не упакованного вариантов. Также для архива.
$newname = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_compr = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_zip = "upload_zip/" . date("Y-m-d") . "_" . rand(0, 10000) . ".zip";

// Если получили zip-архив от формы:
// генерируем путь для распакованного из архива файла. Также новое название файла, после переименования полученного, функцией rename().
$newname_unpack = "upload_unpack/" . date("Y-m-d") . "_" . rand(0, 10000);
$newname_unpack_name = "upload_unpack/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";

if(!empty($myfile) && $myfile_type == "text/plain") {

    if($myfile_type != "text/plain") {
        echo ("Выбран неверный тип файла. Можно загружать только файлы .txt");
    } else {
    if ($myfile_type=="text/plain") {

        // Читаем данные из полученного от формы текстового файла.
        $data = implode("", file($myfile));

        // Меняем их кодировку на "UTF-8", для браузера IE.
        $data_encoding = mb_convert_encoding($data, "UTF-8", "windows-1251");

        // Определяем тип браузера.
        $user_agent = $_SERVER["HTTP_USER_AGENT"];

        // С помощью компрессора Хаффмана, сжимаем полученные данные
        $huffman = new Huffman();
        // Если браузер IE, добавляем на компрессинг $data_encoding.
        if( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') !== false ||
            strpos($_SERVER['HTTP_USER_AGENT'],'rv:11.0') !== false) {
                $compressed = $huffman->compress($data_encoding);
        }
        else {
            $compressed = $huffman->compress($data);
        }

        // Распаковываем декомпрессором данные
        $huffman2 = new Huffman();
        $decompressed = $huffman2->decompress($compressed);

        // Записываем упакованные данные в файл $myfile.
        $fp = fopen($myfile, "w");
        fwrite($fp, $compressed);
        fclose($fp);

        // Копируем временный файл в папку $newname_compr.
        copy($myfile, $newname_compr);

        // Очищаем содержимое файла $myfile.
        file_put_contents($myfile, '');

        // Записываем распакованные данные во временный файл $myfile.
        $fp = fopen($myfile, "w");
        fwrite($fp, $decompressed);
        fclose($fp);

        // Копируем временный файл в папку $newname.
        copy($myfile, $newname);

        //Создание zip-архива на сервере
        $zip = new ZipArchive;
        if ($zip->open($newname_zip, ZipArchive::CREATE) === true) {
	        $zip->addFromString('info_compressed.txt', $compressed);
            $zip->addFromString('info.txt', $decompressed);
	        $zip->close();
        } else {
	        echo 'Не могу создать архив!';
        }
    }
    }
    // Формируем JSON-запись, для передачи ее в js файл.
    echo json_encode($newname.",".$newname_compr.",".$newname_zip);
}
// Если передан архив на сервер
else {

    // Объявляем экземпляр класса ZipArchive.
    $zip = new ZipArchive;

    //Сюда будем складывать имена файлов.
    //$filenames = array();

    // Разархивируем полученный от формы архив.
    if ($zip->open($myfile) === true) {
        //цикл, проходим по индексам файлов
        /*
        for($i=0; $i<$zip->numFiles; $i++) {
            //с помощью метода getNameIndex получаем имя элемента по индексу
            //и помещаем в наш массив имён ;)
            $filenames[] = $zip->getNameIndex($i);
            $zip->renameName($filenames[i],'info.txt');
        }
        */
        // $zip->renameName(getNameIndex('1'),'info.txt');
        $zip->extractTo($newname_unpack);
	    $zip->close();
    } else {
	    echo 'Не могу найти файл архива!';
    }

    // Папка куда распаковались файлы.
    $dir_unpack = $newname_unpack;

    // Получаем список файлов в ней.
    $f = scandir($dir_unpack);

    // Находим распакованный .txt файл. Присваиваем его переменной $dir_unpack_file.
    foreach ($f as $file){
        if(preg_match('/\.(txt)/', $file)) {
            $dir_unpack_file = $newname_unpack."\\".$file;

            // Считываем данные из файла, в перeменную $data_zip
            $data_zip = implode("", file($dir_unpack_file));

            // Делаем компрессинг полученных данных
            $huffman3 = new Huffman();
            $compressed = $huffman3->compress($data_zip);

            // Делаем декомпрессинг полученных данных
            $huffman4 = new Huffman();
            $decompressed = $huffman4->decompress($compressed);

            // Записываем распакованные данные в файл $dir_unpack_file.
            $fp = fopen($dir_unpack_file, "w");
            fwrite($fp, $decompressed);
            fclose($fp);
        }
    }

    // Переносим файл $dir_unpack_file в папку upload_unpack. Чтобы скачивание по ссылке "Скачать распакованный файл", производилось по правильному пути.
    rename($dir_unpack_file, $newname_unpack_name);
    // После переноса файла, удаляем временную папку с первоначальными, разархивированными данными.
    rmdir($newname_unpack);

    // Формируем JSON-запись, для передачи ее в js файл.
    echo json_encode($newname_unpack_name);
}

?>