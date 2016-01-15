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

// Генерируем имена файлов, для упакованного и не упакованного вариантов файла. Также для архива.
$newname = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_compr = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_zip = "upload_zip/" . date("Y-m-d") . "_" . rand(0, 10000) . ".zip";

if(!empty($myfile)) {

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
}

// Формируем JSON-запись, для передачи ее в js файл.
echo json_encode($newname.",".$newname_compr.",".$newname_zip);

?>